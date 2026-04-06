<?php
/**
 * PDF Template: Acuerdo de Confidencialidad y no Divulgación de Información con Terceros
 * Variables: $datos, $config, $logoDataUri, $logoSecundarioDataUri
 */

$colorPrimario = $config['color_primario'] ?? '#003366';
$institucion = htmlspecialchars($config['nombre_institucion'] ?? 'AGENCIA DE REGULACION Y CONTROL DE ELECTRICIDAD');
$piePagina1 = htmlspecialchars($config['pie_pagina_linea1'] ?? '');
$piePagina2 = htmlspecialchars($config['pie_pagina_linea2'] ?? '');
$piePagina3 = htmlspecialchars($config['pie_pagina_linea3'] ?? '');

$oficial = htmlspecialchars($datos['oficial_seguridad'] ?? '');
$cargoOficial = htmlspecialchars($datos['cargo_oficial'] ?? '');
$receptor = htmlspecialchars($datos['nombre_receptor'] ?? '');
$representante = htmlspecialchars($datos['representante_legal'] ?? '');
$cargoRep = htmlspecialchars($datos['cargo_representante'] ?? '');
$codigo = htmlspecialchars($datos['codigo']);
$fecha = $datos['fecha'];

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 20mm 18mm 25mm 18mm; }
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10pt; color: #333; line-height: 1.6; }
    .header-table { width: 100%; border-bottom: 3px solid <?= $colorPrimario ?>; padding-bottom: 8px; margin-bottom: 10px; }
    .header-logo { width: 80px; text-align: left; vertical-align: middle; }
    .header-logo img { max-height: 55px; max-width: 70px; }
    .header-center { text-align: center; vertical-align: middle; }
    .header-right { width: 80px; text-align: right; vertical-align: middle; }
    .header-right img { max-height: 55px; max-width: 70px; }
    .inst-name { font-size: 12pt; font-weight: bold; color: <?= $colorPrimario ?>; text-transform: uppercase; }
    .titulo { text-align: center; background: <?= $colorPrimario ?>; color: white; padding: 6px 15px; font-size: 10pt; font-weight: bold; margin: 10px 0; border-radius: 3px; }
    .info-bar { width: 100%; font-size: 8pt; margin-bottom: 10px; }
    .clausula-titulo { font-weight: bold; color: <?= $colorPrimario ?>; margin: 12px 0 4px; font-size: 10pt; }
    .clausula-texto { text-align: justify; font-size: 9.5pt; margin-bottom: 6px; }
    .firmas-table { width: 100%; margin-top: 40px; }
    .firmas-table td { width: 50%; text-align: center; vertical-align: bottom; padding: 0 15px; }
    .firma-linea { border-top: 1px solid #333; padding-top: 4px; font-size: 8.5pt; }
    .firma-espacio { height: 60px; }
    .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 7pt; color: #888; border-top: 2px solid <?= $colorPrimario ?>; padding-top: 4px; }
    .nota { font-size: 7.5pt; color: #666; text-align: center; margin-top: 15px; font-style: italic; }
</style>
</head>
<body>

<table class="header-table">
    <tr>
        <td class="header-logo"><?php if (!empty($logoDataUri)): ?><img src="<?= $logoDataUri ?>"><?php endif; ?></td>
        <td class="header-center"><div class="inst-name"><?= $institucion ?></div></td>
        <td class="header-right"><?php if (!empty($logoSecundarioDataUri)): ?><img src="<?= $logoSecundarioDataUri ?>"><?php endif; ?></td>
    </tr>
</table>

<div class="titulo">ACUERDO DE CONFIDENCIALIDAD Y NO DIVULGACION DE INFORMACION CON TERCEROS</div>

<table class="info-bar">
    <tr><td style="text-align:left;"><strong>Fecha:</strong> <?= $fecha ?></td><td style="text-align:right;"><strong>Codigo:</strong> <span style="font-family:Courier;font-weight:bold;color:<?= $colorPrimario ?>;"><?= $codigo ?></span></td></tr>
</table>

<p class="clausula-texto">Comparecen al otorgamiento del presente Acuerdo de Confidencialidad y No Divulgacion de Informacion con Terceros, por una parte, la Agencia de Regulacion y Control de Electricidad, representada por el/la <strong><?= $oficial ?></strong>, en calidad de <?= $cargoOficial ?>, a quien en adelante se le denominara como "LA ARCONEL", y, por otra parte, <strong><?= $receptor ?></strong>, representado por el/la <strong><?= $representante ?></strong> en calidad de <strong><?= $cargoRep ?></strong>, a quien en adelante se le denominara como "EL RECEPTOR".</p>

<p class="clausula-texto">Los comparecientes, libre y voluntariamente, en las calidades que intervienen, suscriben el presente Acuerdo de Confidencialidad y de No Divulgacion de Informacion en base a las siguientes clausulas:</p>

<p class="clausula-titulo">PRIMERA. - ANTECEDENTES:</p>
<p class="clausula-texto">1.1 El Art. 66, numeral 19 de la Constitucion de la Republica del Ecuador dispone: "El derecho a la proteccion de datos de caracter personal, que incluye el acceso y la decision sobre informacion y datos de este caracter, asi como su correspondiente proteccion."</p>
<p class="clausula-texto">1.2 El Art. 226 de la Constitucion establece que las instituciones del Estado ejerceran solamente las competencias y facultades que les sean atribuidas en la Constitucion y la ley.</p>
<p class="clausula-texto">1.3 El Art. 6 de la Ley Organica de Transparencia y Acceso a la Informacion Publica indica que se considera informacion confidencial aquella informacion publica personal que no esta sujeta al principio de publicidad.</p>
<p class="clausula-texto">1.4 El Esquema Gubernamental de Seguridad de la Informacion (EGSI) version 2.0 establece la obligatoriedad de suscribir acuerdos de confidencialidad antes de que los empleados, contratistas y usuarios de terceras partes tengan acceso a la informacion.</p>

<p class="clausula-titulo">SEGUNDA. - OBJETO:</p>
<p class="clausula-texto">2.1 Las partes acuerdan que la informacion que sea entregada por LA ARCONEL en virtud de la ejecucion de acuerdos de intercambio de informacion se regira por este Acuerdo.</p>
<p class="clausula-texto">2.2 Toda la informacion institucional proporcionada es de propiedad de LA ARCONEL, por lo que quien suscribe este documento es consciente de que la informacion que reciba, conozca, acceda, maneje o haga uso es confidencial y su utilizacion sera exclusiva de sus funciones.</p>

<p class="clausula-titulo">TERCERA. - DECLARATORIA DE CONFIDENCIALIDAD:</p>
<p class="clausula-texto">EL RECEPTOR de la informacion, libre y voluntariamente acuerda, declara y acepta que:</p>
<p class="clausula-texto">3.1 La informacion institucional que reciba sera mantenida y protegida como confidencial.</p>
<p class="clausula-texto">3.2 Unicamente utilizara la informacion para los fines de su relacion con la Entidad; no podra reproducir, modificar, hacer publica o divulgar a terceros sin autorizacion escrita de LA ARCONEL.</p>
<p class="clausula-texto">3.3 Adoptara las mismas medidas de seguridad que adoptaria respecto a su propia informacion confidencial.</p>
<p class="clausula-texto">3.4 Se obliga a guardar y mantener la reserva. La inobservancia generara responsabilidad civil, penal y/o administrativa.</p>
<p class="clausula-texto">3.5 No podra revelar credenciales de acceso otorgadas por LA ARCONEL.</p>

<p class="clausula-titulo">CUARTA. - VIGENCIA:</p>
<p class="clausula-texto">Los compromisos tendran una duracion indefinida a partir de la fecha de suscripcion; podra ser revocada por autoridad competente cuando las condiciones legales lo ameriten.</p>

<p class="clausula-titulo">QUINTA. - CONTROVERSIAS:</p>
<p class="clausula-texto">Si se suscitaren divergencias, las partes podran recurrir a la mediacion en el Centro de Mediacion de la Procuraduria General del Estado, o acceder a la via jurisdiccional.</p>

<p class="clausula-titulo">SEXTA. - DOMICILIO PARA NOTIFICACIONES:</p>
<p class="clausula-texto">ARCONEL: Av. Naciones Unidas E7-71 y Shirys, Quito - Ecuador.</p>
<p class="clausula-texto"><?= $receptor ?>: <?= htmlspecialchars($datos['direccion_receptor'] ?? '________________________________') ?></p>

<p class="clausula-titulo">SEPTIMA. - ACEPTACION:</p>
<p class="clausula-texto">Las partes aceptan el contenido de todas y cada una de las Clausulas y se comprometen a cumplirlas. Se firma electronicamente y sera vigente conforme la ultima fecha de firma.</p>

<table class="firmas-table">
    <tr><td><div class="firma-espacio"></div></td><td><div class="firma-espacio"></div></td></tr>
    <tr>
        <td><div class="firma-linea"><strong>POR EL RECEPTOR</strong><br><?= $receptor ?><br><?= $cargoRep ?></div></td>
        <td><div class="firma-linea"><strong>POR LA ARCONEL</strong><br><?= $oficial ?><br><?= $cargoOficial ?></div></td>
    </tr>
</table>

<div class="nota">Documento generado automaticamente el <?= $fecha ?> | <?= $codigo ?></div>

<div class="footer">
    <?php if (!empty($piePagina1)): ?><?= $piePagina1 ?><br><?php endif; ?>
    <?php if (!empty($piePagina2)): ?><?= $piePagina2 ?><br><?php endif; ?>
    <?php if (!empty($piePagina3)): ?><?= $piePagina3 ?><?php endif; ?>
</div>

</body>
</html>
<?php
return ob_get_clean();
