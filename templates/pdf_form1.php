<?php
/**
 * PDF Template: Acuerdo de Confidencialidad y no Divulgación de Información con Terceros
 * ACTUALIZADO conforme al documento vigente 2025
 * Variables: $datos, $config, $meta, $logoDataUri, $logoSecundarioDataUri
 */

$colorPrimario = $config['color_primario'] ?? '#003366';
$institucion = htmlspecialchars($config['nombre_institucion'] ?? 'AGENCIA DE REGULACION Y CONTROL DE ELECTRICIDAD');
$piePagina1 = htmlspecialchars($config['pie_pagina_linea1'] ?? '');
$piePagina2 = htmlspecialchars($config['pie_pagina_linea2'] ?? '');
$piePagina3 = htmlspecialchars($config['pie_pagina_linea3'] ?? '');
$encabezadoInstitucional = renderPdfEncabezadoInstitucional($config, $meta, $logoDataUri, $logoSecundarioDataUri);

$oficial = htmlspecialchars($datos['oficial_seguridad'] ?? '');
$cedulaOficial = htmlspecialchars($datos['cedula_oficial'] ?? '');
$cargoOficial = htmlspecialchars($datos['cargo_oficial'] ?? 'Oficial de Seguridad de la Informacion (OSI)');
$receptor = htmlspecialchars($datos['nombre_receptor'] ?? '');
$institucionReceptor = htmlspecialchars($datos['institucion_receptor'] ?? '');
$representante = htmlspecialchars($datos['representante_legal'] ?? '');
$cargoRep = htmlspecialchars($datos['cargo_representante'] ?? '');
$cedulaRep = htmlspecialchars($datos['cedula_representante'] ?? '');
$direccionReceptor = htmlspecialchars($datos['direccion_receptor'] ?? '');
$codigo = htmlspecialchars($datos['codigo']);
$fecha = $datos['fecha'];

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 18mm 16mm 30mm 16mm; }
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9pt; color: #333; line-height: 1.5; }
    .info-bar { width: 100%; font-size: 8pt; margin-bottom: 8px; }
    .lugar-fecha { text-align: right; font-size: 9pt; margin-bottom: 10px; }
    .clausula-titulo { font-weight: bold; color: <?= $colorPrimario ?>; margin: 10px 0 3px; font-size: 9pt; }
    .clausula-texto { text-align: justify; font-size: 8.5pt; margin-bottom: 4px; }
    .clausula-cita { text-align: justify; font-size: 8pt; margin: 2px 0 4px 15px; font-style: italic; color: #444; }
    .sub-item { text-align: justify; font-size: 8pt; margin: 1px 0 2px 30px; }
    .firmas-table { width: 100%; margin-top: 30px; }
    .firmas-table td { width: 50%; text-align: center; vertical-align: bottom; padding: 0 12px; }
    .firma-linea { border-top: 1px solid #333; padding-top: 3px; font-size: 8pt; }
    .firma-espacio { height: 50px; }
    .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 7pt; color: #666; border-top: 1px solid #999; padding-top: 4px; line-height: 1.4; }
</style>
</head>
<body>

<?= $encabezadoInstitucional ?>

<table class="info-bar">
    <tr><td style="text-align:left;"><strong>Fecha:</strong> <?= $fecha ?></td><td style="text-align:right;"><strong>Codigo:</strong> <span style="font-family:Courier;font-weight:bold;color:<?= $colorPrimario ?>;"><?= $codigo ?></span></td></tr>
</table>

<?php
$meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$mesActual = $meses[(int)date('n') - 1];
?>
<p class="lugar-fecha">Quito, <?= date('d') ?> de <?= $mesActual ?> de <?= date('Y') ?></p>

<p class="clausula-texto">Comparecen al otorgamiento del presente Acuerdo de Confidencialidad y No Divulgacion de Informacion con Terceros, por una parte, la Agencia de Regulacion y Control de Electricidad (ARCONEL), representada por el/la <strong><?= $oficial ?></strong>, en calidad de Oficial de Seguridad de la Informacion (OSI), a quien en adelante se le denominara como "LA ARCONEL", y, por otra parte, <strong><?= $institucionReceptor ?></strong>, representado por el senor/a <strong><?= $representante ?></strong> en calidad de <strong><?= $cargoRep ?></strong>, a quien en adelante se le denominara como "EL RECEPTOR".</p>

<p class="clausula-texto">Los comparecientes, libre y voluntariamente, en las calidades que intervienen, suscriben el presente Acuerdo de Confidencialidad y de No Divulgacion de Informacion en base a las siguientes clausulas:</p>

<p class="clausula-titulo">PRIMERA. - ANTECEDENTES:</p>

<p class="clausula-texto">1.1 El Art. 66, numeral 19 de la Constitucion de la Republica del Ecuador, publicado en el Registro Oficial No. 449 de 20 de octubre de 2008, dispone: "El derecho a la proteccion de datos de caracter personal, que incluye el acceso y la decision sobre informacion y datos de este caracter, asi como su correspondiente proteccion. La recoleccion, archivo, procesamiento, distribucion o difusion de estos datos o informacion requeriran la autorizacion del titular o el mandato de la ley".</p>

<p class="clausula-texto">1.2 El Art. 226 de la Constitucion de la Republica del Ecuador, establece: "Las instituciones del Estado, sus organismos, dependencias, las servidoras o servidores publicos y las personas que actuen en virtud de una potestad estatal ejerceran solamente las competencias y facultades que les sean atribuidas en la Constitucion y la ley."</p>

<p class="clausula-texto">1.3 El Art. 4 de la Ley Organica de Transparencia y Acceso a la Informacion Publica, publicada en el Segundo Suplemento del Registro Oficial Nro. 245 del 03 de febrero de 2023, indica:</p>
<p class="clausula-cita">"Informacion Confidencial: Informacion o documentacion, en cualquier formato, final o preparatoria, haya sido o no generada por el sujeto obligado, derivada de los derechos personalisimos y fundamentales, y requiere expresa autorizacion de su titular para su divulgacion."</p>

<p class="clausula-texto">1.4 El Art. 10 de la Ley Organica de Proteccion de Datos Personales, publicada en el Quinto Suplemento del Registro Oficial Nro. 459 de 29 de mayo de 2021, establece: "El tratamiento de datos personales debe concebirse sobre la base del debido sigilo y secreto, es decir, no debe tratarse o comunicarse para un fin distinto para el cual fueron recogidos."</p>

<p class="clausula-texto">1.5 El articulo 36 de la Ley Organica de Proteccion de Datos Personales establece: "Excepciones de consentimiento para la transferencia o comunicacion de datos personales. - No es necesario contar con el consentimiento del titular para la transferencia o comunicacion de datos personales."</p>

<p class="clausula-texto">1.6 El Art. 9 de la Ley de Comercio Electronico, Firmas Electronicas y Mensajes de Datos publicada en la Ley No. 67 de 2002, senala: "Proteccion de datos.- Para la elaboracion, transferencia o utilizacion de bases de datos, obtenidas directa o indirectamente del uso o transmision de mensajes de datos, se requerira el consentimiento expreso del titular de estos."</p>

<p class="clausula-texto">1.7 En el Tercer Suplemento del Registro Oficial Nro. 509, del 01 de marzo de 2024, se publico el Acuerdo Nro. MINTEL-MINTEL-2024-003 del MINTEL, el cual expide el "Esquema Gubernamental de Seguridad de la Informacion - EGSI" Version 3.0.</p>

<p class="clausula-texto">1.8 El literal l) de las Recomendaciones para la implementacion, del subnumeral 1.1 Politicas de seguridad de la informacion, del EGSI Version 3.0 menciona que las politicas deben ser revisadas al menos una vez al ano y cuando se produzcan cambios significativos.</p>

<p class="clausula-texto">1.9 El subnumeral 2.6 Acuerdos de confidencialidad o no divulgacion, del EGSI Version 3.0, establece que los acuerdos deben ser identificados, documentados, revisados regularmente y firmados por el personal y otras partes interesadas relevantes. En las recomendaciones se indica:</p>
<p class="sub-item">a) Los acuerdos deben abordar el requisito de proteger la informacion confidencial utilizando terminos legalmente exigibles.</p>
<p class="sub-item">b) Son aplicables a las partes interesadas y al personal de la institucion.</p>
<p class="sub-item">c) Los terminos deben determinarse teniendo en cuenta el tipo de informacion, su nivel de clasificacion, su uso y el acceso permitido.</p>
<p class="sub-item">d) Se debe considerar: la definicion de la informacion a proteger; la duracion esperada del acuerdo; las acciones requeridas cuando se termina; y las responsabilidades de los signatarios.</p>

<p class="clausula-texto">1.13 Mediante Memorando Nro. ARCONEL-ARCONEL-2025-0012-RES del 21 de marzo de 2025, el Director Ejecutivo de la ARCONEL ratifico la designacion del Mgs. Hector William Lopez Torres como Oficial de Seguridad de la Informacion de la Agencia.</p>

<p class="clausula-titulo">SEGUNDA. - OBJETO:</p>
<p class="clausula-texto">2.1 En virtud de las disposiciones legales invocadas en la clausula anterior, las partes acuerdan que la informacion que sea entregada por LA ARCONEL en virtud de la ejecucion del Acuerdo Nacional de Intercambio de Informacion, mediante replica de informacion automatica que fuera facilitada de sus archivos por cualquier medio, y que, por motivo de la actividad, funciones y servicio, o que por cualquier otra circunstancia o medio llegue a conocimiento de la persona que suscribe este documento, se regira por este Acuerdo.</p>
<p class="clausula-texto">2.2 Toda la informacion institucional que se proporcione mediante replica automatica de informacion entre bases de datos es de propiedad de LA ARCONEL, por lo que quien suscribe este documento es consciente en que la informacion que reciba, conozca, acceda, maneje o haga uso es confidencial y su utilizacion sera exclusiva de sus funciones.</p>

<p class="clausula-titulo">TERCERA. - DECLARATORIA DE CONFIDENCIALIDAD:</p>
<p class="clausula-texto">EL RECEPTOR de la informacion, libre y voluntariamente acuerda, declara y acepta que:</p>
<p class="clausula-texto">3.1 En lo relativo al uso y proteccion de la informacion institucional, EL RECEPTOR se compromete a considerar que la informacion institucional que reciba, conozca, acceda, maneje o haga uso en el marco de su relacion con LA ARCONEL, sera mantenida y protegida como confidencial.</p>
<p class="clausula-texto">3.2 EL RECEPTOR reconoce que unicamente utilizara la informacion de LA ARCONEL que llegue a su poder, para los fines que por razon de la naturaleza de su relacion con la Entidad le correspondan; no podran reproducir, modificar, hacer publica o divulgar a terceros la informacion sin previa autorizacion escrita y expresa de LA ARCONEL.</p>
<p class="clausula-texto">3.3 EL RECEPTOR adoptara respecto de la informacion objeto de este Acuerdo las mismas medidas de seguridad que adoptaria normalmente respecto a la informacion confidencial de su propia entidad, evitando en la medida de lo posible su perdida, robo o sustraccion.</p>
<p class="clausula-texto">3.4 EL RECEPTOR se obliga a guardar y mantener la reserva para la no reproduccion de la informacion institucional confiada. La inobservancia generara responsabilidad y dara lugar a que LA ARCONEL ejerza las acciones legales civiles, penales y/o administrativas correspondientes.</p>
<p class="clausula-texto">EL RECEPTOR no podra revelar credenciales de acceso otorgadas por LA ARCONEL.</p>
<p class="clausula-texto">Las disposiciones de este Acuerdo no se aplicaran a la divulgacion de informacion en la medida en que: es exigida por ley; es publica; lo requiera cualquier corte con jurisdiccion competente o cualquier ente judicial, gubernamental, supervisor o regulador competente; o si dicha informacion ya es de dominio publico sin que se haya violado este Acuerdo.</p>

<p class="clausula-titulo">CUARTA. - VIGENCIA:</p>
<p class="clausula-texto">Los compromisos establecidos en el presente Acuerdo tendran una duracion indefinida, a partir de la fecha de su suscripcion; sin embargo, podra ser revocada, por autoridad competente, cuando las condiciones legales lo ameriten.</p>

<p class="clausula-titulo">QUINTA. - CONTROVERSIAS:</p>
<p class="clausula-texto">Si se suscitaren divergencias o controversias en la interpretacion o ejecucion del presente Acuerdo, cuando las partes no llegaren a un acuerdo amigable directo, podran recurrir al procedimiento de mediacion, en el Centro de Mediacion de la Procuraduria General del Estado. En caso de que las partes no llegaren a un acuerdo, podran acceder a la via contenciosa administrativa conforme al procedimiento establecido en el Codigo Organico General de Procesos.</p>

<p class="clausula-titulo">SEXTA. - DOMICILIO PARA NOTIFICACIONES:</p>
<p class="clausula-texto">Para efecto de la aplicacion de este acuerdo, los avisos y notificaciones se realizaran a traves de oficios de la entidad solicitante entregados en la oficina matriz de la institucion. Las partes declaran las siguientes direcciones:</p>
<p class="clausula-texto"><strong>ARCONEL:</strong> Av. Naciones Unidas E7-71 y Shyris, Quito - Ecuador.</p>
<p class="clausula-texto"><strong>EL RECEPTOR:</strong> <?= !empty($direccionReceptor) ? $direccionReceptor : '_______________________________________________' ?></p>
<p class="clausula-texto">Cualquier cambio de direccion o domicilio debera ser notificado por escrito a la otra parte para que surta sus efectos legales.</p>

<p class="clausula-titulo">SEPTIMA. - RATIFICACION Y ACEPTACION:</p>
<p class="clausula-texto">Las partes libre y voluntariamente aceptan y se ratifican en el contenido de todas y cada una de las Clausulas del presente Acuerdo y en consecuencia se comprometen a cumplirlas en toda su extension, en fe de lo cual y para los fines legales correspondientes, suscriben el presente documento de manera electronica.</p>
<p class="clausula-texto">Las partes establecen que la fecha de suscripcion del presente Acuerdo es la fecha de la ultima firma electronica estampada al pie del presente instrumento.</p>

<table class="firmas-table">
    <tr><td><div class="firma-espacio"></div></td><td><div class="firma-espacio"></div></td></tr>
    <tr>
        <td>
            <div class="firma-linea">
                <strong>POR EL RECEPTOR</strong><br>
                Nombres: <?= $representante ?><br>
                CI: <?= $cedulaRep ?><br>
                Cargo: <?= $cargoRep ?><br>
                <?= $institucionReceptor ?>
            </div>
        </td>
        <td>
            <div class="firma-linea">
                <strong>POR LA ARCONEL</strong><br>
                Nombres del OSI: <?= $oficial ?><br>
                CI: <?= $cedulaOficial ?><br>
                Oficial de Seguridad de la Informacion<br>
                Agencia de Regulacion y Control de Electricidad
            </div>
        </td>
    </tr>
</table>

<div class="footer">
    <em>"Este documento es para uso exclusivo de la ARCONEL. Se prohibe su uso no autorizado".</em><br>
    GESTION GENERAL DE PLANIFICACION Y GESTION ESTRATEGICA | <?= $codigo ?>
</div>

</body>
</html>
<?php
return ob_get_clean();
