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
                precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0,
                valor_total DECIMAL(12,2) NOT NULL DEFAULT 0,
                plazo_dias INT NOT NULL DEFAULT 0,
                contratista_ruc VARCHAR(13) NOT NULL DEFAULT '',
                contratista_representante VARCHAR(255) NOT NULL DEFAULT '',
                contratista_correo VARCHAR(255) NOT NULL DEFAULT '',
                contratista_telefono VARCHAR(50) NOT NULL DEFAULT '',
                contratista_direccion VARCHAR(255) NOT NULL DEFAULT '',
                delegado_nombre VARCHAR(255) NOT NULL DEFAULT '',
                delegado_cedula VARCHAR(10) NOT NULL DEFAULT '',
                delegado_cargo VARCHAR(255) NOT NULL DEFAULT '',
                delegado_correo VARCHAR(255) NOT NULL DEFAULT '',
                delegado_memorando VARCHAR(255) NOT NULL DEFAULT '',
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

    // Columnas agregadas despues de la primera version (idempotente: solo si faltan)
    $columnasNuevas = [
        ['eqr_contratos', 'precio_unitario', "DECIMAL(10,2) NOT NULL DEFAULT 0"],
        ['eqr_contratos', 'valor_total', "DECIMAL(12,2) NOT NULL DEFAULT 0"],
        ['eqr_contratos', 'plazo_dias', "INT NOT NULL DEFAULT 0"],
        ['eqr_contratos', 'contratista_ruc', "VARCHAR(13) NOT NULL DEFAULT ''"],
        ['eqr_contratos', 'contratista_representante', "VARCHAR(255) NOT NULL DEFAULT ''"],
        ['eqr_contratos', 'contratista_correo', "VARCHAR(255) NOT NULL DEFAULT ''"],
        ['eqr_contratos', 'contratista_telefono', "VARCHAR(50) NOT NULL DEFAULT ''"],
        ['eqr_contratos', 'contratista_direccion', "VARCHAR(255) NOT NULL DEFAULT ''"],
        ['eqr_contratos', 'delegado_nombre', "VARCHAR(255) NOT NULL DEFAULT ''"],
        ['eqr_contratos', 'delegado_cedula', "VARCHAR(10) NOT NULL DEFAULT ''"],
        ['eqr_contratos', 'delegado_cargo', "VARCHAR(255) NOT NULL DEFAULT ''"],
        ['eqr_contratos', 'delegado_correo', "VARCHAR(255) NOT NULL DEFAULT ''"],
        ['eqr_contratos', 'delegado_memorando', "VARCHAR(255) NOT NULL DEFAULT ''"],
    ];
    $delegadoRecienCreado = false;
    $stmtCol = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
                             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    foreach ($columnasNuevas as [$tabla, $col, $def]) {
        $stmtCol->execute([$tabla, $col]);
        if ((int)$stmtCol->fetchColumn() === 0) {
            $db->exec("ALTER TABLE $tabla ADD COLUMN $col $def");
            $log[] = "Columna $tabla.$col agregada.";
            if ($col === 'delegado_cedula') { $delegadoRecienCreado = true; }
        }
    }

    // Delegacion vigente del contrato real (solo UNA VEZ, al crear las columnas;
    // despues el admin la edita libremente en Contratos).
    if ($delegadoRecienCreado) {
        $db->prepare("UPDATE eqr_contratos SET
                delegado_nombre = 'Irvin Alexander Estevez Ron',
                delegado_cedula = '1726056706',
                delegado_cargo = 'Servidor de la DTIC',
                delegado_correo = 'irvin.estevez@arconel.gob.ec',
                delegado_memorando = 'Memorando de delegación (completar la referencia)'
              WHERE numero = 'ARCONEL-005-2026' AND delegado_cedula = ''")
           ->execute();
        $log[] = 'Delegado de gestión precargado en el contrato ARCONEL-005-2026 (edítelo en Contratos).';
    }

    // Contrato real ARCONEL-005-2026 precargado desde el documento firmado.
    // INSERT IGNORE: si ya existe un contrato con ese numero no se toca nada.
    $textos = require __DIR__ . '/eqr_textos_default.php';
    $stmtC = $db->prepare("INSERT IGNORE INTO eqr_contratos
        (numero, proceso, objeto, proveedor, fecha_inicio, fecha_fin, total_equipos,
         precio_unitario, valor_total, plazo_dias,
         contratista_ruc, contratista_representante, contratista_correo, contratista_telefono, contratista_direccion,
         admin_nombre, admin_cedula, admin_cargo, admin_correo, admin_calidad,
         delegado_nombre, delegado_cedula, delegado_cargo, delegado_correo, delegado_memorando,
         texto_compromisos, texto_consideraciones, texto_aceptacion, activo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                'Irvin Alexander Estevez Ron', '1726056706', 'Servidor de la DTIC',
                'irvin.estevez@arconel.gob.ec', 'Memorando de delegación (completar la referencia)',
                ?, ?, ?, 1)");
    $stmtC->execute([
        'ARCONEL-005-2026',
        'SIE-ARCONEL-2026-003',
        'ARRENDAMIENTO DE EQUIPOS DE CÓMPUTO',
        'ASITECNO ASESORIA EN SERVICIOS DE INTERNET Y TECNOLOGIA CIA. LTDA.',
        '2026-09-05',   // dia siguiente a la suscripcion (ultima firma electronica: 04/09/2026)
        '2027-09-19',   // 380 dias de plazo contractual
        60,
        48.88,          // valor unitario mensual por equipo (tabla de cantidades y precios)
        35193.60,       // monto maximo del contrato sin IVA
        380,
        '1792076226001',
        'Milton Estuardo Espinosa Toapanta',
        'asitecno.info@gmail.com',
        '0983199389',
        'Checa N151 y Juan Salinas, C.P. 170312, Quito, Pichincha',
        'Verónica Lucía Chamorro Alvarado',
        '1719366757',
        'Directora de Tecnologías de la Información y Comunicación',
        'veronica.chamorro@arconel.gob.ec',
        'Administradora del Contrato / Custodio Administrativo',
        $textos['compromisos'],
        $textos['consideraciones'],
        $textos['aceptacion'],
    ]);
    if ($stmtC->rowCount() > 0) {
        $log[] = 'Contrato ARCONEL-005-2026 precargado con los datos del documento firmado.';
    } else {
        $log[] = 'Contrato ARCONEL-005-2026 ya existía (no se modificó).';
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
