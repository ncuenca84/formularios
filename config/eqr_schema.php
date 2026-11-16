<?php
/**
 * Esquema del modulo de Equipos Rentados (prefijo eqr_).
 *
 * MIGRACION IDEMPOTENTE: se puede ejecutar las veces que haga falta,
 * en una instalacion nueva (desde install.php) o en el servidor que ya
 * esta en produccion (desde admin/eqr_migrar.php), SIN borrar datos:
 * solo CREATE TABLE IF NOT EXISTS e INSERT IGNORE.
 */

function eqrMigrar(PDO $db): array
{
    $log = [];

    $tablas = [

        'eqr_contratos' => "
            CREATE TABLE IF NOT EXISTS eqr_contratos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                numero VARCHAR(100) NOT NULL UNIQUE,
                proceso VARCHAR(100) NOT NULL DEFAULT '',
                objeto VARCHAR(255) NOT NULL DEFAULT '',
                proveedor VARCHAR(255) NOT NULL DEFAULT '',
                fecha_inicio DATE NULL,
                fecha_fin DATE NULL,
                total_equipos INT NOT NULL DEFAULT 0,
                admin_nombre VARCHAR(255) NOT NULL DEFAULT '',
                admin_cedula VARCHAR(10) NOT NULL DEFAULT '',
                admin_cargo VARCHAR(255) NOT NULL DEFAULT '',
                admin_correo VARCHAR(255) NOT NULL DEFAULT '',
                admin_calidad VARCHAR(255) NOT NULL DEFAULT 'Custodio Administrativo / Servidor autorizado DTIC',
                texto_compromisos MEDIUMTEXT NULL,
                texto_consideraciones MEDIUMTEXT NULL,
                texto_aceptacion MEDIUMTEXT NULL,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'eqr_equipos' => "
            CREATE TABLE IF NOT EXISTS eqr_equipos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contrato_id INT NOT NULL,
                codigo_interno VARCHAR(30) NOT NULL UNIQUE,
                tipo VARCHAR(100) NOT NULL DEFAULT 'Computador portátil',
                marca VARCHAR(100) NOT NULL DEFAULT '',
                modelo VARCHAR(150) NOT NULL DEFAULT '',
                procesador VARCHAR(150) NOT NULL DEFAULT '',
                ram VARCHAR(50) NOT NULL DEFAULT '',
                almacenamiento VARCHAR(50) NOT NULL DEFAULT '',
                pantalla VARCHAR(100) NOT NULL DEFAULT '',
                serie VARCHAR(100) NOT NULL UNIQUE,
                cargador_entregado TINYINT(1) NOT NULL DEFAULT 1,
                serie_cargador VARCHAR(100) NOT NULL DEFAULT '',
                estado_fisico TEXT NULL,
                estado_operativo TEXT NULL,
                observaciones TEXT NULL,
                estado ENUM('en_stock','asignado','en_reparacion','retirado') NOT NULL DEFAULT 'en_stock',
                fuera_instalaciones TINYINT(1) NOT NULL DEFAULT 0,
                fecha_retorno_estimada DATE NULL,
                custodio_cedula VARCHAR(10) NULL,
                custodio_nombre VARCHAR(255) NULL,
                custodio_correo VARCHAR(255) NULL,
                custodio_cargo VARCHAR(255) NULL,
                custodio_area VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_contrato (contrato_id),
                KEY idx_estado (estado),
                KEY idx_custodio (custodio_cedula),
                CONSTRAINT fk_eqr_equipo_contrato FOREIGN KEY (contrato_id)
                    REFERENCES eqr_contratos(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'eqr_personas' => "
            CREATE TABLE IF NOT EXISTS eqr_personas (
                cedula VARCHAR(10) PRIMARY KEY,
                nombres VARCHAR(255) NOT NULL,
                correo VARCHAR(255) NOT NULL DEFAULT '',
                cargo VARCHAR(255) NOT NULL DEFAULT '',
                area VARCHAR(255) NOT NULL DEFAULT '',
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'eqr_tramites' => "
            CREATE TABLE IF NOT EXISTS eqr_tramites (
                id INT AUTO_INCREMENT PRIMARY KEY,
                codigo VARCHAR(30) NOT NULL UNIQUE,
                tipo ENUM('asignacion','traspaso','devolucion','salida','carga_inicial') NOT NULL,
                equipo_id INT NOT NULL,
                contrato_id INT NOT NULL,
                estado ENUM('borrador','en_firma','completado','rechazado','anulado') NOT NULL DEFAULT 'borrador',
                entrega_cedula VARCHAR(10) NULL,
                entrega_nombre VARCHAR(255) NULL,
                entrega_correo VARCHAR(255) NULL,
                entrega_cargo VARCHAR(255) NULL,
                entrega_area VARCHAR(255) NULL,
                recibe_cedula VARCHAR(10) NULL,
                recibe_nombre VARCHAR(255) NULL,
                recibe_correo VARCHAR(255) NULL,
                recibe_cargo VARCHAR(255) NULL,
                recibe_area VARCHAR(255) NULL,
                admin_contrato_nombre VARCHAR(255) NULL,
                admin_contrato_cedula VARCHAR(10) NULL,
                admin_contrato_cargo VARCHAR(255) NULL,
                admin_contrato_correo VARCHAR(255) NULL,
                admin_contrato_calidad VARCHAR(255) NULL,
                jefe_cedula VARCHAR(10) NULL,
                jefe_nombre VARCHAR(255) NULL,
                jefe_correo VARCHAR(255) NULL,
                jefe_cargo VARCHAR(255) NULL,
                motivo TEXT NULL,
                estado_fisico_reportado TEXT NULL,
                accesorios TEXT NULL,
                observaciones TEXT NULL,
                salida_motivo VARCHAR(100) NULL,
                salida_fecha DATE NULL,
                salida_fecha_retorno DATE NULL,
                salida_lugar VARCHAR(255) NULL,
                salida_retorno_registrado DATETIME NULL,
                referencia_acta_fisica VARCHAR(255) NULL,
                creado_por VARCHAR(100) NOT NULL DEFAULT '',
                solicitud_id INT NULL,
                pdf_original VARCHAR(500) NULL,
                pdf_actual VARCHAR(500) NULL,
                hash_actual CHAR(64) NULL,
                motivo_rechazo_anulacion TEXT NULL,
                completado_en DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_equipo (equipo_id),
                KEY idx_estado (estado),
                KEY idx_tipo (tipo),
                CONSTRAINT fk_eqr_tramite_equipo FOREIGN KEY (equipo_id)
                    REFERENCES eqr_equipos(id),
                CONSTRAINT fk_eqr_tramite_contrato FOREIGN KEY (contrato_id)
                    REFERENCES eqr_contratos(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'eqr_firmas' => "
            CREATE TABLE IF NOT EXISTS eqr_firmas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tramite_id INT NOT NULL,
                orden TINYINT NOT NULL,
                rol ENUM('administrador','custodio_anterior','nuevo_custodio','custodio','jefe_inmediato') NOT NULL,
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
                cadena_validada TINYINT(1) NOT NULL DEFAULT 0,
                hash_resultante CHAR(64) NULL,
                ip VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                fecha_firma DATETIME NULL,
                motivo_rechazo TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY idx_tramite (tramite_id),
                KEY idx_token (token_hash),
                CONSTRAINT fk_eqr_firma_tramite FOREIGN KEY (tramite_id)
                    REFERENCES eqr_tramites(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'eqr_bitacora' => "
            CREATE TABLE IF NOT EXISTS eqr_bitacora (
                id INT AUTO_INCREMENT PRIMARY KEY,
                equipo_id INT NULL,
                tramite_id INT NULL,
                accion VARCHAR(100) NOT NULL,
                detalle JSON NULL,
                usuario VARCHAR(150) NOT NULL DEFAULT '',
                ip VARCHAR(45) NULL,
                fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY idx_equipo (equipo_id),
                KEY idx_tramite (tramite_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'eqr_notificaciones' => "
            CREATE TABLE IF NOT EXISTS eqr_notificaciones (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tramite_id INT NULL,
                destinatario VARCHAR(255) NOT NULL,
                asunto VARCHAR(255) NOT NULL,
                resultado ENUM('enviado','fallido') NOT NULL,
                error TEXT NULL,
                fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY idx_tramite (tramite_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    foreach ($tablas as $nombre => $sql) {
        $db->exec($sql);
        $log[] = "Tabla $nombre verificada/creada.";
    }

    // Encabezado institucional para las actas del modulo (fila 8, editable en admin)
    $stmt = $db->prepare("INSERT IGNORE INTO formularios_meta
        (formulario_id, codigo_doc, version, nro_acta, fecha_aprobacion, titulo_encabezado)
        VALUES (8, 'GTIC.PA.FO.08', '01', '', '', 'ACTA DE EQUIPO DE CÓMPUTO EN ARRENDAMIENTO')");
    $stmt->execute();
    $log[] = 'Encabezado institucional (fila 8) verificado/creado.';

    // Configuracion del modulo (claves nuevas, sin tocar las existentes)
    $defaults = [
        'eqr_dominio_correo' => 'arconel.gob.ec',
        'eqr_dias_token' => '15',
        'eqr_url_base' => '',
    ];
    $stmt = $db->prepare("INSERT IGNORE INTO configuracion (clave, valor) VALUES (?, ?)");
    foreach ($defaults as $k => $v) {
        $stmt->execute([$k, $v]);
    }
    $log[] = 'Configuración del módulo verificada/creada.';

    return $log;
}
