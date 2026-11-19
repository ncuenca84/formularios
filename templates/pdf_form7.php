<?php
/**
 * PDF Template: Autorizacion de Salida de Equipo de Computo
 * Basado en el acta de asignacion/entrega de equipos en arrendamiento
 */

$colorPrimario = $config['color_primario'] ?? '#003366';
$encabezadoInstitucional = renderPdfEncabezadoInstitucional($config, $meta, $logoDataUri, $logoSecundarioDataUri);
$codigo = htmlspecialchars($datos['codigo']);
$fecha = $datos['fecha'];

$d = array_map(function($v) { return htmlspecialchars($v ?? ''); }, $datos);

function fmtFechaCorta7(string $f): string {
    $ts = strtotime($f);
    return ($f !== '' && $ts !== false) ? date('d/m/Y', $ts) : $f;
}

$motivo = $d['motivo_salida'];
if ($motivo === 'Otro' && !empty($d['motivo_otro'])) {
    $motivo = 'Otro: ' . $d['motivo_otro'];
}
$fechaRetorno = !empty($d['fecha_retorno']) ? fmtFechaCorta7($d['fecha_retorno']) : 'Indefinida';

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 42mm 15mm 28mm 15mm; }
    body { font-family: 'Helvetica', sans-serif; font-size: 9pt; color: #333; line-height: 1.4; }
    .seccion { color: <?= $colorPrimario ?>; font-size: 9pt; font-weight: bold; border-bottom: 2px solid <?= $colorPrimario ?>; padding-bottom: 2px; margin: 10px 0 5px; }
    .dt { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .dt td { padding: 4px 8px; border: 1px solid #ddd; font-size: 8.5pt; }
    .dt .lb { background: #eef2f7; font-weight: 600; color: <?= $colorPrimario ?>; width: 30%; }
    .intro { text-align: justify; font-size: 8.5pt; margin-bottom: 6px; }
    .compromisos { font-size: 7.5pt; text-align: justify; margin: 6px 0; padding: 6px; background: #f8f9fa; border-left: 3px solid <?= $colorPrimario ?>; }
    .compromisos li { margin-bottom: 3px; }
    .firmas-table { width: 100%; margin-top: 15px; border-collapse: collapse; }
    .firmas-table td { width: 50%; text-align: center; vertical-align: bottom; padding: 5px 10px; border: 1px solid #ddd; }
    .firma-linea { border-top: 1px solid #333; padding-top: 3px; font-size: 8pt; margin-top: 70px; }
    .firma-header { background: #eef2f7; font-weight: 600; font-size: 8pt; color: <?= $colorPrimario ?>; padding: 4px; }
</style>
</head>
<body>

<?= $encabezadoInstitucional ?>

<p class="intro">Mediante el presente formulario se registra y autoriza la salida del equipo de computo detallado a continuacion fuera de las instalaciones de la Agencia de Regulacion y Control de Electricidad - ARCONEL, conforme a lo previsto en el acta de asignacion, entrega y recepcion del equipo y en el Reglamento General Sustitutivo para la Administracion, Utilizacion, Manejo y Control de los Bienes e Inventarios del Sector Publico. El usuario debera contar con esta autorizacion antes de retirar el equipo.</p>

<!-- 1. DATOS DEL USUARIO -->
<div class="seccion">1. DATOS DEL USUARIO FINAL</div>
<table class="dt">
    <tr><td class="lb">Apellidos y Nombres:</td><td colspan="3"><?= $d['nombre_completo'] ?></td></tr>
    <tr><td class="lb">Cedula:</td><td><?= $d['cedula'] ?></td><td class="lb">Correo:</td><td><?= $d['correo'] ?></td></tr>
    <tr><td class="lb">Cargo:</td><td><?= $d['cargo'] ?></td><td class="lb">Coordinacion/Direccion:</td><td><?= $d['area'] ?></td></tr>
</table>

<!-- 2. DATOS DEL EQUIPO -->
<div class="seccion">2. DATOS DEL EQUIPO</div>
<table class="dt">
    <tr><td class="lb">Tipo de equipo:</td><td><?= $d['equipo_tipo'] ?></td><td class="lb">Marca:</td><td><?= $d['equipo_marca'] ?></td></tr>
    <tr><td class="lb">Modelo:</td><td><?= $d['equipo_modelo'] ?></td><td class="lb">Numero de serie:</td><td><?= $d['equipo_serie'] ?></td></tr>
    <tr><td class="lb">Cargador:</td><td><?= $d['equipo_cargador'] ?></td><td class="lb">Otros accesorios:</td><td><?= $d['equipo_accesorios'] ?></td></tr>
    <tr><td class="lb">Observaciones:</td><td colspan="3"><?= $d['equipo_observaciones'] ?></td></tr>
</table>

<!-- 3. MOTIVO Y PERIODO -->
<div class="seccion">3. MOTIVO Y PERIODO DE LA SALIDA</div>
<table class="dt">
    <tr><td class="lb">Motivo de la salida:</td><td colspan="3"><?= $motivo ?></td></tr>
    <tr><td class="lb">Fecha de salida:</td><td><?= fmtFechaCorta7($d['fecha_salida']) ?></td><td class="lb">Fecha estimada de retorno:</td><td><?= $fechaRetorno ?></td></tr>
    <tr><td class="lb">Lugar donde estara el equipo:</td><td colspan="3"><?= $d['lugar_destino'] ?></td></tr>
</table>

<!-- 4. COMPROMISOS -->
<div class="seccion">4. COMPROMISOS DEL USUARIO</div>
<div class="compromisos">
<ul style="margin:0;padding-left:15px;">
    <li>Utilizar el equipo exclusivamente para el cumplimiento de sus actividades institucionales.</li>
    <li>Mantener el equipo bajo condiciones adecuadas de cuidado, custodia y conservacion, evitando acciones que puedan ocasionar dano, perdida o afectacion a su funcionamiento.</li>
    <li>No dejar el equipo desatendido en vehiculos, lugares publicos o zonas vulnerables que representen un riesgo previsible de perdida, hurto, robo o dano.</li>
    <li>Cumplir las politicas institucionales de seguridad de la informacion, buenas practicas informaticas y normativas sobre confidencialidad y proteccion de datos vigentes en la ARCONEL.</li>
    <li>No abrir, reparar, modificar el hardware ni alterar sellos de garantia. Toda atencion tecnica debera canalizarse formal y exclusivamente a traves de la DTIC.</li>
    <li>En caso de falla o dano del equipo, reportar inmediatamente la novedad a la DTIC mediante la Mesa de Ayuda (soporte@arconel.gob.ec) y entregar el equipo para su revision.</li>
    <li>En caso de producirse un siniestro de robo o asalto, notificar de forma inmediata a la DTIC y a su jefe inmediato, presentar la denuncia correspondiente ante la Fiscalia General del Estado o autoridad competente dentro de los plazos legales, y remitir copia certificada de la misma.</li>
    <li>Devolver el equipo y la totalidad de sus accesorios a la DTIC al finalizar el periodo de salida, en condiciones fisicas y operativas equivalentes a las registradas, salvo el desgaste normal derivado de su uso adecuado.</li>
</ul>
</div>

<!-- 5. ACEPTACION -->
<div class="seccion">5. ACEPTACION</div>
<p class="intro">El usuario declara conocer y aceptar los compromisos detallados en el presente formulario y asume la responsabilidad del cuidado, buen uso, custodia y conservacion del equipo mientras permanezca fuera de las instalaciones de la ARCONEL. El jefe inmediato autoriza la salida del equipo para el motivo y periodo indicados. Esta autorizacion no modifica las responsabilidades establecidas en el acta de asignacion, entrega y recepcion del equipo.</p>

<!-- FIRMAS -->
<div class="seccion">Firmas</div>
<table class="firmas-table">
    <tr>
        <td class="firma-header">Solicitado por (Usuario final):</td>
        <td class="firma-header">Autorizado por (Jefe inmediato):</td>
    </tr>
    <tr>
        <td style="height:80px;">
            <div class="firma-linea">
                <strong>f. Usuario final</strong><br>
                Nombre: <?= $d['nombre_completo'] ?><br>
                C.C.: <?= $d['cedula'] ?><br>
                Fecha: <?= $fecha ?>
            </div>
        </td>
        <td>
            <div class="firma-linea">
                <strong>f. Director/Coordinador/Jefe de Area</strong><br>
                Nombre: <?= $d['autorizador_nombre'] ?><br>
                C.C.: <?= $d['autorizador_cedula'] ?><br>
                Fecha: <?= $fecha ?>
            </div>
        </td>
    </tr>
</table>

</body>
</html>
<?php
return ob_get_clean();
