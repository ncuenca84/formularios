<?php
/**
 * Pagina PUBLICA de firma: firmar.php?t=TOKEN
 * El firmante de turno recibe este enlace por correo. CSP estricta, sin CDN.
 * El archivo .p12 y su clave se procesan unicamente en el navegador.
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/eqr/flujo.php';

header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self'; " .
       "connect-src 'self'; frame-src 'self'; img-src 'self'; base-uri 'none'; form-action 'self'");
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

$e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$db = getDB();

/** Limite de intentos con tokens invalidos por IP (15 minutos). */
function eqrIpBloqueada(PDO $db): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($ip === '') { return false; }
    $stmt = $db->prepare("SELECT COUNT(*) FROM eqr_bitacora
                          WHERE accion = 'token_invalido' AND ip = ? AND fecha > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmt->execute([$ip]);
    return (int)$stmt->fetchColumn() >= 20;
}

$token = (string)($_GET['t'] ?? '');
$problema = null;
$ctx = null;

if (eqrIpBloqueada($db)) {
    http_response_code(429);
    $problema = 'Demasiados intentos desde su conexión. Espere unos minutos e intente de nuevo.';
} else {
    $ctx = eqrFirmaPorToken($db, $token);
    if (!$ctx) {
        eqrFlujoBitacora($db, null, null, 'token_invalido', [], 'publico');
        $problema = 'El enlace de firma no es válido o ya no está activo. Si cree que es un error, '
            . 'solicite al administrador del contrato que le reenvíe el enlace.';
    } else {
        $problema = eqrTokenProblema($ctx['firma'], $ctx['tramite']);
    }
}

$tipos = ['asignacion' => 'Asignación', 'traspaso' => 'Traspaso', 'devolucion' => 'Devolución',
          'salida' => 'Salida de las instalaciones'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Firma electrónica de acta — ARCONEL</title>
<link rel="stylesheet" href="assets/css/firma.css">
</head>
<?php if ($ctx === null): // token invalido o IP bloqueada: no hay nada que mostrar ?>
<body>
<div class="caja">
    <h1>Firma electrónica de actas</h1>
    <div class="alerta error"><?= $e($problema) ?></div>
</div>
</body>
</html>
<?php exit; endif; ?>
<?php $tramite = $ctx['tramite']; $firma = $ctx['firma']; ?>
<body data-api="firmar_api.php?t=<?= $e($token) ?>">
<div class="caja">
    <h1>Firma electrónica de acta — <?= $e($tipos[$tramite['tipo']] ?? $tramite['tipo']) ?></h1>
    <p class="sub">Trámite <strong><?= $e($tramite['codigo']) ?></strong>, creado por
        <?= $e($tramite['admin_contrato_nombre']) ?> (Administrador del contrato).<br>
        Equipo: <strong><?= $e($tramite['equipo_codigo']) ?></strong> —
        <?= $e($tramite['equipo_marca']) ?> <?= $e($tramite['equipo_modelo']) ?>,
        serie <?= $e($tramite['equipo_serie']) ?>.<br>
        Firma a su cargo: <strong><?= $e($firma['nombre']) ?></strong> (C.C. <?= $e($firma['cedula']) ?>).
        Revise el acta completa más abajo antes de firmar.</p>

    <div id="mensajes" class="alerta info" style="display:none"></div>
    <div id="detalle" class="detalle" style="display:none"></div>

    <h2>Estado de las firmas</h2>
    <div id="firmantes"></div>

    <div id="panelFirma" style="display:none">
        <h2>Firmar el acta</h2>
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
            jamás se envían al servidor. La cédula de su certificado debe ser
            <strong><?= $e($firma['cedula']) ?></strong>.</p>
    </div>

    <div id="panelRechazo" style="display:none">
        <h2>¿No está de acuerdo?</h2>
        <p class="nota">Si no corresponde firmar esta acta, puede rechazar el trámite indicando el motivo.
            Se avisará al administrador del contrato y el custodio del equipo no cambiará.</p>
        <div class="campo">
            <label for="motivoRechazo">Motivo del rechazo</label>
            <input type="text" id="motivoRechazo" maxlength="500" autocomplete="off">
        </div>
        <button id="btnRechazar" type="button">Rechazar el trámite</button>
    </div>

    <h2>Acta</h2>
    <iframe id="marco" title="Acta en PDF" data-src="firmar_acta.php?t=<?= $e($token) ?>"></iframe>
</div>
<script src="assets/js/firma.bundle.js"></script>
<script src="assets/js/firma_ui.js"></script>
</body>
</html>
