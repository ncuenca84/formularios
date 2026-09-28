<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Genera un codigo unico de solicitud: SOL-YYYYMMDD-XXXX
 */
function generarCodigoSolicitud(): string
{
    $fecha = date('Ymd');
    $random = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    return "SOL-{$fecha}-{$random}";
}

/**
 * Obtiene un valor de configuracion de la BD
 */
function getConfig(string $clave, string $default = ''): string
{
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT valor FROM configuracion WHERE clave = ?");
        $stmt->execute([$clave]);
        $result = $stmt->fetch();
        return $result ? ($result['valor'] ?? $default) : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Obtiene todas las configuraciones
 */
function getAllConfig(): array
{
    try {
        $db = getDB();
        $stmt = $db->query("SELECT clave, valor FROM configuracion");
        $config = [];
        while ($row = $stmt->fetch()) {
            $config[$row['clave']] = $row['valor'];
        }
        return $config;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Guarda una solicitud en la BD
 */
function guardarSolicitud(array $datos): bool
{
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO solicitudes (codigo, nombre_completo, cedula, correo, cargo, area, tipo_formulario, pdf_path)
            VALUES (:codigo, :nombre, :cedula, :correo, :cargo, :area, :tipo, :pdf_path)
        ");
        return $stmt->execute([
            ':codigo' => $datos['codigo'],
            ':nombre' => $datos['nombre_completo'],
            ':cedula' => $datos['cedula'],
            ':correo' => $datos['correo'],
            ':cargo' => $datos['cargo'],
            ':area' => $datos['area'],
            ':tipo' => $datos['tipo_formulario'] ?? 'Solicitud de Acceso VPN',
            ':pdf_path' => $datos['pdf_path'] ?? '',
        ]);
    } catch (Exception $e) {
        error_log("Error guardando solicitud: " . $e->getMessage());
        return false;
    }
}

/**
 * Envia correo de notificacion al admin
 */
function enviarNotificacionAdmin(array $datos): bool
{
    $emailAdmin = getConfig('email_admin', ADMIN_EMAIL);
    $institucion = getConfig('nombre_institucion', 'INSTITUCION');

    $asunto = "[{$institucion}] Nueva solicitud: {$datos['codigo']}";

    $mensaje = "
    <html>
    <head><style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #003366; color: white; padding: 15px; text-align: center; }
        .body { padding: 20px; background: #f9f9f9; }
        .field { margin-bottom: 10px; }
        .label { font-weight: bold; color: #003366; }
        .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
    </style></head>
    <body>
    <div class='container'>
        <div class='header'>
            <h2>Nueva Solicitud Generada</h2>
        </div>
        <div class='body'>
            <p>Se ha generado una nueva solicitud con los siguientes datos:</p>
            <div class='field'><span class='label'>Codigo:</span> {$datos['codigo']}</div>
            <div class='field'><span class='label'>Nombre:</span> {$datos['nombre_completo']}</div>
            <div class='field'><span class='label'>Cedula:</span> {$datos['cedula']}</div>
            <div class='field'><span class='label'>Correo:</span> {$datos['correo']}</div>
            <div class='field'><span class='label'>Cargo:</span> {$datos['cargo']}</div>
            <div class='field'><span class='label'>Area:</span> {$datos['area']}</div>
            <div class='field'><span class='label'>Fecha:</span> {$datos['fecha']}</div>
        </div>
        <div class='footer'>
            <p>Este es un correo automatico del sistema de formularios.</p>
        </div>
    </div>
    </body>
    </html>";

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: noreply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost'),
        'Reply-To: ' . $datos['correo'],
    ];

    return @mail($emailAdmin, $asunto, $mensaje, implode("\r\n", $headers));
}

/**
 * Sanitiza datos de entrada
 */
function sanitizar(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida los datos del formulario
 */
function validarFormulario(array $post): array
{
    $errores = [];

    if (empty($post['nombre_completo'])) {
        $errores[] = 'El nombre completo es obligatorio.';
    }
    if (empty($post['cedula']) || !preg_match('/^[0-9]{10,13}$/', $post['cedula'])) {
        $errores[] = 'La cedula debe tener entre 10 y 13 digitos numericos.';
    }
    if (empty($post['correo']) || !filter_var($post['correo'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingrese un correo electronico valido.';
    }
    if (empty($post['cargo'])) {
        $errores[] = 'El cargo es obligatorio.';
    }
    if (empty($post['area'])) {
        $errores[] = 'El area es obligatoria.';
    }

    return $errores;
}

/**
 * Obtiene metadatos de encabezado de un formulario especifico
 */
function getFormularioMeta(int $formularioId): array
{
    $defaults = [
        'codigo_doc' => '',
        'version' => '01',
        'nro_acta' => '',
        'fecha_aprobacion' => '',
        'titulo_encabezado' => '',
    ];
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM formularios_meta WHERE formulario_id = ?");
        $stmt->execute([$formularioId]);
        $result = $stmt->fetch();
        return $result ?: $defaults;
    } catch (Exception $e) {
        return $defaults;
    }
}

/**
 * Obtiene metadatos de todos los formularios
 */
function getAllFormulariosMeta(): array
{
    try {
        $db = getDB();
        $stmt = $db->query("SELECT * FROM formularios_meta ORDER BY formulario_id");
        $result = [];
        while ($row = $stmt->fetch()) {
            $result[$row['formulario_id']] = $row;
        }
        return $result;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Genera el HTML del encabezado institucional para PDF (position:fixed para repetir en cada pagina)
 */
function renderPdfEncabezadoInstitucional(array $config, array $meta, string $logoDataUri, string $logoSecundarioDataUri): string
{
    $institucion = htmlspecialchars($config['nombre_institucion'] ?? 'AGENCIA DE REGULACION Y CONTROL DE ELECTRICIDAD');
    $subtitulo = htmlspecialchars($config['subtitulo_institucion'] ?? 'DIRECCION DE TECNOLOGIAS DE LA INFORMACION Y COMUNICACION');
    $tituloForm = htmlspecialchars($meta['titulo_encabezado'] ?? '');
    $codigoDoc = htmlspecialchars($meta['codigo_doc'] ?? '');
    $version = htmlspecialchars($meta['version'] ?? '01');
    $nroActa = htmlspecialchars($meta['nro_acta'] ?? '');
    $fechaAprob = htmlspecialchars($meta['fecha_aprobacion'] ?? '');

    $logoHtml = '';
    if (!empty($logoDataUri)) {
        $logoHtml = '<img src="' . $logoDataUri . '" style="max-height:50px;max-width:85px;">';
    }
    $logoSecHtml = '';
    if (!empty($logoSecundarioDataUri)) {
        $logoSecHtml = '<img src="' . $logoSecundarioDataUri . '" style="max-height:50px;max-width:85px;">';
    }

    return '
    <div style="position:fixed;top:-30mm;left:0;right:0;">
        <table style="width:100%;border-collapse:collapse;border:1px solid #999;">
            <tr>
                <td rowspan="3" style="width:110px;text-align:center;vertical-align:middle;border:1px solid #999;padding:4px;">
                    ' . $logoHtml . '
                    ' . ($logoSecHtml ? '<br>' . $logoSecHtml : '') . '
                </td>
                <td style="text-align:center;vertical-align:middle;border:1px solid #999;padding:3px;font-size:9pt;font-weight:bold;">
                    ' . $institucion . '
                </td>
                <td style="width:140px;border:1px solid #999;padding:3px;font-size:7.5pt;">
                    <strong>Codigo:</strong> ' . $codigoDoc . '
                </td>
            </tr>
            <tr>
                <td style="text-align:center;vertical-align:middle;border:1px solid #999;padding:3px;font-size:8pt;">
                    ' . $subtitulo . '
                </td>
                <td style="border:1px solid #999;padding:3px;font-size:7.5pt;">
                    <strong>Version:</strong> ' . $version . '
                </td>
            </tr>
            <tr>
                <td style="text-align:center;vertical-align:middle;border:1px solid #999;padding:4px;font-size:9pt;font-weight:bold;">
                    ' . $tituloForm . '
                </td>
                <td style="border:1px solid #999;padding:3px;font-size:7.5pt;">
                    <strong>N&deg; de Acta:</strong> ' . $nroActa . '<br>
                    <strong>Fecha aprob.:</strong> ' . $fechaAprob . '
                </td>
            </tr>
        </table>
    </div>
    <div style="position:fixed;bottom:-20mm;left:0;right:0;text-align:center;font-size:7pt;color:#666;border-top:1px solid #999;padding-top:4px;line-height:1.4;">
        <em>&quot;Este documento es para uso exclusivo de la ARCONEL. Se prohibe su uso no autorizado&quot;.</em><br>
        GESTION GENERAL DE PLANIFICACION Y GESTION ESTRATEGICA
    </div>';
}

/**
 * Obtiene la ruta base del logo como data URI para incrustar en el PDF
 */
function getLogoDataUri(string $configKey): string
{
    $path = getConfig($configKey);
    if (empty($path)) {
        return '';
    }

    $fullPath = __DIR__ . '/../' . $path;
    if (!file_exists($fullPath)) {
        return '';
    }

    $mime = mime_content_type($fullPath);
    $data = base64_encode(file_get_contents($fullPath));
    return "data:{$mime};base64,{$data}";
}
