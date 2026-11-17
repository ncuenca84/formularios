<?php
/**
 * Crea la autorizacion de salida (formulario 7): valida todo en el servidor,
 * crea el tramite 'salida' con su acta, lo registra en `solicitudes` y lleva
 * al custodio DIRECTAMENTE a firmar (firmar.php?t=TOKEN).
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/eqr/publico.php';
require_once __DIR__ . '/includes/layout.php';

$db = getDB();
$errores = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: forms/form7.php'); exit; }
if (!eqrPubCsrfOk($_POST['csrf'] ?? null)) {
    $errores[] = 'Su sesión caducó. Vuelva al formulario y recargue la página.';
}
if (!$errores && eqrPubIpBloqueada($db, 'salida_verificacion_fallida')) {
    $errores[] = 'Demasiados intentos desde su conexión. Espere unos minutos e intente de nuevo.';
}

$equipo = null;
$contrato = null;
if (!$errores) {
    $cedula = preg_replace('/\D/', '', (string)($_POST['cedula'] ?? ''));
    $codigo = strtoupper(trim((string)($_POST['codigo'] ?? '')));
    $equipo = eqrEquipoDeCustodio($db, $cedula, $codigo);
    if (!$equipo) {
        eqrPubIntentoFallido($db, 'salida_verificacion_fallida', ['codigo' => substr($codigo, 0, 20)]);
        $errores[] = 'Los datos no coinciden con un equipo a su cargo.';
    } elseif (($problema = eqrSalidaProblema($db, $equipo)) !== null) {
        $errores[] = $problema;
    } elseif (trim((string)$equipo['custodio_correo']) === '') {
        $errores[] = 'Su equipo no tiene registrado un correo institucional de custodio. Pida a la DTIC (soporte@arconel.gob.ec) completarlo antes de tramitar la salida.';
    }
}

$datos = [];
if (!$errores) {
    $limpia = fn($k) => trim((string)($_POST[$k] ?? ''));
    $dominio = getConfig('eqr_dominio_correo', 'arconel.gob.ec');

    $motivo = $limpia('salida_motivo');
    $motivoOtro = $limpia('motivo_otro');
    if ($motivo === '') { $errores[] = 'Seleccione el motivo de la salida.'; }
    if ($motivo === 'Otro') {
        if ($motivoOtro === '') { $errores[] = 'Escriba el detalle del motivo "Otro".'; }
        else { $motivo = 'Otro: ' . $motivoOtro; }
    }

    $fechaSalida = $limpia('salida_fecha');
    if ($fechaSalida === '' || strtotime($fechaSalida) === false) {
        $errores[] = 'Indique la fecha de salida.';
    }
    $fechaRetorno = $limpia('salida_fecha_retorno');
    if ($fechaRetorno !== '' && (strtotime($fechaRetorno) === false
        || strtotime($fechaRetorno) < strtotime($fechaSalida))) {
        $errores[] = 'La fecha estimada de retorno no puede ser anterior a la fecha de salida.';
    }

    $jefeCedula = preg_replace('/\D/', '', $limpia('jefe_cedula'));
    if (strlen($jefeCedula) === 9 && eqrValidarCedula('0' . $jefeCedula)) { $jefeCedula = '0' . $jefeCedula; }
    $jefeNombre = $limpia('jefe_nombre');
    $jefeCorreo = $limpia('jefe_correo');
    $jefeCargo = $limpia('jefe_cargo');
    if (!eqrValidarCedula($jefeCedula)) { $errores[] = 'La cédula del jefe inmediato no es válida (su firma se valida contra ella).'; }
    if ($jefeNombre === '') { $errores[] = 'Escriba el nombre de su jefe inmediato.'; }
    if ($jefeCorreo === '' || !filter_var($jefeCorreo, FILTER_VALIDATE_EMAIL)
        || !str_ends_with(strtolower($jefeCorreo), '@' . strtolower($dominio))) {
        $errores[] = "El correo del jefe inmediato debe ser institucional (@$dominio): ahí le llega su enlace de firma.";
    }
    if (!$errores && $jefeCedula === $equipo['custodio_cedula']) {
        $errores[] = 'El jefe inmediato no puede ser la misma persona que el custodio.';
    }

    $datos = [
        'entrega_cedula' => $equipo['custodio_cedula'],
        'entrega_nombre' => $equipo['custodio_nombre'],
        'entrega_correo' => $equipo['custodio_correo'],
        'entrega_cargo' => $equipo['custodio_cargo'],
        'entrega_area' => $equipo['custodio_area'],
        'jefe_cedula' => $jefeCedula, 'jefe_nombre' => $jefeNombre,
        'jefe_correo' => $jefeCorreo, 'jefe_cargo' => $jefeCargo,
        'salida_motivo' => $motivo,
        'salida_fecha' => $fechaSalida,
        'salida_fecha_retorno' => $fechaRetorno !== '' ? $fechaRetorno : null,
        'salida_lugar' => $limpia('salida_lugar'),
        'accesorios' => $limpia('accesorios'),
        'observaciones' => $limpia('observaciones'),
    ];

    $stmt = $db->prepare("SELECT * FROM eqr_contratos WHERE id = ?");
    $stmt->execute([(int)$equipo['contrato_id']]);
    $contrato = $stmt->fetch();
    if (!$contrato) { $errores[] = 'No se encontró el contrato del equipo. Contacte a la DTIC.'; }
}

if (!$errores) {
    try {
        // registro en `solicitudes` (historial general del sistema)
        $codigoSol = generarCodigoSolicitud();
        $db->prepare("INSERT INTO solicitudes (codigo, nombre_completo, cedula, correo, cargo, area, tipo_formulario)
                      VALUES (?,?,?,?,?,?,?)")
           ->execute([$codigoSol, $equipo['custodio_nombre'], $equipo['custodio_cedula'],
                      $equipo['custodio_correo'], (string)$equipo['custodio_cargo'],
                      (string)$equipo['custodio_area'], 'Autorización de Salida de Equipo Rentado']);
        $solicitudId = (int)$db->lastInsertId();

        $r = eqrCrearSalida($db, $equipo, $contrato, $datos, $solicitudId);
        header('Location: /firmar.php?t=' . $r['token']);
        exit;
    } catch (Throwable $ex) {
        $errores[] = 'No se pudo crear el trámite: ' . $ex->getMessage();
    }
}

// ------------------------------------------------ pagina de errores
$config = getAllConfig();
renderHeadHtml('Salida de equipo', $config['color_primario'] ?? '#003366');
renderHeader($config, 'Autorización de Salida de Equipo');
?>
<div class="container mb-5">
    <div class="row justify-content-center"><div class="col-lg-8">
        <div class="alert alert-danger">
            <h6 class="fw-bold"><i class="bi bi-exclamation-triangle"></i> No se pudo crear la autorización</h6>
            <ul class="mb-0">
                <?php foreach ($errores as $er): ?>
                    <li><?= htmlspecialchars($er, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <a href="javascript:history.back()" class="btn btn-primary"><i class="bi bi-arrow-left"></i>
            Volver al formulario (sus datos se conservan)</a>
    </div></div>
</div>
<?php renderFooterHtml($config['nombre_institucion'] ?? 'ARCONEL');
