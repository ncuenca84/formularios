<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/functions.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$tipoFormulario = (int)($_POST['tipo_formulario'] ?? 0);

if ($tipoFormulario < 1 || $tipoFormulario > 6) {
    $_SESSION['mensaje'] = 'Tipo de formulario no valido.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: index.php');
    exit;
}

// Nombres de los formularios
$nombresFormulario = [
    1 => 'Acuerdo de Confidencialidad con Terceros',
    2 => 'Solicitud de Acceso a Sistemas para Terceros',
    3 => 'Solicitud de Acceso a Sistemas Informaticos',
    4 => 'Habilitacion de Acceso VPN Externos',
    5 => 'Privilegios Especiales Directorio Activo',
    6 => 'Accesos Especiales Internet',
];

// Validar campos obligatorios segun tipo
$errores = validarPorTipo($tipoFormulario, $_POST);

if (!empty($errores)) {
    $_SESSION['mensaje'] = implode('<br>', $errores);
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: index.php');
    exit;
}

// Preparar datos
$config = getAllConfig();
$codigo = generarCodigoSolicitud();
$fecha = date('d/m/Y H:i');

// Sanitizar todos los campos POST
$datos = array_map(function ($v) {
    return is_string($v) ? sanitizar($v) : $v;
}, $_POST);

$datos['codigo'] = $codigo;
$datos['fecha'] = $fecha;
$datos['tipo_formulario'] = $nombresFormulario[$tipoFormulario];

// Extraer nombre y correo segun tipo de formulario para la BD
$datosDB = extraerDatosPrincipales($tipoFormulario, $datos);

// Obtener logos como data URI
$logoDataUri = getLogoDataUri('logo_path');
$logoSecundarioDataUri = getLogoDataUri('logo_secundario_path');

// Obtener metadatos del encabezado institucional para este formulario
$meta = getFormularioMeta($tipoFormulario);

// Generar HTML del PDF usando el template correspondiente
$templateFile = __DIR__ . "/templates/pdf_form{$tipoFormulario}.php";
if (!file_exists($templateFile)) {
    $_SESSION['mensaje'] = 'Template PDF no encontrado.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: index.php');
    exit;
}

$html = require $templateFile;

// Configurar DomPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');
$options->set('isFontSubsettingEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Guardar PDF en servidor
$pdfDir = __DIR__ . '/assets/uploads/pdfs';
if (!is_dir($pdfDir)) {
    mkdir($pdfDir, 0755, true);
}
$pdfFilename = "formulario{$tipoFormulario}_{$codigo}.pdf";
$pdfPath = "{$pdfDir}/{$pdfFilename}";
file_put_contents($pdfPath, $dompdf->output());

$datosDB['pdf_path'] = "assets/uploads/pdfs/{$pdfFilename}";

// Guardar en BD
guardarSolicitud($datosDB);

// Enviar correo a soporte
enviarNotificacionAdmin($datosDB);

// Redirigir con datos por URL (compatible con Edge)
$params = http_build_query([
    'codigo' => $codigo,
    'tipo' => $nombresFormulario[$tipoFormulario],
    'nombre' => $datosDB['nombre_completo'] ?? '',
    'pdf' => "assets/uploads/pdfs/{$pdfFilename}",
]);

header('Location: resultado.php?' . $params);
exit;


/**
 * Validacion por tipo de formulario
 */
function validarPorTipo(int $tipo, array $post): array
{
    $errores = [];

    switch ($tipo) {
        case 1:
            if (empty($post['institucion_receptor'])) $errores[] = 'El nombre de la institucion es obligatorio.';
            if (empty($post['nombre_receptor'])) $errores[] = 'El nombre del receptor es obligatorio.';
            if (empty($post['cargo_receptor'])) $errores[] = 'El cargo del receptor es obligatorio.';
            if (empty($post['cedula_receptor'])) $errores[] = 'La cedula del receptor es obligatoria.';
            if (empty($post['correo']) || !filter_var($post['correo'], FILTER_VALIDATE_EMAIL)) $errores[] = 'Correo invalido.';
            break;

        case 2:
            if (empty($post['institucion'])) $errores[] = 'La institucion es obligatoria.';
            if (empty($post['solicitante_nombres'])) $errores[] = 'Los nombres del solicitante son obligatorios.';
            if (empty($post['solicitante_nro_doc'])) $errores[] = 'El numero de documento es obligatorio.';
            if (empty($post['correo']) || !filter_var($post['correo'], FILTER_VALIDATE_EMAIL)) $errores[] = 'Correo invalido.';
            if (empty($post['sistema_servicio'])) $errores[] = 'El sistema/servicio solicitado es obligatorio.';
            break;

        case 3:
            if (empty($post['autorizador_nombre'])) $errores[] = 'El nombre del autorizador es obligatorio.';
            if (empty($post['nombre1'])) $errores[] = 'El primer nombre es obligatorio.';
            if (empty($post['apellido1'])) $errores[] = 'El primer apellido es obligatorio.';
            if (empty($post['cedula'])) $errores[] = 'La cedula es obligatoria.';
            if (empty($post['correo_usuario'])) $errores[] = 'El correo es obligatorio.';
            break;

        case 4:
            if (empty($post['institucion'])) $errores[] = 'La institucion es obligatoria.';
            if (empty($post['nombre_completo'])) $errores[] = 'El nombre completo es obligatorio.';
            if (empty($post['cedula'])) $errores[] = 'La cedula es obligatoria.';
            if (empty($post['correo']) || !filter_var($post['correo'], FILTER_VALIDATE_EMAIL)) $errores[] = 'Correo invalido.';
            if (empty($post['justificacion'])) $errores[] = 'La justificacion es obligatoria.';
            break;

        case 5:
            if (empty($post['nombre_completo'])) $errores[] = 'El nombre completo es obligatorio.';
            if (empty($post['cedula'])) $errores[] = 'La cedula es obligatoria.';
            if (empty($post['correo']) || !filter_var($post['correo'], FILTER_VALIDATE_EMAIL)) $errores[] = 'Correo invalido.';
            if (empty($post['justificacion'])) $errores[] = 'La justificacion es obligatoria.';
            break;

        case 6:
            if (empty($post['nombre_completo'])) $errores[] = 'El nombre completo es obligatorio.';
            if (empty($post['cedula'])) $errores[] = 'La cedula es obligatoria.';
            if (empty($post['correo']) || !filter_var($post['correo'], FILTER_VALIDATE_EMAIL)) $errores[] = 'Correo invalido.';
            if (empty($post['justificacion'])) $errores[] = 'La justificacion es obligatoria.';
            break;
    }

    return $errores;
}

/**
 * Extrae nombre, cedula, correo, cargo, area para guardar en la BD
 */
function extraerDatosPrincipales(int $tipo, array $datos): array
{
    $base = [
        'codigo' => $datos['codigo'],
        'fecha' => $datos['fecha'],
        'tipo_formulario' => $datos['tipo_formulario'],
    ];

    switch ($tipo) {
        case 1:
            return array_merge($base, [
                'nombre_completo' => $datos['nombre_receptor'] ?? '',
                'cedula' => $datos['cedula_receptor'] ?? '',
                'correo' => $datos['correo'] ?? '',
                'cargo' => $datos['cargo_receptor'] ?? '',
                'area' => $datos['institucion_receptor'] ?? '',
            ]);

        case 2:
            return array_merge($base, [
                'nombre_completo' => ($datos['solicitante_nombres'] ?? '') . ' ' . ($datos['solicitante_apellidos'] ?? ''),
                'cedula' => $datos['solicitante_nro_doc'] ?? '',
                'correo' => $datos['correo'] ?? '',
                'cargo' => $datos['solicitante_cargo'] ?? '',
                'area' => $datos['institucion'] ?? '',
            ]);

        case 3:
            $nombre = trim(($datos['nombre1'] ?? '') . ' ' . ($datos['nombre2'] ?? '') . ' ' . ($datos['apellido1'] ?? '') . ' ' . ($datos['apellido2'] ?? ''));
            return array_merge($base, [
                'nombre_completo' => $nombre,
                'cedula' => $datos['cedula'] ?? '',
                'correo' => ($datos['correo_usuario'] ?? '') . '@arconel.gob.ec',
                'cargo' => $datos['cargo'] ?? '',
                'area' => $datos['coordinacion_direccion'] ?? '',
            ]);

        case 4:
        case 5:
        case 6:
            return array_merge($base, [
                'nombre_completo' => $datos['nombre_completo'] ?? '',
                'cedula' => $datos['cedula'] ?? '',
                'correo' => $datos['correo'] ?? '',
                'cargo' => $datos['cargo'] ?? $datos['estado_empleado'] ?? '',
                'area' => $datos['area'] ?? '',
            ]);
    }

    return $base;
}
