<?php
/**
 * Flujo de tramites y circuito de firmas del modulo de Equipos Rentados.
 *
 * Reglas (seccion 3.2 del plan, NO cambiar sin consultar):
 *  - Solo el administrador del contrato crea tramites, desde el panel.
 *  - Firma 1 = administrador, al crear. Los demas firman uno por uno, en orden.
 *  - El custodio cambia SOLO al completarse la ultima firma (en transaccion).
 *  - Tokens de 32 bytes; en BD solo su hash SHA-256. Se invalidan al usarse,
 *    al rechazar, al anular o al reenviar.
 *  - El acta se genera una vez y nunca se regenera: solo crece con firmas.
 */

require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/PdfSigner.php';
require_once __DIR__ . '/CmsVerifier.php';
require_once __DIR__ . '/Notificador.php';
require_once __DIR__ . '/acta_pdf.php';

date_default_timezone_set('America/Guayaquil');

const EQR_MAX_INTENTOS_FIRMA = 8;

/** Roles de firma por tipo de tramite: orden => [rol, prefijo de datos en el tramite]. */
function eqrRolesTramite(string $tipo): array
{
    return match ($tipo) {
        'asignacion' => [1 => ['administrador', 'admin_contrato_'], 2 => ['nuevo_custodio', 'recibe_']],
        'traspaso'   => [1 => ['administrador', 'admin_contrato_'], 2 => ['custodio_anterior', 'entrega_'],
                         3 => ['nuevo_custodio', 'recibe_']],
        'devolucion' => [1 => ['administrador', 'admin_contrato_'], 2 => ['custodio', 'entrega_']],
        default => throw new InvalidArgumentException('Tipo de trámite no soportado.'),
    };
}

/** Bitacora con usuario explicito (sirve en paginas publicas y admin). */
function eqrFlujoBitacora(PDO $db, ?int $equipoId, ?int $tramiteId, string $accion,
                          array $detalle, string $usuario): void
{
    $stmt = $db->prepare("INSERT INTO eqr_bitacora (equipo_id, tramite_id, accion, detalle, usuario, ip)
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$equipoId, $tramiteId, $accion,
        json_encode($detalle, JSON_UNESCAPED_UNICODE), $usuario, $_SERVER['REMOTE_ADDR'] ?? null]);
}

/** Codigo unico TRM-AAAAMMDD-XXXX. */
function eqrGenerarCodigoTramite(PDO $db): string
{
    for ($i = 0; $i < 20; $i++) {
        $codigo = 'TRM-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        $stmt = $db->prepare("SELECT 1 FROM eqr_tramites WHERE codigo = ?");
        $stmt->execute([$codigo]);
        if (!$stmt->fetch()) { return $codigo; }
    }
    throw new RuntimeException('No se pudo generar un código de trámite único.');
}

/** ¿El equipo tiene un tramite de custodia abierto (borrador o en firma)? */
function eqrTramiteAbierto(PDO $db, int $equipoId): ?array
{
    $stmt = $db->prepare("SELECT id, codigo, tipo, estado FROM eqr_tramites
                          WHERE equipo_id = ? AND tipo IN ('asignacion','traspaso','devolucion')
                          AND estado IN ('borrador','en_firma') LIMIT 1");
    $stmt->execute([$equipoId]);
    return $stmt->fetch() ?: null;
}

/**
 * Crea el tramite en estado BORRADOR: fila en eqr_tramites, firmas en orden
 * (la 1 'pendiente' para el administrador, el resto 'en_espera') y el acta PDF.
 * Devuelve el id del tramite. Todo o nada.
 */
function eqrCrearTramite(PDO $db, string $tipo, array $equipo, array $contrato,
                         array $datos, string $usuarioAdmin): int
{
    $roles = eqrRolesTramite($tipo);
    $codigo = eqrGenerarCodigoTramite($db);

    $tramite = [
        'codigo' => $codigo, 'tipo' => $tipo,
        'equipo_id' => (int)$equipo['id'], 'contrato_id' => (int)$contrato['id'],
        'estado' => 'borrador',
        'entrega_cedula' => $datos['entrega_cedula'] ?? null,
        'entrega_nombre' => $datos['entrega_nombre'] ?? null,
        'entrega_correo' => $datos['entrega_correo'] ?? null,
        'entrega_cargo' => $datos['entrega_cargo'] ?? null,
        'entrega_area' => $datos['entrega_area'] ?? null,
        'recibe_cedula' => $datos['recibe_cedula'] ?? null,
        'recibe_nombre' => $datos['recibe_nombre'] ?? null,
        'recibe_correo' => $datos['recibe_correo'] ?? null,
        'recibe_cargo' => $datos['recibe_cargo'] ?? null,
        'recibe_area' => $datos['recibe_area'] ?? null,
        // datos del administrador VIGENTE copiados al tramite (regla 3.1)
        'admin_contrato_nombre' => $contrato['admin_nombre'],
        'admin_contrato_cedula' => $contrato['admin_cedula'],
        'admin_contrato_cargo' => $contrato['admin_cargo'],
        'admin_contrato_correo' => $contrato['admin_correo'],
        'admin_contrato_calidad' => $contrato['admin_calidad'],
        'motivo' => $datos['motivo'] ?? null,
        'estado_fisico_reportado' => $datos['estado_fisico_reportado'] ?? null,
        'accesorios' => $datos['accesorios'] ?? null,
        'observaciones' => $datos['observaciones'] ?? null,
        'creado_por' => $usuarioAdmin,
    ];

    $db->beginTransaction();
    try {
        $cols = implode(', ', array_keys($tramite));
        $ph = implode(', ', array_map(fn($k) => ":$k", array_keys($tramite)));
        $db->prepare("INSERT INTO eqr_tramites ($cols) VALUES ($ph)")->execute($tramite);
        $tramiteId = (int)$db->lastInsertId();

        $firmantes = [];
        $insF = $db->prepare("INSERT INTO eqr_firmas (tramite_id, orden, rol, cedula, nombre, correo, cargo, estado)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($roles as $orden => [$rol, $pref]) {
            $f = [
                'orden' => $orden, 'rol' => $rol,
                'cedula' => (string)($tramite[$pref . 'cedula'] ?? ''),
                'nombre' => (string)($tramite[$pref . 'nombre'] ?? ''),
                'correo' => (string)($tramite[$pref . 'correo'] ?? ''),
                'cargo' => (string)($tramite[$pref . 'cargo'] ?? ''),
            ];
            $insF->execute([$tramiteId, $orden, $rol, $f['cedula'], $f['nombre'], $f['correo'], $f['cargo'],
                            $orden === 1 ? 'pendiente' : 'en_espera']);
            $firmantes[] = $f;
        }

        // Acta: una sola vez. Si algo falla, rollback y se borra la carpeta.
        $tramiteCompleto = $tramite + ['id' => $tramiteId, 'created_at' => date('Y-m-d H:i:s')];
        $acta = eqrGenerarActa($tramiteCompleto, $equipo, $contrato, $firmantes);
        $rel = 'storage/actas/' . basename(eqrActaDir($codigo)) . '/acta.pdf';
        $db->prepare("UPDATE eqr_tramites SET pdf_original = ?, pdf_actual = ?, hash_actual = ? WHERE id = ?")
           ->execute([$rel, $rel, $acta['hash'], $tramiteId]);

        eqrFlujoBitacora($db, (int)$equipo['id'], $tramiteId, 'tramite_creado',
            ['codigo' => $codigo, 'tipo' => $tipo], $usuarioAdmin);
        $db->commit();
        return $tramiteId;
    } catch (Throwable $ex) {
        $db->rollBack();
        $dir = eqrActaDir($codigo);
        @unlink($dir . '/acta.pdf'); @unlink($dir . '/v0.pdf'); @unlink($dir . '/acta.state.json'); @rmdir($dir);
        throw $ex;
    }
}

function eqrTramitePorId(PDO $db, int $id): ?array
{
    $stmt = $db->prepare("SELECT t.*, e.codigo_interno equipo_codigo, e.marca equipo_marca,
                                 e.modelo equipo_modelo, e.serie equipo_serie, e.estado equipo_estado
                          FROM eqr_tramites t JOIN eqr_equipos e ON e.id = t.equipo_id WHERE t.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function eqrFirmasDeTramite(PDO $db, int $tramiteId): array
{
    $stmt = $db->prepare("SELECT * FROM eqr_firmas WHERE tramite_id = ? ORDER BY orden");
    $stmt->execute([$tramiteId]);
    return $stmt->fetchAll();
}

/** La firma cuyo turno esta activo (estado 'pendiente'). */
function eqrFirmaPendiente(PDO $db, int $tramiteId): ?array
{
    $stmt = $db->prepare("SELECT * FROM eqr_firmas WHERE tramite_id = ? AND estado = 'pendiente'
                          ORDER BY orden LIMIT 1");
    $stmt->execute([$tramiteId]);
    return $stmt->fetch() ?: null;
}

// =====================================================================
//  Tokens de firma
// =====================================================================

/** Crea un token nuevo para la firma (invalida el anterior) y lo devuelve EN CLARO. */
function eqrTokenNuevo(PDO $db, int $firmaId): array
{
    $token = bin2hex(random_bytes(32));
    $dias = max(1, (int)getConfig('eqr_dias_token', '15'));
    $expira = date('Y-m-d H:i:s', time() + $dias * 86400);
    $db->prepare("UPDATE eqr_firmas SET token_hash = ?, token_expira = ?, intentos_fallidos = 0 WHERE id = ?")
       ->execute([hash('sha256', $token), $expira, $firmaId]);
    return ['token' => $token, 'expira' => $expira];
}

function eqrUrlFirma(string $token): string
{
    $base = rtrim(trim(getConfig('eqr_url_base')), '/');
    return ($base !== '' ? $base : '') . '/firmar.php?t=' . $token;
}

/** Busca la firma por token en claro. Devuelve firma + tramite o null. */
function eqrFirmaPorToken(PDO $db, string $token): ?array
{
    if (!preg_match('/^[0-9a-f]{64}$/', $token)) { return null; }
    $stmt = $db->prepare("SELECT * FROM eqr_firmas WHERE token_hash = ? LIMIT 1");
    $stmt->execute([hash('sha256', $token)]);
    $firma = $stmt->fetch();
    if (!$firma) { return null; }
    $tramite = eqrTramitePorId($db, (int)$firma['tramite_id']);
    if (!$tramite) { return null; }
    return ['firma' => $firma, 'tramite' => $tramite];
}

/** Valida que el token este vigente y sea el turno de esa firma. Devuelve ?string error. */
function eqrTokenProblema(array $firma, array $tramite): ?string
{
    if ($tramite['estado'] === 'anulado') { return 'El trámite fue anulado por el administrador.'; }
    if ($tramite['estado'] === 'rechazado') { return 'El trámite fue rechazado y ya no admite firmas.'; }
    if ($tramite['estado'] === 'completado') { return 'El trámite ya se completó: el acta tiene todas sus firmas.'; }
    if ($firma['estado'] === 'firmado') { return 'Usted ya firmó esta acta. Gracias.'; }
    if ($firma['estado'] === 'rechazado') { return 'Usted rechazó este trámite.'; }
    if ($firma['estado'] !== 'pendiente') { return 'Todavía no es su turno de firma. Recibirá un nuevo aviso cuando lo sea.'; }
    if ($firma['token_expira'] !== null && strtotime($firma['token_expira']) < time()) {
        return 'El enlace de firma venció. Solicite al administrador del contrato que se lo reenvíe.';
    }
    if ((int)$firma['intentos_fallidos'] >= EQR_MAX_INTENTOS_FIRMA) {
        return 'Se superó el número de intentos permitidos. Solicite al administrador un nuevo enlace.';
    }
    return null;
}

// =====================================================================
//  Preparar / completar una firma (nucleo criptografico)
// =====================================================================

function eqrRutaActaAbs(array $tramite): string
{
    return eqrActaPath($tramite['codigo']);
}

/** Prepara la firma del firmante de turno. Devuelve bytes a firmar (B64). */
function eqrPrepararFirma(array $tramite, array $firma): array
{
    $rects = eqrRectsFirma();
    $signer = new PdfSigner();
    $prep = $signer->preparar(eqrRutaActaAbs($tramite), eqrActaStatePath($tramite['codigo']), [
        'nombre' => $firma['nombre'], 'cedula' => $firma['cedula'],
        'rol' => $firma['rol'], 'rect' => $rects[(int)$firma['orden']]['rect'],
    ]);
    return ['bytesB64' => base64_encode($prep['bytesAFirmar'])];
}

/** CAs raiz configuradas (storage/ca/*.pem|*.crt), si el admin las cargo. */
function eqrCaRaices(): array
{
    $dir = __DIR__ . '/../../storage/ca';
    if (!is_dir($dir)) { return []; }
    return array_merge(glob("$dir/*.pem") ?: [], glob("$dir/*.crt") ?: []);
}

/**
 * Recibe la firma CMS del navegador, la inserta, VERIFICA (seccion 4.4) y,
 * si pasa, la acepta y avanza el circuito. Si es la ultima, aplica el cambio
 * de custodio en una transaccion y envia la alerta de cierre.
 *
 * @return array ['ok'=>bool,'mensaje'=>,'errores'=>[],'advertencias'=>[],'completo'=>bool]
 */
function eqrCompletarFirma(PDO $db, array $tramite, array $firma, string $firmaHex, string $actor): array
{
    $statePath = eqrActaStatePath($tramite['codigo']);
    $actaPath = eqrRutaActaAbs($tramite);
    $signer = new PdfSigner();
    $verifier = new CmsVerifier();

    $signed = $signer->completar($statePath, $firmaHex);
    $anterior = (string)file_get_contents($actaPath);
    $res = $verifier->verificarDocumento((string)file_get_contents($signed), $anterior,
                                         $firma['cedula'], eqrCaRaices());
    if (!$res['ok']) {
        @unlink($signed);
        $db->prepare("UPDATE eqr_firmas SET intentos_fallidos = intentos_fallidos + 1 WHERE id = ?")
           ->execute([(int)$firma['id']]);
        eqrFlujoBitacora($db, (int)$tramite['equipo_id'], (int)$tramite['id'], 'firma_rechazada_verificacion',
            ['orden' => $firma['orden'], 'errores' => $res['errores']], $actor);
        return ['ok' => false, 'mensaje' => 'La firma fue RECHAZADA por la verificación del servidor.',
                'errores' => $res['errores'], 'advertencias' => $res['advertencias'], 'completo' => false];
    }

    $signer->aceptar($statePath, $signed, $actaPath);
    $hash = hash_file('sha256', $actaPath);
    $cert = $res['cert'];

    $db->prepare("UPDATE eqr_firmas SET estado = 'firmado', fecha_firma = NOW(),
                    cert_cn = ?, cert_serial = ?, cert_emisor = ?, cert_desde = ?, cert_hasta = ?,
                    cadena_validada = ?, hash_resultante = ?, ip = ?, user_agent = ?,
                    token_hash = NULL, token_expira = NULL
                  WHERE id = ?")
       ->execute([
            $cert['cn'] ?? '', (string)($cert['serial'] ?? ''), $cert['emisor'] ?? '',
            !empty($cert['desde']) ? date('Y-m-d H:i:s', strtotime($cert['desde'])) : null,
            !empty($cert['hasta']) ? date('Y-m-d H:i:s', strtotime($cert['hasta'])) : null,
            eqrCaRaices() ? 1 : 0, $hash,
            $_SERVER['REMOTE_ADDR'] ?? null, substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            (int)$firma['id'],
        ]);
    $db->prepare("UPDATE eqr_tramites SET hash_actual = ?, estado = IF(estado='borrador','en_firma',estado) WHERE id = ?")
       ->execute([$hash, (int)$tramite['id']]);
    eqrFlujoBitacora($db, (int)$tramite['equipo_id'], (int)$tramite['id'], 'firma_aceptada',
        ['orden' => $firma['orden'], 'rol' => $firma['rol'], 'cn' => $cert['cn'] ?? ''], $actor);

    // ¿hay siguiente firmante?
    $stmt = $db->prepare("SELECT * FROM eqr_firmas WHERE tramite_id = ? AND estado = 'en_espera' ORDER BY orden LIMIT 1");
    $stmt->execute([(int)$tramite['id']]);
    $siguiente = $stmt->fetch();

    $advertencias = $res['advertencias'];
    if ($siguiente) {
        $db->prepare("UPDATE eqr_firmas SET estado = 'pendiente' WHERE id = ?")->execute([(int)$siguiente['id']]);
        $tk = eqrTokenNuevo($db, (int)$siguiente['id']);
        $notif = new Notificador();
        $r = $notif->notificarTurno($db, eqrTramitePorId($db, (int)$tramite['id']), $siguiente,
                                    eqrUrlFirma($tk['token']), $tk['expira']);
        if (!$r['ok']) {
            $advertencias[] = 'El correo al siguiente firmante (' . $siguiente['correo'] . ') no se pudo enviar: '
                . $r['error'] . ' Puede reenviar el enlace desde la bandeja de trámites.';
        }
        return ['ok' => true, 'mensaje' => 'Firma aceptada. El enlace pasó al siguiente firmante: ' . $siguiente['nombre'] . '.',
                'errores' => [], 'advertencias' => $advertencias, 'completo' => false];
    }

    // Ultima firma: cerrar el tramite y aplicar el cambio de custodio (transaccion)
    eqrCerrarTramiteCompleto($db, (int)$tramite['id'], $actor);
    $tramiteFinal = eqrTramitePorId($db, (int)$tramite['id']);
    $firmas = eqrFirmasDeTramite($db, (int)$tramite['id']);
    $notif = new Notificador();
    $r = $notif->notificarCierre($db, $tramiteFinal, $firmas, $actaPath);
    if (!$r['ok']) {
        $advertencias[] = 'El acta quedó completa, pero algún correo de cierre no se pudo enviar: ' . $r['error'];
    }
    return ['ok' => true, 'mensaje' => 'Acta completada con todas sus firmas. El cambio de custodio quedó aplicado.',
            'errores' => [], 'advertencias' => $advertencias, 'completo' => true];
}

/**
 * Transaccion de cierre: tramite completado + cambio de custodio + bitacora.
 * asignacion/traspaso: el equipo pasa al "recibe". devolucion: vuelve a stock.
 */
function eqrCerrarTramiteCompleto(PDO $db, int $tramiteId, string $actor): void
{
    $db->beginTransaction();
    try {
        $t = eqrTramitePorId($db, $tramiteId);
        $db->prepare("UPDATE eqr_tramites SET estado = 'completado', completado_en = NOW() WHERE id = ?")
           ->execute([$tramiteId]);
        if (in_array($t['tipo'], ['asignacion', 'traspaso'], true)) {
            $db->prepare("UPDATE eqr_equipos SET estado = 'asignado',
                            custodio_cedula = ?, custodio_nombre = ?, custodio_correo = ?,
                            custodio_cargo = ?, custodio_area = ? WHERE id = ?")
               ->execute([$t['recibe_cedula'], $t['recibe_nombre'], $t['recibe_correo'],
                          $t['recibe_cargo'], $t['recibe_area'], (int)$t['equipo_id']]);
            if ($t['recibe_cedula']) {
                $db->prepare("INSERT INTO eqr_personas (cedula, nombres, correo, cargo, area)
                              VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE nombres=VALUES(nombres),
                              correo=VALUES(correo), cargo=VALUES(cargo), area=VALUES(area)")
                   ->execute([$t['recibe_cedula'], $t['recibe_nombre'], $t['recibe_correo'],
                              $t['recibe_cargo'], $t['recibe_area']]);
            }
        } elseif ($t['tipo'] === 'devolucion') {
            $db->prepare("UPDATE eqr_equipos SET estado = 'en_stock', fuera_instalaciones = 0,
                            custodio_cedula = NULL, custodio_nombre = NULL, custodio_correo = NULL,
                            custodio_cargo = NULL, custodio_area = NULL WHERE id = ?")
               ->execute([(int)$t['equipo_id']]);
        }
        eqrFlujoBitacora($db, (int)$t['equipo_id'], $tramiteId, 'tramite_completado',
            ['codigo' => $t['codigo'], 'tipo' => $t['tipo']], $actor);
        $db->commit();
    } catch (Throwable $ex) {
        $db->rollBack();
        throw $ex;
    }
}

// =====================================================================
//  Rechazo, anulacion, reenvio, descarte de borrador
// =====================================================================

function eqrRechazarFirma(PDO $db, array $tramite, array $firma, string $motivo, string $actor): void
{
    $db->beginTransaction();
    try {
        $db->prepare("UPDATE eqr_firmas SET estado = 'rechazado', motivo_rechazo = ?,
                        token_hash = NULL, token_expira = NULL, fecha_firma = NOW() WHERE id = ?")
           ->execute([$motivo, (int)$firma['id']]);
        $db->prepare("UPDATE eqr_tramites SET estado = 'rechazado', motivo_rechazo_anulacion = ? WHERE id = ?")
           ->execute(['Rechazado por ' . $firma['nombre'] . ': ' . $motivo, (int)$tramite['id']]);
        eqrFlujoBitacora($db, (int)$tramite['equipo_id'], (int)$tramite['id'], 'tramite_rechazado',
            ['orden' => $firma['orden'], 'motivo' => $motivo], $actor);
        $db->commit();
    } catch (Throwable $ex) {
        $db->rollBack();
        throw $ex;
    }
    (new Notificador())->notificarRechazo($db, $tramite, $firma, $motivo);
}

function eqrAnularTramite(PDO $db, array $tramite, string $motivo, string $actor): void
{
    $db->prepare("UPDATE eqr_tramites SET estado = 'anulado', motivo_rechazo_anulacion = ? WHERE id = ?")
       ->execute([$motivo, (int)$tramite['id']]);
    $db->prepare("UPDATE eqr_firmas SET token_hash = NULL, token_expira = NULL
                  WHERE tramite_id = ? AND estado IN ('pendiente','en_espera')")
       ->execute([(int)$tramite['id']]);
    eqrFlujoBitacora($db, (int)$tramite['equipo_id'], (int)$tramite['id'], 'tramite_anulado',
        ['motivo' => $motivo], $actor);
}

/** Reenvia el enlace al firmante de turno (token nuevo; el anterior muere). */
function eqrReenviarEnlace(PDO $db, array $tramite, string $actor): array
{
    $firma = eqrFirmaPendiente($db, (int)$tramite['id']);
    if (!$firma) { return ['ok' => false, 'error' => 'No hay un firmante de turno en este trámite.']; }
    if ((int)$firma['orden'] === 1) {
        return ['ok' => false, 'error' => 'La primera firma es del administrador: fírmela desde el panel (Retomar).'];
    }
    $tk = eqrTokenNuevo($db, (int)$firma['id']);
    eqrFlujoBitacora($db, (int)$tramite['equipo_id'], (int)$tramite['id'], 'enlace_reenviado',
        ['orden' => $firma['orden'], 'correo' => $firma['correo']], $actor);
    $r = (new Notificador())->notificarTurno($db, $tramite, $firma, eqrUrlFirma($tk['token']), $tk['expira']);
    return $r['ok']
        ? ['ok' => true, 'error' => null, 'correo' => $firma['correo']]
        : ['ok' => false, 'error' => $r['error'], 'correo' => $firma['correo']];
}

/** Descarta un borrador (sin firmas): borra tramite, firmas y acta. */
function eqrDescartarBorrador(PDO $db, array $tramite, string $actor): void
{
    if ($tramite['estado'] !== 'borrador') {
        throw new RuntimeException('Solo se puede descartar un trámite en borrador.');
    }
    $db->beginTransaction();
    try {
        $db->prepare("DELETE FROM eqr_firmas WHERE tramite_id = ?")->execute([(int)$tramite['id']]);
        eqrFlujoBitacora($db, (int)$tramite['equipo_id'], (int)$tramite['id'], 'borrador_descartado',
            ['codigo' => $tramite['codigo']], $actor);
        $db->prepare("DELETE FROM eqr_tramites WHERE id = ?")->execute([(int)$tramite['id']]);
        $db->commit();
    } catch (Throwable $ex) {
        $db->rollBack();
        throw $ex;
    }
    $dir = eqrActaDir($tramite['codigo']);
    foreach (['acta.pdf', 'v0.pdf', 'acta.state.json', 'acta.pdf.prep'] as $f) { @unlink("$dir/$f"); }
    @rmdir($dir);
}
