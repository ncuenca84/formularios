<?php
/**
 * Firma del ADMINISTRADOR del contrato (paso 3 del asistente, o "retomar"
 * un borrador). Pagina con CSP estricta: nada de CDN, nada inline.
 * El .p12 y su clave se procesan unicamente en el navegador.
 */
require __DIR__ . '/eqr_common.php';          // sesion + CSRF (no imprime nada)
require_once __DIR__ . '/../includes/eqr/flujo.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$tramite = eqrTramitePorId($db, $id);
if (!$tramite || !in_array($tramite['estado'], ['borrador', 'en_firma'], true)) {
    header('Location: eqr_tramites.php');
    exit;
}

header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self'; " .
       "connect-src 'self'; frame-src 'self'; img-src 'self'; base-uri 'none'; form-action 'self'");
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

$tipos = ['asignacion' => 'Asignación', 'traspaso' => 'Traspaso', 'devolucion' => 'Devolución'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Firmar acta — <?= e($tramite['codigo']) ?></title>
<link rel="stylesheet" href="../assets/css/firma.css">
</head>
<body data-api="eqr_firma_api.php?id=<?= (int)$id ?>" data-csrf="<?= e(eqrCsrfToken()) ?>">
<div class="caja">
    <h1>Firma del administrador del contrato</h1>
    <p class="sub">Trámite <strong><?= e($tramite['codigo']) ?></strong> —
        <?= e($tipos[$tramite['tipo']] ?? $tramite['tipo']) ?> del equipo
        <strong><?= e($tramite['equipo_codigo']) ?></strong>
        (<?= e($tramite['equipo_marca']) ?> <?= e($tramite['equipo_modelo']) ?>,
        serie <?= e($tramite['equipo_serie']) ?>).<br>
        Debe firmar con el certificado de quien firma por la DTIC en este trámite:
        <strong><?= e($tramite['admin_contrato_nombre']) ?></strong>
        (C.C. <strong><?= e($tramite['admin_contrato_cedula']) ?></strong> —
        <?= e($tramite['admin_contrato_calidad']) ?>). Cualquier otro certificado será rechazado.
        Mientras no firme, el trámite queda en borrador y no se envía nada a nadie.</p>

    <div id="mensajes" class="alerta info" style="display:none"></div>
    <div id="detalle" class="detalle" style="display:none"></div>

    <h2>Circuito de firmas</h2>
    <div id="firmantes"></div>

    <div id="panelFirma" style="display:none">
        <h2>Firmar ahora</h2>
        <div class="campo">
            <label for="p12">Su archivo de firma electrónica (.p12 / .pfx)</label>
            <input type="file" id="p12" accept=".p12,.pfx">
        </div>
        <div class="campo">
            <label for="clave">Clave del archivo de firma</label>
            <input type="password" id="clave" autocomplete="off">
        </div>
        <button id="btnFirmar" type="button" class="primario">Firmar el acta</button>
        <p class="nota">Su archivo .p12 y su clave se procesan únicamente en este navegador;
            jamás se envían al servidor.</p>
    </div>

    <p>
        <a class="boton" href="eqr_tramite_ver.php?id=<?= (int)$id ?>">Volver al detalle del trámite</a>
        <a class="boton" href="eqr_tramites.php">Ir a la bandeja</a>
    </p>

    <h2>Acta</h2>
    <iframe id="marco" title="Acta en PDF" data-src="eqr_acta_ver.php?id=<?= (int)$id ?>&inline=1"></iframe>
</div>
<script src="../assets/js/firma.bundle.js"></script>
<script src="../assets/js/firma_ui.js"></script>
</body>
</html>
