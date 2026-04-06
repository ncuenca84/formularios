<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'formularios_pdf');
define('DB_USER', 'root');
define('DB_PASS', '');

define('ADMIN_EMAIL', 'soporte@tudominio.com');
define('SITE_NAME', 'Sistema de Formularios Institucionales');

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            die("Error de conexion a la base de datos: " . $e->getMessage());
        }
    }
    return $pdo;
}
