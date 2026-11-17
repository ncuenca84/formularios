<?php
/**
 * Comun de las paginas PUBLICAS del modulo (formulario 7 de salida):
 * sesion, CSRF y limite de intentos por IP. No requiere sesion de admin.
 */
require_once __DIR__ . '/flujo.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    session_start();
}

function eqrPubCsrfToken(): string
{
    if (empty($_SESSION['eqr_pub_csrf'])) {
        $_SESSION['eqr_pub_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['eqr_pub_csrf'];
}

function eqrPubCsrfCampo(): string
{
    return '<input type="hidden" name="csrf" value="'
        . htmlspecialchars(eqrPubCsrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function eqrPubCsrfOk(?string $t): bool
{
    return is_string($t) && $t !== '' && hash_equals(eqrPubCsrfToken(), $t);
}

/** ¿La IP supero el limite de intentos fallidos en 15 minutos? */
function eqrPubIpBloqueada(PDO $db, string $accion, int $max = 15): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($ip === '') { return false; }
    $stmt = $db->prepare("SELECT COUNT(*) FROM eqr_bitacora
                          WHERE accion = ? AND ip = ? AND fecha > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmt->execute([$accion, $ip]);
    return (int)$stmt->fetchColumn() >= $max;
}

function eqrPubIntentoFallido(PDO $db, string $accion, array $detalle = []): void
{
    eqrFlujoBitacora($db, null, null, $accion, $detalle, 'publico');
}
