-- Crear base de datos
CREATE DATABASE IF NOT EXISTS formularios_pdf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE formularios_pdf;

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

-- Crear admin por defecto (password: admin123)
INSERT INTO admins (username, password, nombre) VALUES
('admin', '$2y$10$YourHashedPasswordHere', 'Administrador')
ON DUPLICATE KEY UPDATE username = username;
