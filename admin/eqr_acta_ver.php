<?php
/** Entrega el acta PDF de un tramite (solo sesion de admin). storage/ no es publico. */
require __DIR__ . '/eqr_common.php';
require_once __DIR__ . '/../includes/eqr/flujo.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$tramite = eqrTramitePorId($db, $id);
$path = $tramite ? eqrRutaActaAbs($tramite) : '';
if (!$tramite || !is_file($path)) {
    http_response_code(404);
    exit('Acta no encontrada.');
}
$inline = isset($_GET['inline']);
header('Content-Type: application/pdf');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') .
       '; filename="ACTA_' . preg_replace('/[^A-Za-z0-9\-]/', '_', $tramite['codigo']) . '.pdf"');
readfile($path);
