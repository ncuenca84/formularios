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

// Validar datos
$errores = validarFormulario($_POST);

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

$datos = [
    'codigo' => $codigo,
    'nombre_completo' => sanitizar($_POST['nombre_completo']),
    'cedula' => sanitizar($_POST['cedula']),
    'correo' => sanitizar($_POST['correo']),
    'cargo' => sanitizar($_POST['cargo']),
    'area' => sanitizar($_POST['area']),
    'fecha' => $fecha,
    'tipo_formulario' => 'Solicitud de Acceso VPN',
];

// Obtener logos como data URI
$logoDataUri = getLogoDataUri('logo_path');
$logoSecundarioDataUri = getLogoDataUri('logo_secundario_path');

// Generar HTML del PDF
$html = require __DIR__ . '/templates/pdf_template.php';

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
$pdfFilename = "solicitud_{$codigo}.pdf";
$pdfPath = "{$pdfDir}/{$pdfFilename}";
file_put_contents($pdfPath, $dompdf->output());

// Guardar datos relativos al PDF
$datos['pdf_path'] = "assets/uploads/pdfs/{$pdfFilename}";

// Guardar en BD
guardarSolicitud($datos);

// Enviar correo al admin
enviarNotificacionAdmin($datos);

// Preparar sesion para respuesta
$_SESSION['mensaje'] = 'Solicitud generada exitosamente.';
$_SESSION['tipo_mensaje'] = 'success';
$_SESSION['codigo_solicitud'] = $codigo;

header('Location: index.php');
exit;
