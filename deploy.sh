#!/bin/bash
# ============================================================
# SCRIPT DE DESPLIEGUE - Sistema de Formularios ARCONEL
# Servidor: CWP (CentOS Web Panel)
# Ruta: /home/arconel/formularios.arconel.gob.ec
# ============================================================

set -e

# CONFIGURACION - MODIFICAR SEGUN TU SERVIDOR
DEPLOY_PATH="/home/arconel/formularios.arconel.gob.ec"
DB_NAME="arconel_formularios"
DB_USER="arconel_formdb"
DB_PASS="CAMBIA_ESTA_CLAVE_SEGURA"
DOMAIN="formularios.arconel.gob.ec"
WEB_USER="arconel"

echo "=========================================="
echo " Desplegando Sistema de Formularios"
echo " Dominio: $DOMAIN"
echo "=========================================="

# 1. Verificar que estamos en el directorio correcto
if [ ! -d "$DEPLOY_PATH" ]; then
    echo "[ERROR] El directorio $DEPLOY_PATH no existe."
    echo "Verifica que el subdominio este creado en CWP."
    exit 1
fi

cd "$DEPLOY_PATH"

# 2. Clonar o actualizar repositorio
if [ -d ".git" ]; then
    echo "[INFO] Actualizando repositorio existente..."
    git pull origin claude/php-pdf-form-generator-RmXT9
else
    echo "[INFO] Clonando repositorio..."
    git clone -b claude/php-pdf-form-generator-RmXT9 https://github.com/ncuenca84/formularios.git .
fi

# 3. Instalar Composer si no existe
if ! command -v composer &> /dev/null; then
    echo "[INFO] Instalando Composer..."
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
fi

# 4. Instalar dependencias (DomPDF)
echo "[INFO] Instalando dependencias PHP..."
composer install --no-dev --optimize-autoloader

# 5. Configurar base de datos
echo "[INFO] Configurando conexion a base de datos..."
cat > config/database.php << 'DBEOF'
<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'PLACEHOLDER_DB');
define('DB_USER', 'PLACEHOLDER_USER');
define('DB_PASS', 'PLACEHOLDER_PASS');

define('ADMIN_EMAIL', 'soporte@arconel.gob.ec');
define('SITE_NAME', 'Sistema de Formularios - ARCONEL');

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
DBEOF

# Reemplazar placeholders con valores reales
sed -i "s/PLACEHOLDER_DB/$DB_NAME/g" config/database.php
sed -i "s/PLACEHOLDER_USER/$DB_USER/g" config/database.php
sed -i "s/PLACEHOLDER_PASS/$DB_PASS/g" config/database.php

# 6. Crear directorios de uploads
echo "[INFO] Creando directorios..."
mkdir -p assets/uploads/logos
mkdir -p assets/uploads/pdfs

# 7. Establecer permisos
echo "[INFO] Configurando permisos..."
chown -R ${WEB_USER}:${WEB_USER} "$DEPLOY_PATH"
find "$DEPLOY_PATH" -type f -exec chmod 644 {} \;
find "$DEPLOY_PATH" -type d -exec chmod 755 {} \;

# Permisos de escritura para uploads
chmod -R 775 assets/uploads/
chown -R ${WEB_USER}:nobody assets/uploads/

# Proteger archivos sensibles
chmod 640 config/database.php

echo ""
echo "=========================================="
echo " DESPLIEGUE COMPLETADO"
echo "=========================================="
echo ""
echo " PASOS SIGUIENTES (manuales):"
echo ""
echo " 1. Crear BD y usuario en CWP:"
echo "    - Panel CWP > SQL Databases > Create Database"
echo "    - Nombre BD: $DB_NAME"
echo "    - Usuario: $DB_USER"
echo "    - Password: (el que configuraste arriba)"
echo ""
echo " 2. Ejecutar instalador web:"
echo "    https://$DOMAIN/install.php"
echo ""
echo " 3. Despues de instalar, ELIMINAR install.php:"
echo "    rm $DEPLOY_PATH/install.php"
echo ""
echo " 4. Acceder al panel admin:"
echo "    https://$DOMAIN/admin/login.php"
echo ""
echo "=========================================="
