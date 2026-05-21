<?php
/**
 * PDF Template: Acuerdo de Confidencialidad y no Divulgacion de Informacion con Terceros
 * Texto fiel al documento aprobado (formulario 1 actualizado.docx)
 * Variables: $datos, $config, $meta, $logoDataUri, $logoSecundarioDataUri
 */

$colorPrimario = $config['color_primario'] ?? '#003366';
$piePagina1 = htmlspecialchars($config['pie_pagina_linea1'] ?? '');
$piePagina2 = htmlspecialchars($config['pie_pagina_linea2'] ?? '');
$piePagina3 = htmlspecialchars($config['pie_pagina_linea3'] ?? '');
$encabezadoInstitucional = renderPdfEncabezadoInstitucional($config, $meta, $logoDataUri, $logoSecundarioDataUri);

$oficial = htmlspecialchars($datos['oficial_seguridad'] ?? '');
$cedulaOficial = htmlspecialchars($datos['cedula_oficial'] ?? '');
$nombreReceptor = htmlspecialchars($datos['nombre_receptor'] ?? '');
$cargoReceptor = htmlspecialchars($datos['cargo_receptor'] ?? '');
$cedulaReceptor = htmlspecialchars($datos['cedula_receptor'] ?? '');
$institucionReceptor = htmlspecialchars($datos['institucion_receptor'] ?? '');
$direccionReceptor = htmlspecialchars($datos['direccion_receptor'] ?? '');
$codigo = htmlspecialchars($datos['codigo']);
$fecha = $datos['fecha'];

$meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$mesActual = $meses[(int)date('n') - 1];

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 18mm 18mm 18mm 18mm; }
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9pt; color: #333; line-height: 1.5; }
    .info-bar { width: 100%; font-size: 8pt; margin-bottom: 6px; }
    .lugar-fecha { text-align: right; font-size: 9pt; margin-bottom: 8px; }
    .ct { font-weight: bold; margin: 10px 0 3px; font-size: 9pt; }
    .tx { text-align: justify; font-size: 8.5pt; margin-bottom: 4px; }
    .cita { text-align: justify; font-size: 8pt; margin: 2px 0 4px 15px; font-style: italic; color: #444; }
    .sub { text-align: justify; font-size: 8pt; margin: 1px 0 2px 25px; }
    .firmas { width: 100%; margin-top: 25px; }
    .firmas td { width: 50%; text-align: center; vertical-align: top; padding: 0 10px; }
    .firma-linea { border-top: 1px solid #333; padding-top: 3px; font-size: 8pt; margin-top: 50px; }
    .footer {
        text-align: center; font-size: 7pt; color: #666;
        border-top: 1px solid #999; padding-top: 5px; line-height: 1.5;
        margin-top: 30px;
    }
</style>
</head>
<body>

<?= $encabezadoInstitucional ?>

<table class="info-bar">
    <tr><td style="text-align:left;"><strong>Fecha:</strong> <?= $fecha ?></td><td style="text-align:right;"><strong>Codigo:</strong> <span style="font-family:Courier;font-weight:bold;"><?= $codigo ?></span></td></tr>
</table>

<p class="lugar-fecha">Quito, <?= date('d') ?> de <?= $mesActual ?> de <?= date('Y') ?></p>

<p class="tx">Comparecen al otorgamiento del presente Acuerdo de Confidencialidad y No Divulgacion de Informacion con Terceros, por una parte, la Agencia de Regulacion y Control de Electricidad (ARCONEL), representada por el <?= $oficial ?>, en calidad de Oficial de Seguridad de la Informacion (OSI), a quien en adelante se le denominara como "LA ARCONEL", y, por otra parte, <?= $institucionReceptor ?>, representado por el senor/a <?= $nombreReceptor ?> en calidad de <?= $cargoReceptor ?>, a quien en adelante se le denominara como "EL RECEPTOR".</p>

<p class="tx">Los comparecientes, libre y voluntariamente, en las calidades que intervienen, suscriben el presente Acuerdo de Confidencialidad y de No Divulgacion de Informacion en base a las siguientes clausulas:</p>

<p class="ct">PRIMERA. - ANTECEDENTES:</p>

<p class="tx">1.1 El Art. 66, numeral 19 de la Constitucion de la Republica del Ecuador, publicado en el Registro Oficial No. 449 de 20 de octubre de 2008, dispone: "El derecho a la proteccion de datos de caracter personal, que incluye el acceso y la decision sobre informacion y datos de este caracter, asi como su correspondiente proteccion. La recoleccion, archivo, procesamiento, distribucion o difusion de estos datos o informacion requeriran la autorizacion del titular o el mandato de la ley".</p>

<p class="tx">1.2 El Art. 226 de la Constitucion de la Republica del Ecuador, establece: "Las instituciones del Estado, sus organismos, dependencias, las servidoras o servidores publicos y las personas que actuen en virtud de una potestad estatal ejerceran solamente las competencias y facultades que les sean atribuidas en la Constitucion y la ley. Tendran el deber de coordinar acciones para el cumplimiento de sus fines y hacer efectivo el goce y ejercicio de los derechos reconocidos en la Constitucion".</p>

<p class="tx">1.3 El Art. 4 de la Ley Organica de Transparencia y Acceso a la Informacion Publica, publicada en el Segundo Suplemento del Registro Oficial Nro. 245 del 03 de febrero de 2023, indica:</p>
<p class="cita">"Informacion Confidencial: Informacion o documentacion, en cualquier formato, final o preparatoria, haya sido o no generada por el sujeto obligado, derivada de los derechos personalisimos y fundamentales, y requiere expresa autorizacion de su titular para su divulgacion, que contiene datos que al revelarse, pudiesen danar los siguientes intereses privados: a) El derecho a la privacidad, incluyendo privacidad relacionada a la vida, la salud o la seguridad, asi como el derecho al honor y la propia imagen; b) Los datos personales cuya difusion requiera el consentimiento de sus titulares y deberan ser tratados segun lo dispuesto en la Ley Organica de Proteccion de Datos Personales; c) Los intereses comerciales y economicos legitimos; y, d) Las patentes, derechos de autor y secretos comerciales.".</p>

<p class="tx">1.4 El Art. 10 de la Ley Organica de Proteccion de Datos Personales, publicada en el Quinto Suplemento del Registro Oficial Nro. 459 de 29 de mayo de 2021, establece: "El tratamiento de datos personales debe concebirse sobre la base del debido sigilo y secreto, es decir, no debe tratarse o comunicarse para un fin distinto para el cual fueron recogidos, a menos que concurra una de las causales que habiliten un nuevo tratamiento conforme los supuestos de tratamiento legitimo senalados en esta ley. Para tal efecto, el responsable del tratamiento debera adecuar las medidas tecnicas organizativas para cumplir con este principio."</p>

<p class="tx">1.5 El articulo 36 de la Ley Organica de Proteccion de Datos Personales, publicada en el Quinto Suplemento del Registro Oficial Nro. 459 de 29 de mayo de 2021, establece: "Excepciones de consentimiento para la transferencia o comunicacion de datos personales. - No es necesario contar con el consentimiento del titular para la transferencia o comunicacion de datos personales"</p>

<p class="tx">1.6 El Art. 9 de la Ley de Comercio Electronico, Firmas Electronicas y Mensajes de Datos publicada en la Ley No. 67 de 2002, senala: "Proteccion de datos.- Para la elaboracion, transferencia o utilizacion de bases de datos, obtenidas directa o indirectamente del uso o transmision de mensajes de datos, se requerira el consentimiento expreso del titular de estos, quien podra seleccionar la informacion a compartirse con terceros".</p>

<p class="tx">1.7 En el Tercer Suplemento del Registro Oficial Nro. 509, del 01 de marzo de 2024, se publico el Acuerdo Nro. MINTEL-MINTEL-2024-003 del Ministerio de Telecomunicaciones y de la Sociedad de la Informacion (MINTEL), el cual expide el "Esquema Gubernamental de Seguridad de la Informacion - EGSI" Version 3.0 que se encuentra como Anexo al Acuerdo Ministerial, el cual establece el mecanismo para implementar el Sistema de Gestion de la Seguridad de la Informacion en el Sector Publico.</p>

<p class="tx">1.8 El literal l) de las Recomendaciones para la implementacion, del subnumeral 1.1 Politicas de seguridad de la informacion, numeral 1. Controles organizacionales del Anexo C "Guia para la Implementacion de Controles de Seguridad de la Informacion", del EGSI Version 3.0 menciona que "Para garantizar la vigencia de la politica de seguridad de la informacion y las politicas especificas en la institucion, estas deben ser revisadas al menos una vez al ano y cuando se produzcan cambios significativos a nivel operativo, legal, tecnologico, economico, entre otros; los cuales deben ser documentados, versionados y socializados a las partes interesadas relevantes."</p>

<p class="tx">1.9 El subnumeral 2.6 Acuerdos de confidencialidad o no divulgacion, del numeral 2. Control de personas del Anexo C "Guia para la Implementacion de Controles de Seguridad de la Informacion", del EGSI Version 3.0, se establece en la seccion de Control que: "Los acuerdos de confidencialidad o no divulgacion que reflejen las necesidades de la institucion para la proteccion de la informacion deben ser identificados, documentados, revisados regularmente y firmados por el personal y otras partes interesadas relevantes de acuerdo a la necesidad de la institucion".</p>

<p class="tx">Ademas, en la seccion de "Recomendaciones para la implementacion", se establece lo siguiente:</p>
<p class="sub">a) Los acuerdos de confidencialidad o de no divulgacion deben abordar el requisito de proteger la informacion confidencial utilizando terminos legalmente exigibles.</p>
<p class="sub">b) Los acuerdos de confidencialidad o no divulgacion son aplicables a las partes interesadas y al personal de la institucion.</p>
<p class="sub">c) En funcion de los requisitos de seguridad de la informacion de una institucion, los terminos de los acuerdos deben determinarse teniendo en cuenta el tipo de informacion que se manejare, su nivel de clasificacion, su uso y el acceso permitido por la otra parte.</p>
<p class="sub">d) Para identificar los requisitos para los acuerdos de confidencialidad o no divulgacion, se debe considerar los siguientes elementos:</p>
<p class="sub" style="margin-left:40px;">- Una definicion de la informacion a proteger de acuerdo a la clasificacion de la misma;</p>
<p class="sub" style="margin-left:40px;">- La duracion esperada de un acuerdo de confidencialidad, incluidos los casos en que puede ser necesario mantener la confidencialidad indefinidamente o hasta que la informacion este disponible publicamente;</p>
<p class="sub" style="margin-left:40px;">- Las acciones requeridas cuando se termina un acuerdo;</p>
<p class="sub" style="margin-left:40px;">- Las responsabilidades y acciones de los signatarios para evitar la divulgacion de informacion no autorizada; (...)</p>

<p class="tx">1.13 Mediante Memorando Nro. ARCONEL-ARCONEL-2025-0012-RES del 21 de marzo de 2025, el Director Ejecutivo de la Agencia de Regulacion y Control de Electricidad ratifico la designacion efectuada al Mgs. Hector William Lopez Torres como Oficial de Seguridad de la Informacion de la Agencia, realizada con memorando Nro. ARCONEL-ARCONEL-2024-0400-ME de 26 de septiembre de 2024.</p>

<p class="ct">SEGUNDA. - OBJETO:</p>

<p class="tx">2.1 En virtud de las disposiciones legales invocadas en la clausula anterior, las partes acuerdan que la informacion que sea entregada por LA ARCONEL en virtud de la ejecucion del Acuerdo Nacional de Intercambio de Informacion, mediante replica de informacion automatica que fuera facilitada de sus archivos por cualquier medio, y que, por motivo de la actividad, funciones y servicio, o que por cualquier otra circunstancia o medio llegue a conocimiento de la persona que suscribe este documento, se regira por este Acuerdo.</p>

<p class="tx">2.2 Toda la informacion institucional que se proporcione mediante replica automatica de informacion entre bases de datos es de propiedad de LA ARCONEL, por lo que quien suscribe este documento es consciente en que la informacion que reciba, conozca, acceda, maneje o haga uso es confidencial y su utilizacion sera exclusiva de sus funciones.</p>

<p class="ct">TERCERA. - DECLARATORIA DE CONFIDENCIALIDAD:</p>

<p class="tx">EL RECEPTOR de la informacion, libre y voluntariamente acuerda, declara y acepta que:</p>

<p class="tx">3.1 En lo relativo al uso y proteccion de la informacion institucional, EL RECEPTOR se compromete a considerar que la informacion institucional que reciba, conozca, acceda, maneje o haga uso en el marco de su relacion con LA ARCONEL, sera mantenida y protegida como confidencial. La informacion obtenida en el marco de la Ley Organica de Transparencia y Acceso a la Informacion Publica, unicamente podra ser utilizada para el objeto determinado en su solicitud.</p>

<p class="tx">3.2 EL RECEPTOR reconoce que unicamente utilizara la informacion de LA ARCONEL que llegue a su poder, para los fines que por razon de la naturaleza de su relacion con la Entidad le correspondan, advirtiendo de su deber de confidencialidad, mismo que se extiende a sus servidores, funcionarios y a cualquier persona que, por su relacion con quien suscribe, pueda tener acceso a la informacion, en consecuencia no podran reproducir, modificar, hacer publica o divulgar a terceros la informacion objeto del presente Acuerdo sin previa autorizacion escrita y expresa de LA ARCONEL.</p>

<p class="tx">3.3 EL RECEPTOR adoptara respecto de la informacion objeto de este Acuerdo las mismas medidas de seguridad que adoptaria normalmente respecto a la informacion confidencial de su propia entidad, evitando en la medida de lo posible su perdida, robo o sustraccion.</p>

<p class="tx">3.4 EL RECEPTOR se obliga a guardar y mantener la reserva para la no reproduccion de la informacion institucional confiada en virtud de la ejecucion y cumplimiento del presente Acuerdo. La inobservancia de lo manifestado generara responsabilidad y dara lugar a que LA ARCONEL ejerza las acciones legales civiles, penales y/o administrativas correspondientes.</p>

<p class="tx">EL RECEPTOR no podra revelar credenciales de acceso otorgadas por LA ARCONEL.</p>

<p class="tx">Las disposiciones de este Acuerdo no se aplicaran a la divulgacion de informacion en la medida en que:</p>
<p class="sub">- es exigida por ley;</p>
<p class="sub">- es publica;</p>
<p class="sub">- lo requiera cualquier corte con jurisdiccion competente o cualquier ente judicial, gubernamental, supervisor o regulador competente;</p>
<p class="sub">- si dicha informacion ya es de dominio publico sin que se haya violado este Acuerdo.</p>

<p class="ct">CUARTA. - VIGENCIA:</p>

<p class="tx">Los compromisos establecidos en el presente Acuerdo tendran una duracion indefinida, a partir de la fecha de su suscripcion; sin embargo, podra ser revocada, por autoridad competente, cuando las condiciones legales lo ameriten.</p>

<p class="ct">QUINTA. - CONTROVERSIAS:</p>

<p class="tx">Si se suscitaren divergencias o controversias en la interpretacion o ejecucion del presente Acuerdo, cuando las partes no llegaren a un acuerdo amigable directo, podran recurrir al procedimiento de mediacion, en el Centro de Mediacion de la Procuraduria General del Estado. En caso de que las partes no llegaren a un acuerdo, las partes podran acceder a la via contenciosa administrativa conforme al procedimiento establecido en el Codigo Organico General de Procesos.</p>

<p class="ct">SEXTA. - DOMICILIO PARA NOTIFICACIONES:</p>

<p class="tx">Para efecto de la aplicacion de este acuerdo, los avisos y notificaciones, se realizaran a traves de oficios de la entidad solicitante entregados en la oficina matriz de la institucion:</p>

<p class="tx">Las partes declaran las siguientes direcciones de su matriz:</p>

<p class="tx"><strong>ARCONEL.</strong> Av. Naciones Unidas E7-71 y Shyris, Quito - Ecuador.</p>

<p class="tx"><strong>EL RECEPTOR:</strong></p>
<p class="tx"><?= !empty($direccionReceptor) ? $direccionReceptor : '.............................................................................................................................' ?></p>

<p class="tx">Cualquier cambio de direccion o domicilio debera ser notificado por escrito a la otra parte para que surta sus efectos legales; de lo contrario tendran validez los avisos efectuados a las direcciones antes indicadas.</p>

<p class="ct">SEPTIMA. - RATIFICACION Y ACEPTACION:</p>

<p class="tx">Las partes libre y voluntariamente aceptan y se ratifican en el contenido de todas y cada una de las Clausulas del presente Acuerdo y en consecuencia se comprometen a cumplirlas en toda su extension, en fe de lo cual y para los fines legales correspondientes, suscriben el presente documento de manera electronica.</p>

<p class="tx">Las partes establecen que la fecha de suscripcion del presente Acuerdo es la fecha de la ultima firma electronica estampada al pie del presente instrumento.</p>

<!-- FIRMAS -->
<table class="firmas">
    <tr>
        <td><strong>POR EL RECEPTOR</strong></td>
        <td><strong>POR LA ARCONEL</strong></td>
    </tr>
    <tr>
        <td style="height:60px;"></td>
        <td style="height:60px;"></td>
    </tr>
    <tr>
        <td>
            <div class="firma-linea">
                Nombres: <?= $nombreReceptor ?><br>
                CI: <?= $cedulaReceptor ?><br>
                Cargo: <?= $cargoReceptor ?><br>
                <?= $institucionReceptor ?>
            </div>
        </td>
        <td>
            <div class="firma-linea">
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
    GESTION GENERAL DE PLANIFICACION Y GESTION ESTRATEGICA
</div>

</body>
</html>
<?php
return ob_get_clean();
