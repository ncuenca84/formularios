-- NOTA: Ejecutar con: mysql -u root NOMBRE_DE_TU_BD < config/setup.sql
-- No incluye CREATE DATABASE para evitar conflictos con el nombre real de la BD

-- Tabla de solicitudes
CREATE TABLE IF NOT EXISTS solicitudes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    nombre_completo VARCHAR(255) NOT NULL,
    cedula VARCHAR(20) NOT NULL,
    correo VARCHAR(255) NOT NULL,
    cargo VARCHAR(255) NOT NULL,
    area VARCHAR(255) NOT NULL,
    tipo_formulario VARCHAR(100) NOT NULL DEFAULT 'Solicitud de Acceso VPN',
    fecha_solicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'aprobada', 'rechazada') DEFAULT 'pendiente',
    pdf_path VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de configuracion del sistema (admin)
CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de usuarios admin
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insertar configuracion por defecto
INSERT INTO configuracion (clave, valor) VALUES
('nombre_institucion', 'INSTITUCION PUBLICA'),
('subtitulo_institucion', 'Direccion de Tecnologias de la Informacion'),
('titulo_formulario', 'SOLICITUD DE ACCESO VPN'),
('logo_path', ''),
('logo_secundario_path', ''),
('color_primario', '#003366'),
('color_secundario', '#0066cc'),
('pie_pagina_linea1', 'Direccion: Av. Principal S/N'),
('pie_pagina_linea2', 'Telefono: (02) 123-4567 | www.institucion.gob.ec'),
('pie_pagina_linea3', 'Correo: soporte@tudominio.com'),
('encabezado_extra', ''),
('email_admin', 'soporte@tudominio.com'),
('texto_post_envio', 'Descargue el documento, firmelo con FirmaEC y envielo al correo: soporte@tudominio.com')
ON DUPLICATE KEY UPDATE clave = clave;

-- Tabla de metadatos por formulario (encabezado institucional)
CREATE TABLE IF NOT EXISTS formularios_meta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    codigo_doc VARCHAR(50) NOT NULL DEFAULT '',
    version VARCHAR(10) NOT NULL DEFAULT '01',
    nro_acta VARCHAR(20) NOT NULL DEFAULT '',
    fecha_aprobacion VARCHAR(20) NOT NULL DEFAULT '',
    titulo_encabezado VARCHAR(255) NOT NULL DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_formulario (formulario_id)
) ENGINE=InnoDB;

-- Insertar metadatos por defecto para cada formulario
INSERT INTO formularios_meta (formulario_id, codigo_doc, version, nro_acta, fecha_aprobacion, titulo_encabezado) VALUES
(1, 'GTIC.PA.FO.01', '01', '021', '27/01/2026', 'ACUERDO DE CONFIDENCIALIDAD Y NO DIVULGACION DE INFORMACION CON TERCEROS'),
(2, 'GTIC.PA.FO.02', '01', '021', '27/01/2026', 'SOLICITUD DE ACCESO A SISTEMAS PARA TERCEROS'),
(3, 'GTIC.PA.FO.03', '01', '021', '27/01/2026', 'SOLICITUD DE ACCESO A LOS SISTEMAS INFORMATICOS'),
(4, 'GTIC.PA.FO.04', '01', '021', '27/01/2026', 'FORMULARIO DE HABILITACION DE ACCESO A LA RED INTERNA VIA VPN PARA USUARIOS EXTERNOS'),
(5, 'GTIC.PA.FO.05', '01', '021', '27/01/2026', 'FORMULARIO DE AUTORIZACION DE PRIVILEGIOS ESPECIALES DIRECTORIO ACTIVO'),
(6, 'GTIC.PA.FO.06', '01', '021', '27/01/2026', 'SOLICITUD DE ACCESOS ESPECIALES PARA EL SERVICIO DE INTERNET')
ON DUPLICATE KEY UPDATE formulario_id = formulario_id;

-- Crear admin por defecto (password: admin123)
INSERT INTO admins (username, password, nombre) VALUES
('admin', '$2y$10$YourHashedPasswordHere', 'Administrador')
ON DUPLICATE KEY UPDATE username = username;
