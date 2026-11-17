<?php
/**
 * Esquema de la FIRMA EN LINEA de los formularios 1-6 (prefijo fir_).
 * Migracion IDEMPOTENTE: se ejecuta desde install.php y admin/eqr_migrar.php
 * sin borrar nada (CREATE TABLE IF NOT EXISTS + columnas solo si faltan).
 */

function firMigrar(PDO $db): array
{
    $log = [];

    $db->exec("
        CREATE TABLE IF NOT EXISTS fir_documentos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            codigo VARCHAR(30) NOT NULL UNIQUE,
            formulario_id INT NOT NULL,
            titulo VARCHAR(255) NOT NULL DEFAULT '',
            solicitud_id INT NULL,
            estado ENUM('borrador','en_firma','completado','rechazado','anulado') NOT NULL DEFAULT 'borrador',
            pdf_actual VARCHAR(500) NULL,
            hash_actual CHAR(64) NULL,
            motivo_rechazo_anulacion TEXT NULL,
            completado_en DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_estado (estado),
            KEY idx_formulario (formulario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $log[] = 'Tabla fir_documentos verificada/creada.';

    $db->exec("
        CREATE TABLE IF NOT EXISTS fir_firmas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            documento_id INT NOT NULL,
            orden TINYINT NOT NULL,
            rol VARCHAR(40) NOT NULL,
            etiqueta VARCHAR(120) NOT NULL DEFAULT '',
            cedula VARCHAR(10) NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            correo VARCHAR(255) NOT NULL DEFAULT '',
            cargo VARCHAR(255) NOT NULL DEFAULT '',
            estado ENUM('en_espera','pendiente','firmado','rechazado') NOT NULL DEFAULT 'en_espera',
            token_hash CHAR(64) NULL,
            token_expira DATETIME NULL,
            intentos_fallidos INT NOT NULL DEFAULT 0,
            cert_cn VARCHAR(255) NULL,
            cert_serial VARCHAR(100) NULL,
            cert_emisor VARCHAR(255) NULL,
            cert_desde DATETIME NULL,
            cert_hasta DATETIME NULL,
            hash_resultante CHAR(64) NULL,
            ip VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            fecha_firma DATETIME NULL,
            motivo_rechazo TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_documento (documento_id),
            KEY idx_token (token_hash),
            CONSTRAINT fk_fir_firma_doc FOREIGN KEY (documento_id) REFERENCES fir_documentos(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $log[] = 'Tabla fir_firmas verificada/creada.';

    // Columna documento_id en eqr_notificaciones (si falta)
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
                          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'eqr_notificaciones'
                          AND COLUMN_NAME = 'documento_id'");
    $stmt->execute();
    if ((int)$stmt->fetchColumn() === 0) {
        $db->exec("ALTER TABLE eqr_notificaciones ADD COLUMN documento_id INT NULL AFTER tramite_id");
        $log[] = 'Columna eqr_notificaciones.documento_id agregada.';
    }

    // Regularizar solicitudes segun sus circuitos de firma (con conteos)
    require_once __DIR__ . '/../includes/eqr/fir_flujo.php';
    $n = firSincronizarSolicitudesPendientes($db);
    $log[] = sprintf('Solicitudes sincronizadas: %d enlaces reparados, %d aprobadas, %d rechazadas (formularios); %d aprobadas, %d rechazadas (salidas).',
        $n['enlaces'], $n['aprobadas'], $n['rechazadas'], $n['salidas_aprobadas'], $n['salidas_rechazadas']);

    // Correos de las autoridades prellenadas (editables en el admin)
    $stmt = $db->prepare("INSERT IGNORE INTO configuracion (clave, valor) VALUES (?, ?)");
    foreach (['osi_correo' => '', 'dtic_correo' => ''] as $k => $v) {
        $stmt->execute([$k, $v]);
    }
    $log[] = 'Configuración de correos de autoridades verificada.';

    return $log;
}
