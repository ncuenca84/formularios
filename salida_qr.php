<?php
/**
 * Validacion en PORTERIA de la salida de un equipo rentado: salida_qr.php?v=TOKEN
 *
 * El guardia escanea el QR del acta con su celular y esta pagina le dice,
 * en grande y sin datos sensibles, si la salida esta AUTORIZADA y vigente.
 * No requiere usuario ni clave; el token es aleatorio e inadivinable.
 * Cada consulta queda en la bitacora. Limite de intentos por IP.
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/eqr/flujo.php';

header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; img-src 'self'");
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

$e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$db = getDB();

// limite de intentos con codigos invalidos por IP (15 minutos)
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$bloqueada = false;
if ($ip !== '') {
    $stmt = $db->prepare("SELECT COUNT(*) FROM eqr_bitacora
                          WHERE accion = 'salida_qr_invalido' AND ip = ? AND fecha > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmt->execute([$ip]);
    $bloqueada = (int)$stmt->fetchColumn() >= 30;
}

$token = (string)($_GET['v'] ?? '');
if ($bloqueada) {
    $r = ['encontrada' => false, 'autorizada' => false,
          'motivo' => 'Demasiados intentos desde esta conexión. Espere unos minutos.', 'tramite' => null];
} else {
    // Las salidas con plazo vencido se cierran solas (el equipo vuelve al
    // estado normal); un QR vencido se muestra en rojo con "VENCIÓ el ...".
    try { eqrCerrarSalidasVencidas($db); } catch (Throwable $exAuto) { /* tablas sin migrar */ }
    $r = eqrEstadoSalidaQr($db, $token);
    if (!$r['encontrada']) {
        eqrFlujoBitacora($db, null, null, 'salida_qr_invalido', [], 'porteria');
    } else {
        eqrFlujoBitacora($db, (int)$r['tramite']['equipo_id'], (int)$r['tramite']['id'],
            'salida_qr_consulta', ['autorizada' => $r['autorizada'], 'motivo' => $r['motivo']], 'porteria');
    }
}
$t = $r['tramite'];
$ok = $r['autorizada'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Validación de salida — ARCONEL</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: system-ui, -apple-system, "Segoe UI", Arial, sans-serif;
           margin: 0; background: #f0f2f5; color: #222; }
    .cab { background: #003366; color: #fff; padding: 14px 16px; font-size: .95rem; }
    .cab strong { font-size: 1.05rem; }
    .caja { max-width: 560px; margin: 16px auto; padding: 0 12px; }
    .banner { border-radius: 14px; padding: 26px 18px; text-align: center; color: #fff; }
    .banner.ok { background: #157347; }
    .banner.no { background: #b02a37; }
    .banner .icono { font-size: 3.4rem; line-height: 1; }
    .banner h1 { font-size: 1.6rem; margin: .4rem 0 .2rem; }
    .banner p { margin: .2rem 0 0; font-size: 1.02rem; }
    .datos { background: #fff; border-radius: 14px; margin-top: 14px; padding: 6px 16px;
             box-shadow: 0 2px 12px rgba(0,0,0,.08); }
    .fila { display: flex; justify-content: space-between; gap: 10px; padding: 10px 0;
            border-bottom: 1px solid #eee; font-size: .98rem; }
    .fila:last-child { border-bottom: none; }
    .fila .k { color: #666; }
    .fila .v { font-weight: 600; text-align: right; }
    .pie { text-align: center; color: #888; font-size: .8rem; margin: 16px 0; }
</style>
</head>
<body>
<div class="cab"><strong>ARCONEL</strong> · Validación de salida de equipo</div>
<div class="caja">

    <?php if ($ok): ?>
        <div class="banner ok">
            <div class="icono">&#10004;</div>
            <h1>SALIDA AUTORIZADA</h1>
            <p>Autorización vigente con todas sus firmas.</p>
        </div>
    <?php else: ?>
        <div class="banner no">
            <div class="icono">&#10006;</div>
            <h1>NO AUTORIZADA</h1>
            <p><?= $e($r['motivo']) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($t): ?>
        <div class="datos">
            <div class="fila"><span class="k">Equipo</span>
                <span class="v"><?= $e($t['equipo_codigo']) ?><?= trim($t['equipo_marca'] . ' ' . $t['equipo_modelo']) !== ''
                    ? ' — ' . $e($t['equipo_marca']) . ' ' . $e($t['equipo_modelo'])
                    : ' — Bien institucional' ?></span></div>
            <?php if (!str_starts_with((string)$t['equipo_serie'], 'BIEN-')): ?>
            <div class="fila"><span class="k">Serie</span>
                <span class="v"><?= $e($t['equipo_serie']) ?></span></div>
            <?php endif; ?>
            <div class="fila"><span class="k">Custodio</span>
                <span class="v"><?= $e($t['entrega_nombre']) ?></span></div>
            <div class="fila"><span class="k">Sale el</span>
                <span class="v"><?= $t['salida_fecha'] ? $e(date('d/m/Y', strtotime($t['salida_fecha']))) : '—' ?></span></div>
            <div class="fila"><span class="k">Retorno estimado</span>
                <span class="v"><?= $t['salida_fecha_retorno'] ? $e(date('d/m/Y', strtotime($t['salida_fecha_retorno']))) : 'Indefinido' ?></span></div>
            <div class="fila"><span class="k">Trámite</span>
                <span class="v"><?= $e($t['codigo']) ?></span></div>
        </div>
        <p class="pie">Verifique que la serie y el nombre coincidan con el equipo y la persona.<br>
            Consulta: <?= $e(date('d/m/Y H:i:s')) ?></p>
    <?php else: ?>
        <p class="pie">Si el código debería ser válido, el funcionario debe contactar a la DTIC.</p>
    <?php endif; ?>
</div>
</body>
</html>
