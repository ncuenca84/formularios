<?php
require_once __DIR__ . '/config/database.php';

if (empty($_GET['codigo'])) {
    header('Location: index.php');
    exit;
}

$codigo = preg_replace('/[^A-Za-z0-9\-]/', '', $_GET['codigo']);

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT pdf_path, codigo FROM solicitudes WHERE codigo = ?");
    $stmt->execute([$codigo]);
    $solicitud = $stmt->fetch();

    if (!$solicitud || empty($solicitud['pdf_path'])) {
        die('Solicitud no encontrada.');
    }

    $filePath = __DIR__ . '/' . $solicitud['pdf_path'];
    if (!file_exists($filePath)) {
        die('El archivo PDF no existe.');
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Solicitud_' . $solicitud['codigo'] . '.pdf"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, must-revalidate');

    readfile($filePath);
    exit;
} catch (Exception $e) {
    die('Error al descargar el archivo.');
}
