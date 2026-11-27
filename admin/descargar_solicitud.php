<?php
/**
 * Entrega el PDF de una solicitud al ADMIN (los PDF nuevos viven en
 * storage/, que no es publico). Resuelve la version con firmas:
 *   - formularios 1-8 firmados en linea  -> fir_documentos
 *   - salidas de equipos (formulario 7)  -> acta del tramite
 *   - solicitudes antiguas               -> archivo publico heredado
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/eqr/fir_flujo.php';

if (empty($_SESSION['admin_logged'])) {
    header('Location: login.php');
    exit;
}

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM solicitudes WHERE id = ?");
$stmt->execute([$id]);
$sol = $stmt->fetch();
if (!$sol) { http_response_code(404); exit('Solicitud no encontrada.'); }

$path = null;
$nombre = preg_replace('/[^A-Za-z0-9\-]/', '_', (string)$sol['codigo']) . '.pdf';

// 1) documento firmado en linea (formularios 1-8)
$stmt = $db->prepare("SELECT * FROM fir_documentos WHERE solicitud_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$id]);
if ($doc = $stmt->fetch()) {
    $path = firDocPath($doc['codigo']);
}

// 2) acta de salida de equipo (formulario 7)
if ($path === null || !is_file($path)) {
    $stmt = $db->prepare("SELECT * FROM eqr_tramites WHERE solicitud_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$id]);
    if ($tr = $stmt->fetch()) {
        $p = eqrActaPath($tr['codigo']);
        if (is_file($p)) { $path = $p; }
    }
}

// 3) PDF publico heredado (flujo antiguo)
if (($path === null || !is_file($path)) && !empty($sol['pdf_path'])
    && strpos($sol['pdf_path'], 'assets/') === 0) {
    $p = __DIR__ . '/../' . $sol['pdf_path'];
    if (is_file($p)) { $path = $p; }
}

if ($path === null || !is_file($path)) {
    http_response_code(404);
    exit('El PDF de esta solicitud no está disponible.');
}
header('Content-Type: application/pdf');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . $nombre . '"');
readfile($path);
