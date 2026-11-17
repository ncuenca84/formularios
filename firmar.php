<?php
/**
 * Pagina PUBLICA de firma: firmar.php?t=TOKEN
 * Sirve para DOS tipos de documento con el mismo circuito:
 *   - actas de equipos rentados (eqr_*)
 *   - formularios institucionales 1-6 firmados en linea (fir_*)
 * CSP estricta, sin CDN. El .p12 y su clave se procesan solo en el navegador.
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/eqr/flujo.php';
require_once __DIR__ . '/includes/eqr/fir_flujo.php';

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
$vista = null;

if (eqrIpBloqueada($db)) {
    http_response_code(429);
    $problema = 'Demasiados intentos desde su conexión. Espere unos minutos e intente de nuevo.';
} else {
    $tipos = ['asignacion' => 'Asignación', 'traspaso' => 'Traspaso', 'devolucion' => 'Devolución',
              'reposicion' => 'Reposición', 'salida' => 'Salida de las instalaciones'];
    if (($ctx = eqrFirmaPorToken($db, $token)) !== null) {
        $t = $ctx['tramite'];
        $vista = [
            'firma' => $ctx['firma'],
            'titulo' => 'Firma electrónica de acta — ' . ($tipos[$t['tipo']] ?? $t['tipo']),
            'sub' => 'Trámite <strong>' . $e($t['codigo']) . '</strong>, creado por '
                . $e($t['admin_contrato_nombre']) . '.<br>Equipo: <strong>' . $e($t['equipo_codigo'])
                . '</strong> — ' . $e($t['equipo_marca']) . ' ' . $e($t['equipo_modelo'])
                . ', serie ' . $e($t['equipo_serie']) . '.',
            'problema' => eqrTokenProblema($ctx['firma'], $t),
        ];
    } elseif (($ctx = firFirmaPorToken($db, $token)) !== null) {
        $d = $ctx['documento'];
        $vista = [
            'firma' => $ctx['firma'],
            'titulo' => 'Firma electrónica — ' . $d['titulo'],
            'sub' => 'Solicitud <strong>' . $e($d['codigo']) . '</strong>. Usted firma en calidad de <strong>'
                . $e($ctx['firma']['etiqueta'] ?: $ctx['firma']['rol']) . '</strong>.',
            'problema' => firTokenProblema($ctx['firma'], $d),
        ];
    } else {
        eqrFlujoBitacora($db, null, null, 'token_invalido', [], 'publico');
        $problema = 'El enlace de firma no es válido o ya no está activo. Si cree que es un error, '
            . 'solicite que le reenvíen el enlace.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Firma electrónica — ARCONEL</title>
<link rel="stylesheet" href="assets/css/firma.css">
</head>
<?php if ($vista === null): // token invalido o IP bloqueada ?>
<body>
<div class="caja">
    <h1>Firma electrónica de documentos</h1>
    <div class="alerta error"><?= $e($problema) ?></div>
</div>
</body>
</html>
<?php exit; endif; ?>
<?php $firma = $vista['firma']; ?>
<body data-api="firmar_api.php?t=<?= $e($token) ?>">
<div class="caja">
    <h1><?= $e($vista['titulo']) ?></h1>
    <p class="sub"><?= $vista['sub'] ?><br>
        Firma a su cargo: <strong><?= $e($firma['nombre']) ?></strong> (C.C. <?= $e($firma['cedula']) ?>).
        Revise el documento completo más abajo antes de firmar.</p>

    <div id="mensajes" class="alerta info" style="display:none"></div>
    <div id="detalle" class="detalle" style="display:none"></div>

    <h2>Estado de las firmas</h2>
    <div id="firmantes"></div>

    <div id="panelFirma" style="display:none">
        <h2>Firmar el documento</h2>
        <div class="campo">
            <label for="p12">Su archivo de firma electrónica (.p12 / .pfx)</label>
            <input type="file" id="p12" accept=".p12,.pfx">
        </div>
        <div class="campo">
            <label for="clave">Clave del archivo de firma</label>
            <input type="password" id="clave" autocomplete="off">
        </div>
        <button id="btnFirmar" type="button" class="primario">Firmar</button>
        <p class="nota">Su archivo .p12 y su clave se procesan únicamente en este navegador;
            jamás se envían al servidor. La cédula de su certificado debe ser
            <strong><?= $e($firma['cedula']) ?></strong>.</p>
    </div>

    <div id="panelRechazo" style="display:none">
        <h2>¿No está de acuerdo?</h2>
        <p class="nota">Si no corresponde firmar, puede rechazar indicando el motivo.
            El trámite se detendrá y se avisará a los responsables.</p>
        <div class="campo">
            <label for="motivoRechazo">Motivo del rechazo</label>
            <input type="text" id="motivoRechazo" maxlength="500" autocomplete="off">
        </div>
        <button id="btnRechazar" type="button">Rechazar</button>
    </div>

    <h2>Documento</h2>
    <iframe id="marco" title="Documento en PDF" data-src="firmar_acta.php?t=<?= $e($token) ?>"></iframe>
</div>
<script src="assets/js/firma.bundle.js"></script>
<script src="assets/js/firma_ui.js"></script>
</body>
</html>
