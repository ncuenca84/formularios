<?php
/** Autocompletado de personas por cedula (solo sesion de admin). */
require __DIR__ . '/eqr_common.php';
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$cedula = preg_replace('/\D/', '', (string)($_GET['cedula'] ?? ''));
if (strlen($cedula) === 9) { $cedula = '0' . $cedula; }
if (strlen($cedula) !== 10) {
    echo json_encode(['ok' => false]);
    exit;
}
$stmt = getDB()->prepare("SELECT cedula, nombres, correo, cargo, area FROM eqr_personas WHERE cedula = ?");
$stmt->execute([$cedula]);
$p = $stmt->fetch();
echo json_encode(['ok' => true, 'persona' => $p ?: null], JSON_UNESCAPED_UNICODE);
