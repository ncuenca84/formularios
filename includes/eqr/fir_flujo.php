<?php
/**
 * Firma en linea de los FORMULARIOS 1-6 (documentos fir_*).
 *
 * Mismo motor que las actas de equipos (PdfSigner + CmsVerifier + Notificador):
 *  - El PDF se genera UNA sola vez con los recuadros reservados y nunca se regenera.
 *  - Firma 1 = el solicitante, inmediatamente al enviar el formulario.
 *  - Los demas firman uno por uno, en orden, con enlace por correo (token).
 *  - Al completarse: el PDF final firmado llega a todos los firmantes y a la
 *    DTIC (email_admin). Sin verificacion en linea: se valida en FirmaEC.
 */

require_once __DIR__ . '/flujo.php';

const FIR_DOCS_DIR = __DIR__ . '/../../storage/documentos';

function firDocDir(string $codigo): string
{
    return FIR_DOCS_DIR . '/' . preg_replace('/[^A-Za-z0-9\-]/', '_', $codigo);
}
function firDocPath(string $codigo): string { return firDocDir($codigo) . '/doc.pdf'; }
function firDocStatePath(string $codigo): string { return firDocDir($codigo) . '/doc.state.json'; }

/** Pagina final del PDF con los recuadros de firma reservados (hasta 4). */
function firPaginaFirmasHtml(string $codigo, array $firmantes, string $colorPrimario = '#003366'): string
{
    $e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    $rects = eqrRectsFirma();
    $cajas = '';
    foreach ($firmantes as $f) {
        $n = (int)$f['orden'];
        if (!isset($rects[$n])) { continue; }
        $c = $rects[$n]['css'];
        $cajas .= sprintf(
            '<div style="position:absolute; left:%.2fpt; top:%.2fpt; width:%.2fpt; height:%.2fpt;
                 border:0.8pt solid #777;"></div>
             <div style="position:absolute; left:%.2fpt; top:%.2fpt; width:%.2fpt; text-align:center;
                 font-size:7.5pt; font-weight:bold; color:#333;">%s</div>
             <div style="position:absolute; left:%.2fpt; top:%.2fpt; width:%.2fpt; text-align:center;
                 font-size:8pt; color:#333;">%s<br>C.C. %s%s</div>',
            $c['left'], $c['top'], $c['w'], $c['h'],
            $c['left'], $c['top'] + $c['h'] + 4, $c['w'], $e(mb_strtoupper($f['etiqueta'] ?? $f['rol'])),
            $c['left'], $c['top'] + $c['h'] + 26, $c['w'],
            $e($f['nombre']), $e($f['cedula']),
            !empty($f['cargo']) ? '<br>' . $e($f['cargo']) : ''
        );
    }
    return '
<div style="page-break-before: always;">
    <div style="color:' . $colorPrimario . '; font-size:9pt; font-weight:bold;
         border-bottom:2px solid ' . $colorPrimario . '; padding-bottom:2px; margin:10px 0 5px;">
        FIRMAS ELECTRÓNICAS</div>
    <p style="font-size:8pt; color:#444; text-align:justify;">
        Solicitud Nro. <strong>' . $e($codigo) . '</strong>.
        Las firmas electrónicas de este documento se estampan por actualización incremental
        en los recuadros reservados a continuación, en el orden establecido, y se validan
        únicamente en FirmaEC.
    </p>
    ' . $cajas . '
</div>';
}

/**
 * Crea el documento: guarda el PDF (una sola vez), crea fir_documentos y
 * fir_firmas (firma 1 pendiente con token para firmar de inmediato).
 * @param array $firmantes [['rol','etiqueta','nombre','cedula','correo','cargo'], ...] EN ORDEN
 * @return array ['documento_id'=>int, 'token'=>string]
 */
function firCrearDocumento(PDO $db, string $codigo, int $formularioId, string $titulo,
                           ?int $solicitudId, array $firmantes, string $pdfBytes): array
{
    $dir = firDocDir($codigo);
    if (is_file(firDocPath($codigo))) {
        throw new RuntimeException('El documento ya existe y no se regenera.');
    }
    @mkdir($dir, 0775, true);

    $db->beginTransaction();
    try {
        $db->prepare("INSERT INTO fir_documentos (codigo, formulario_id, titulo, solicitud_id, estado, pdf_actual, hash_actual)
                      VALUES (?,?,?,?, 'borrador', ?, ?)")
           ->execute([$codigo, $formularioId, $titulo, $solicitudId,
                      'storage/documentos/' . basename($dir) . '/doc.pdf', hash('sha256', $pdfBytes)]);
        $docId = (int)$db->lastInsertId();

        $ins = $db->prepare("INSERT INTO fir_firmas (documento_id, orden, rol, etiqueta, cedula, nombre, correo, cargo, estado)
                             VALUES (?,?,?,?,?,?,?,?,?)");
        $orden = 0;
        foreach ($firmantes as $f) {
            $orden++;
            $ins->execute([$docId, $orden, $f['rol'], (string)($f['etiqueta'] ?? ''),
                           $f['cedula'], $f['nombre'], $f['correo'], (string)($f['cargo'] ?? ''),
                           $orden === 1 ? 'pendiente' : 'en_espera']);
        }

        file_put_contents(firDocPath($codigo), $pdfBytes);
        file_put_contents($dir . '/v0.pdf', $pdfBytes);

        eqrFlujoBitacora($db, null, null, 'fir_documento_creado',
            ['codigo' => $codigo, 'formulario' => $formularioId, 'documento_id' => $docId],
            'formulario' . $formularioId);

        // token de la firma 1 (el solicitante firma de inmediato)
        $stmt = $db->prepare("SELECT id FROM fir_firmas WHERE documento_id = ? AND orden = 1");
        $stmt->execute([$docId]);
        $firmaId = (int)$stmt->fetchColumn();
        $token = bin2hex(random_bytes(32));
        $dias = max(1, (int)getConfig('eqr_dias_token', '15'));
        $db->prepare("UPDATE fir_firmas SET token_hash = ?, token_expira = ? WHERE id = ?")
           ->execute([hash('sha256', $token), date('Y-m-d H:i:s', time() + $dias * 86400), $firmaId]);

        $db->commit();
        return ['documento_id' => $docId, 'token' => $token];
    } catch (Throwable $ex) {
        $db->rollBack();
        @unlink(firDocPath($codigo)); @unlink($dir . '/v0.pdf'); @rmdir($dir);
        throw $ex;
    }
}

function firDocumentoPorId(PDO $db, int $id): ?array
{
    $stmt = $db->prepare("SELECT * FROM fir_documentos WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function firFirmasDe(PDO $db, int $docId): array
{
    $stmt = $db->prepare("SELECT * FROM fir_firmas WHERE documento_id = ? ORDER BY orden");
    $stmt->execute([$docId]);
    return $stmt->fetchAll();
}

function firFirmaPendiente(PDO $db, int $docId): ?array
{
    $stmt = $db->prepare("SELECT * FROM fir_firmas WHERE documento_id = ? AND estado = 'pendiente'
                          ORDER BY orden LIMIT 1");
    $stmt->execute([$docId]);
    return $stmt->fetch() ?: null;
}

function firTokenNuevo(PDO $db, int $firmaId): array
{
    $token = bin2hex(random_bytes(32));
    $dias = max(1, (int)getConfig('eqr_dias_token', '15'));
    $expira = date('Y-m-d H:i:s', time() + $dias * 86400);
    $db->prepare("UPDATE fir_firmas SET token_hash = ?, token_expira = ?, intentos_fallidos = 0 WHERE id = ?")
       ->execute([hash('sha256', $token), $expira, $firmaId]);
    return ['token' => $token, 'expira' => $expira];
}

function firFirmaPorToken(PDO $db, string $token): ?array
{
    if (!preg_match('/^[0-9a-f]{64}$/', $token)) { return null; }
    $stmt = $db->prepare("SELECT * FROM fir_firmas WHERE token_hash = ? LIMIT 1");
    $stmt->execute([hash('sha256', $token)]);
    $firma = $stmt->fetch();
    if (!$firma) { return null; }
    $doc = firDocumentoPorId($db, (int)$firma['documento_id']);
    if (!$doc) { return null; }
    return ['firma' => $firma, 'documento' => $doc];
}

/** Misma logica de turno/vencimiento que las actas de equipos. */
function firTokenProblema(array $firma, array $doc): ?string
{
    if ($doc['estado'] === 'anulado') { return 'El documento fue anulado.'; }
    if ($doc['estado'] === 'rechazado') { return 'El documento fue rechazado y ya no admite firmas.'; }
    if ($doc['estado'] === 'completado') { return 'El documento ya se completó: tiene todas sus firmas.'; }
    if ($firma['estado'] === 'firmado') { return 'Usted ya firmó este documento. Gracias.'; }
    if ($firma['estado'] === 'rechazado') { return 'Usted rechazó este documento.'; }
    if ($firma['estado'] !== 'pendiente') { return 'Todavía no es su turno de firma. Recibirá un aviso por correo cuando lo sea.'; }
    if ($firma['token_expira'] !== null && strtotime($firma['token_expira']) < time()) {
        return 'El enlace de firma venció. Solicite a la DTIC que se lo reenvíe.';
    }
    if ((int)$firma['intentos_fallidos'] >= EQR_MAX_INTENTOS_FIRMA) {
        return 'Se superó el número de intentos permitidos. Solicite a la DTIC un nuevo enlace.';
    }
    return null;
}

function firPrepararFirma(array $doc, array $firma): array
{
    $rects = eqrRectsFirma();
    $signer = new PdfSigner();
    $prep = $signer->preparar(firDocPath($doc['codigo']), firDocStatePath($doc['codigo']), [
        'nombre' => $firma['nombre'], 'cedula' => $firma['cedula'],
        'rol' => $firma['rol'], 'rect' => $rects[(int)$firma['orden']]['rect'],
        'qrMatrix' => eqrQrSelloFirma($firma),
    ]);
    return ['bytesB64' => base64_encode($prep['bytesAFirmar'])];
}

function firCompletarFirma(PDO $db, array $doc, array $firma, string $firmaHex, string $actor): array
{
    $statePath = firDocStatePath($doc['codigo']);
    $pdfPath = firDocPath($doc['codigo']);
    $signer = new PdfSigner();
    $verifier = new CmsVerifier();

    $signed = $signer->completar($statePath, $firmaHex);
    $anterior = (string)file_get_contents($pdfPath);
    $res = $verifier->verificarDocumento((string)file_get_contents($signed), $anterior, $firma['cedula']);
    if (!$res['ok']) {
        @unlink($signed);
        $db->prepare("UPDATE fir_firmas SET intentos_fallidos = intentos_fallidos + 1 WHERE id = ?")
           ->execute([(int)$firma['id']]);
        eqrFlujoBitacora($db, null, null, 'fir_firma_rechazada_verificacion',
            ['codigo' => $doc['codigo'], 'orden' => $firma['orden'], 'errores' => $res['errores']], $actor);
        return ['ok' => false, 'mensaje' => 'La firma fue RECHAZADA por la verificación del servidor.',
                'errores' => $res['errores'], 'advertencias' => $res['advertencias'], 'completo' => false];
    }

    $signer->aceptar($statePath, $signed, $pdfPath);
    $hash = hash_file('sha256', $pdfPath);
    $cert = $res['cert'];

    $db->prepare("UPDATE fir_firmas SET estado = 'firmado', fecha_firma = NOW(),
                    cert_cn = ?, cert_serial = ?, cert_emisor = ?, cert_desde = ?, cert_hasta = ?,
                    hash_resultante = ?, ip = ?, user_agent = ?
                  WHERE id = ?")
       ->execute([
            $cert['cn'] ?? '', (string)($cert['serial'] ?? ''), $cert['emisor'] ?? '',
            !empty($cert['desde']) ? date('Y-m-d H:i:s', strtotime($cert['desde'])) : null,
            !empty($cert['hasta']) ? date('Y-m-d H:i:s', strtotime($cert['hasta'])) : null,
            $hash, $_SERVER['REMOTE_ADDR'] ?? null,
            substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255), (int)$firma['id'],
        ]);
    $db->prepare("UPDATE fir_documentos SET hash_actual = ?, estado = IF(estado='borrador','en_firma',estado) WHERE id = ?")
       ->execute([$hash, (int)$doc['id']]);
    eqrFlujoBitacora($db, null, null, 'fir_firma_aceptada',
        ['codigo' => $doc['codigo'], 'orden' => $firma['orden'], 'cn' => $cert['cn'] ?? ''], $actor);

    $stmt = $db->prepare("SELECT * FROM fir_firmas WHERE documento_id = ? AND estado = 'en_espera' ORDER BY orden LIMIT 1");
    $stmt->execute([(int)$doc['id']]);
    $siguiente = $stmt->fetch();
    $advertencias = $res['advertencias'];
    $notif = new Notificador();

    if ($siguiente) {
        $db->prepare("UPDATE fir_firmas SET estado = 'pendiente' WHERE id = ?")->execute([(int)$siguiente['id']]);
        $tk = firTokenNuevo($db, (int)$siguiente['id']);
        $r = $notif->notificarTurnoDoc($db, firDocumentoPorId($db, (int)$doc['id']), $siguiente,
                                       eqrUrlFirma($tk['token']), $tk['expira']);
        if (!$r['ok']) {
            $advertencias[] = 'El correo al siguiente firmante (' . $siguiente['correo'] . ') no se pudo enviar: '
                . $r['error'] . ' La DTIC puede reenviar el enlace desde el panel.';
        }
        return ['ok' => true, 'mensaje' => 'Firma aceptada. El turno pasó a: ' . $siguiente['nombre']
                . ' (' . ($siguiente['etiqueta'] ?: $siguiente['rol']) . ').',
                'errores' => [], 'advertencias' => $advertencias, 'completo' => false];
    }

    // ultima firma: documento completo
    $db->prepare("UPDATE fir_documentos SET estado = 'completado', completado_en = NOW() WHERE id = ?")
       ->execute([(int)$doc['id']]);
    eqrFlujoBitacora($db, null, null, 'fir_documento_completado', ['codigo' => $doc['codigo']], $actor);
    $docFinal = firDocumentoPorId($db, (int)$doc['id']);
    $r = $notif->notificarCierreDoc($db, $docFinal, firFirmasDe($db, (int)$doc['id']), $pdfPath);
    if (!$r['ok']) {
        $advertencias[] = 'El documento quedó completo, pero algún correo de entrega no se pudo enviar: ' . $r['error'];
    }
    return ['ok' => true,
        'mensaje' => 'Documento completado con todas sus firmas. Se envió por correo a todos los firmantes y a la DTIC.',
        'errores' => [], 'advertencias' => $advertencias, 'completo' => true];
}

function firRechazarFirma(PDO $db, array $doc, array $firma, string $motivo, string $actor): void
{
    $db->beginTransaction();
    try {
        $db->prepare("UPDATE fir_firmas SET estado = 'rechazado', motivo_rechazo = ?, fecha_firma = NOW() WHERE id = ?")
           ->execute([$motivo, (int)$firma['id']]);
        $db->prepare("UPDATE fir_documentos SET estado = 'rechazado', motivo_rechazo_anulacion = ? WHERE id = ?")
           ->execute(['Rechazado por ' . $firma['nombre'] . ': ' . $motivo, (int)$doc['id']]);
        eqrFlujoBitacora($db, null, null, 'fir_documento_rechazado',
            ['codigo' => $doc['codigo'], 'orden' => $firma['orden'], 'motivo' => $motivo], $actor);
        $db->commit();
    } catch (Throwable $ex) {
        $db->rollBack();
        throw $ex;
    }
    (new Notificador())->notificarRechazoDoc($db, $doc, $firma, $motivo, firFirmasDe($db, (int)$doc['id']));
}

function firAnularDocumento(PDO $db, array $doc, string $motivo, string $actor): void
{
    $db->prepare("UPDATE fir_documentos SET estado = 'anulado', motivo_rechazo_anulacion = ? WHERE id = ?")
       ->execute([$motivo, (int)$doc['id']]);
    $db->prepare("UPDATE fir_firmas SET token_hash = NULL, token_expira = NULL
                  WHERE documento_id = ? AND estado IN ('pendiente','en_espera')")
       ->execute([(int)$doc['id']]);
    eqrFlujoBitacora($db, null, null, 'fir_documento_anulado',
        ['codigo' => $doc['codigo'], 'motivo' => $motivo], $actor);
}

function firReenviarEnlace(PDO $db, array $doc, string $actor): array
{
    $firma = firFirmaPendiente($db, (int)$doc['id']);
    if (!$firma) { return ['ok' => false, 'error' => 'No hay un firmante de turno en este documento.']; }
    $tk = firTokenNuevo($db, (int)$firma['id']);
    eqrFlujoBitacora($db, null, null, 'fir_enlace_reenviado',
        ['codigo' => $doc['codigo'], 'orden' => $firma['orden'], 'correo' => $firma['correo']], $actor);
    $r = (new Notificador())->notificarTurnoDoc($db, $doc, $firma, eqrUrlFirma($tk['token']), $tk['expira']);
    return $r['ok'] ? ['ok' => true, 'error' => null, 'correo' => $firma['correo']]
                    : ['ok' => false, 'error' => $r['error'], 'correo' => $firma['correo']];
}

/**
 * Circuito de firmantes EN ORDEN para la firma en linea (formularios 1-6).
 * Devuelve [firmantes[], errores[]]. Cada firmante: rol, etiqueta, nombre,
 * cedula, correo, cargo. El primero firma al enviar; el resto por correo.
 */
function firFirmantesPorTipo(int $tipo, array $d, array $config, string $dominio): array
{
    $errores = [];
    $firmantes = [];
    $limpiaCed = function ($v) {
        $c = preg_replace('/\D/', '', (string)$v);
        if (strlen($c) === 9 && eqrValidarCedula('0' . $c)) { $c = '0' . $c; }
        return $c;
    };
    $agrega = function (string $rol, string $etiqueta, $nombre, $cedula, $correo, $cargo, bool $interno)
        use (&$firmantes, &$errores, $limpiaCed, $dominio) {
        $nombre = trim((string)$nombre);
        $cedula = $limpiaCed($cedula);
        $correo = trim((string)$correo);
        if ($nombre === '') { $errores[] = "Falta el nombre de: $etiqueta."; }
        if (!eqrValidarCedula($cedula)) {
            $errores[] = "La cedula de \"$etiqueta\" no es una cedula ecuatoriana valida (su firma electronica se valida contra ella).";
        }
        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "Falta un correo valido para: $etiqueta (ahi le llega su enlace de firma).";
        } elseif ($interno && !str_ends_with(strtolower($correo), '@' . strtolower($dominio))) {
            $errores[] = "El correo de \"$etiqueta\" debe ser institucional (@$dominio).";
        }
        $firmantes[] = [
            'rol' => $rol, 'etiqueta' => $etiqueta, 'nombre' => $nombre,
            'cedula' => $cedula, 'correo' => $correo, 'cargo' => trim((string)$cargo),
        ];
    };

    switch ($tipo) {
        case 1:
            $agrega('receptor', 'Por el Receptor', $d['nombre_receptor'] ?? '', $d['cedula_receptor'] ?? '',
                $d['correo'] ?? '', $d['cargo_receptor'] ?? '', false);
            $agrega('osi', 'Por la ARCONEL — Oficial de Seguridad de la Informacion',
                $config['osi_nombre'] ?? '', $config['osi_cedula'] ?? '', $config['osi_correo'] ?? '',
                'Oficial de Seguridad de la Informacion', true);
            if (empty($config['osi_correo'])) {
                $errores[] = 'Falta configurar el correo del OSI en el panel de administracion (pestaña Autoridades). Avise a la DTIC.';
            }
            break;

        case 2:
            $agrega('solicitante', 'Solicitante',
                trim(($d['solicitante_nombres'] ?? '') . ' ' . ($d['solicitante_apellidos'] ?? '')),
                $d['solicitante_nro_doc'] ?? '', $d['correo'] ?? '', $d['solicitante_cargo'] ?? '', false);
            $agrega('entidad_externa', 'Director/Jefe de la entidad solicitante',
                $d['firma_entidad_nombre'] ?? '', $d['firma_entidad_cedula'] ?? '',
                $d['firma_entidad_correo'] ?? '', $d['firma_entidad_cargo'] ?? '', false);
            $agrega('area_arconel', 'Director/Coordinador del area ARCONEL',
                $d['firma_area_nombre'] ?? '', $d['firma_area_cedula'] ?? '',
                $d['firma_area_correo'] ?? '', $d['firma_area_cargo'] ?? '', true);
            $agrega('dtic', 'Director/a de Tecnologias de la Informacion',
                $d['firma_dtic_nombre'] ?? ($config['dtic_nombre'] ?? ''),
                $d['firma_dtic_cedula'] ?? ($config['dtic_cedula'] ?? ''),
                $config['dtic_correo'] ?? '',
                $d['firma_dtic_cargo'] ?? ($config['dtic_cargo'] ?? ''), true);
            if (empty($config['dtic_correo'])) {
                $errores[] = 'Falta configurar el correo de la Direccion TIC en el panel de administracion (pestaña Autoridades). Avise a la DTIC.';
            }
            break;

        case 3:
            $nombre = trim(($d['nombre1'] ?? '') . ' ' . ($d['nombre2'] ?? '') . ' '
                . ($d['apellido1'] ?? '') . ' ' . ($d['apellido2'] ?? ''));
            $correoUsuario = trim((string)($d['correo_usuario'] ?? ''));
            if ($correoUsuario !== '' && strpos($correoUsuario, '@') === false) {
                $correoUsuario .= '@' . $dominio;
            }
            $agrega('solicitante', 'Solicitante', $nombre, $d['cedula'] ?? '', $correoUsuario,
                $d['cargo'] ?? '', true);
            $agrega('autorizador', 'Director/Coordinador/Jefe de Area (autoriza)',
                $d['autorizador_nombre'] ?? '', $d['autorizador_cedula'] ?? '',
                $d['autorizador_correo'] ?? '', $d['autorizador_cargo'] ?? '', true);
            break;

        case 4:
            $esInterno = str_starts_with((string)($d['tipo_usuario'] ?? ''), 'Personal de la ARCONEL');
            $agrega('solicitante', 'Solicitante', $d['nombre_completo'] ?? '', $d['cedula'] ?? '',
                $d['correo'] ?? '', '', $esInterno);
            // El jefe inmediato de la institucion del solicitante firma siempre.
            // Si el solicitante es de la ARCONEL, su jefe tambien (correo institucional).
            $agrega('institucion', 'Director/Jefe inmediato (autoriza)',
                $d['firma_inst_nombre'] ?? '', $d['firma_inst_cedula'] ?? '',
                $d['firma_inst_correo'] ?? '', $d['firma_inst_cargo'] ?? '', $esInterno);
            // El responsable del area ARCONEL es opcional (solo externos): si se
            // lo indica, tambien firma; si queda en blanco, no entra al circuito.
            if (!$esInterno && trim((string)($d['firma_area_nombre'] ?? '')) !== '') {
                $agrega('area_arconel', 'Director/Jefe del area ARCONEL responsable',
                    $d['firma_area_nombre'] ?? '', $d['firma_area_cedula'] ?? '',
                    $d['firma_area_correo'] ?? '', $d['firma_area_cargo'] ?? '', true);
            }
            $agrega('dtic', 'Director/a de Tecnologias de la Informacion',
                $d['firma_dtic_nombre'] ?? ($config['dtic_nombre'] ?? ''),
                $d['firma_dtic_cedula'] ?? ($config['dtic_cedula'] ?? ''),
                $config['dtic_correo'] ?? '',
                $d['firma_dtic_cargo'] ?? ($config['dtic_cargo'] ?? ''), true);
            if (empty($config['dtic_correo'])) {
                $errores[] = 'Falta configurar el correo de la Direccion TIC en el panel de administracion (pestaña Autoridades). Avise a la DTIC.';
            }
            break;

        case 5:
        case 6:
        case 8:
            $agrega('solicitante', 'Solicitante (Servidor)', $d['nombre_completo'] ?? '',
                $d['cedula'] ?? '', $d['correo'] ?? '', '', true);
            $agrega('autorizador', 'Director/Coordinador/Jefe de Area (autoriza)',
                $d['autorizador_nombre'] ?? '', $d['autorizador_cedula'] ?? '',
                $d['autorizador_correo'] ?? '', $d['autorizador_cargo'] ?? '', true);
            break;
    }

    return [$firmantes, $errores];
}
