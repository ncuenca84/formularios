<?php
/**
 * Template HTML del PDF institucional.
 * Variables disponibles: $datos, $config, $logoDataUri, $logoSecundarioDataUri
 */

$nombre = htmlspecialchars($datos['nombre_completo']);
$cedula = htmlspecialchars($datos['cedula']);
$correo = htmlspecialchars($datos['correo']);
$cargo = htmlspecialchars($datos['cargo']);
$area = htmlspecialchars($datos['area']);
$codigo = htmlspecialchars($datos['codigo']);
$fecha = $datos['fecha'];

$institucion = htmlspecialchars($config['nombre_institucion'] ?? 'INSTITUCION PUBLICA');
$subtitulo = htmlspecialchars($config['subtitulo_institucion'] ?? '');
$titulo = htmlspecialchars($config['titulo_formulario'] ?? 'SOLICITUD DE ACCESO VPN');
$colorPrimario = $config['color_primario'] ?? '#003366';
$colorSecundario = $config['color_secundario'] ?? '#0066cc';

$piePagina1 = htmlspecialchars($config['pie_pagina_linea1'] ?? '');
$piePagina2 = htmlspecialchars($config['pie_pagina_linea2'] ?? '');
$piePagina3 = htmlspecialchars($config['pie_pagina_linea3'] ?? '');
$encabezadoExtra = htmlspecialchars($config['encabezado_extra'] ?? '');

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page {
        margin: 20mm 15mm 25mm 15mm;
    }
    body {
        font-family: 'Helvetica', 'Arial', sans-serif;
        font-size: 11pt;
        color: #333;
        line-height: 1.5;
    }

    .header-table {
        width: 100%;
        border-bottom: 3px solid <?= $colorPrimario ?>;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }
    .header-logo { width: 80px; text-align: left; vertical-align: middle; }
    .header-logo img { max-height: 60px; max-width: 75px; }
    .header-center { text-align: center; vertical-align: middle; }
    .header-right { width: 80px; text-align: right; vertical-align: middle; }
    .header-right img { max-height: 60px; max-width: 75px; }

    .institucion-nombre {
        font-size: 14pt;
        font-weight: bold;
        color: <?= $colorPrimario ?>;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .institucion-subtitulo {
        font-size: 9pt;
        color: #666;
        margin-top: 2px;
    }

    .titulo-formulario {
        text-align: center;
        background: <?= $colorPrimario ?>;
        color: white;
        padding: 8px 20px;
        font-size: 12pt;
        font-weight: bold;
        letter-spacing: 1px;
        margin: 15px 0;
        border-radius: 4px;
    }

    .info-bar {
        width: 100%;
        margin-bottom: 15px;
        font-size: 9pt;
    }
    .info-bar td { padding: 3px 5px; }
    .info-codigo {
        font-weight: bold;
        color: <?= $colorPrimario ?>;
        font-size: 10pt;
        font-family: 'Courier New', monospace;
    }

    .datos-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .datos-table th {
        background: <?= $colorPrimario ?>;
        color: white;
        padding: 8px 12px;
        text-align: left;
        font-size: 10pt;
        font-weight: 600;
    }
    .datos-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #ddd;
        font-size: 10.5pt;
    }
    .datos-table tr:nth-child(even) { background: #f8f9fa; }
    .datos-table .label-cell {
        width: 35%;
        font-weight: 600;
        color: <?= $colorPrimario ?>;
        background: #eef2f7;
    }
    .datos-table .value-cell { width: 65%; }

    .seccion-titulo {
        color: <?= $colorPrimario ?>;
        font-size: 11pt;
        font-weight: bold;
        border-bottom: 2px solid <?= $colorSecundario ?>;
        padding-bottom: 4px;
        margin: 20px 0 10px;
    }

    .declaracion {
        font-size: 9.5pt;
        text-align: justify;
        color: #555;
        margin: 15px 0;
        padding: 10px;
        background: #f8f9fa;
        border-left: 3px solid <?= $colorPrimario ?>;
    }

    .firmas-table {
        width: 100%;
        margin-top: 50px;
    }
    .firmas-table td {
        width: 50%;
        text-align: center;
        vertical-align: bottom;
        padding: 0 20px;
    }
    .firma-linea {
        border-top: 1px solid #333;
        padding-top: 5px;
        font-size: 9pt;
        color: #333;
    }
    .firma-espacio { height: 70px; }

    .footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 7.5pt;
        color: #888;
        border-top: 2px solid <?= $colorPrimario ?>;
        padding-top: 5px;
        line-height: 1.4;
    }

    .nota-importante {
        font-size: 8.5pt;
        color: #666;
        text-align: center;
        margin-top: 25px;
        font-style: italic;
    }
</style>
</head>
<body>

<!-- ENCABEZADO -->
<table class="header-table">
    <tr>
        <td class="header-logo">
            <?php if (!empty($logoDataUri)): ?>
                <img src="<?= $logoDataUri ?>" alt="Logo">
            <?php endif; ?>
        </td>
        <td class="header-center">
            <div class="institucion-nombre"><?= $institucion ?></div>
            <?php if (!empty($subtitulo)): ?>
                <div class="institucion-subtitulo"><?= $subtitulo ?></div>
            <?php endif; ?>
            <?php if (!empty($encabezadoExtra)): ?>
                <div class="institucion-subtitulo"><?= $encabezadoExtra ?></div>
            <?php endif; ?>
        </td>
        <td class="header-right">
            <?php if (!empty($logoSecundarioDataUri)): ?>
                <img src="<?= $logoSecundarioDataUri ?>" alt="Logo Secundario">
            <?php endif; ?>
        </td>
    </tr>
</table>

<!-- TITULO -->
<div class="titulo-formulario"><?= $titulo ?></div>

<!-- INFO DE SOLICITUD -->
<table class="info-bar">
    <tr>
        <td style="text-align:left;"><strong>Fecha:</strong> <?= $fecha ?></td>
        <td style="text-align:right;">
            <strong>Codigo:</strong> <span class="info-codigo"><?= $codigo ?></span>
        </td>
    </tr>
</table>

<!-- DATOS DEL SOLICITANTE -->
<div class="seccion-titulo">DATOS DEL SOLICITANTE</div>
<table class="datos-table">
    <tr>
        <td class="label-cell">Nombre Completo</td>
        <td class="value-cell"><?= $nombre ?></td>
    </tr>
    <tr>
        <td class="label-cell">Numero de Cedula</td>
        <td class="value-cell"><?= $cedula ?></td>
    </tr>
    <tr>
        <td class="label-cell">Correo Electronico</td>
        <td class="value-cell"><?= $correo ?></td>
    </tr>
    <tr>
        <td class="label-cell">Cargo</td>
        <td class="value-cell"><?= $cargo ?></td>
    </tr>
    <tr>
        <td class="label-cell">Area / Departamento</td>
        <td class="value-cell"><?= $area ?></td>
    </tr>
</table>

<!-- DECLARACION -->
<div class="seccion-titulo">DECLARACION</div>
<div class="declaracion">
    Yo, <strong><?= $nombre ?></strong>, con cedula de identidad No. <strong><?= $cedula ?></strong>,
    solicito formalmente el acceso a los recursos indicados en este formulario.
    Me comprometo a utilizar los recursos asignados exclusivamente para fines institucionales,
    cumpliendo con las politicas de seguridad de la informacion vigentes.
    Acepto la responsabilidad sobre el uso de las credenciales y accesos otorgados.
</div>

<!-- FIRMAS -->
<div class="seccion-titulo">FIRMAS DE AUTORIZACION</div>
<table class="firmas-table">
    <tr>
        <td><div class="firma-espacio"></div></td>
        <td><div class="firma-espacio"></div></td>
    </tr>
    <tr>
        <td>
            <div class="firma-linea">
                <strong>SOLICITANTE</strong><br>
                <?= $nombre ?><br>
                C.I.: <?= $cedula ?>
            </div>
        </td>
        <td>
            <div class="firma-linea">
                <strong>JEFE INMEDIATO / AUTORIZADOR</strong><br>
                Nombre: _________________________<br>
                Cargo: __________________________
            </div>
        </td>
    </tr>
</table>

<div class="nota-importante">
    Este documento debe ser firmado digitalmente mediante FirmaEC y enviado al correo institucional correspondiente.<br>
    Documento generado automaticamente el <?= $fecha ?> | <?= $codigo ?>
</div>

<!-- PIE DE PAGINA -->
<div class="footer">
    <?php if (!empty($piePagina1)): ?><?= $piePagina1 ?><br><?php endif; ?>
    <?php if (!empty($piePagina2)): ?><?= $piePagina2 ?><br><?php endif; ?>
    <?php if (!empty($piePagina3)): ?><?= $piePagina3 ?><?php endif; ?>
</div>

</body>
</html>
<?php
return ob_get_clean();
