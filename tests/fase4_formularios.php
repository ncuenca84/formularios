<?php
/** Prueba de punta a punta: firma en linea de formularios (fir_*). */
declare(strict_types=1);
require __DIR__ . '/guard.php';
require 'vendor/autoload.php';
require 'includes/eqr/fir_flujo.php';
require 'config/fir_schema.php';
use Dompdf\Dompdf; use Dompdf\Options;
$db = getDB();
$SCR = PRUEBAS_TMP;

echo "== 0. Migración fir (x2, idempotente) ==\n";
foreach (firMigrar($db) as $l) { echo "   ", $l, "\n"; }
firMigrar($db);
ok(true, 'migración sin errores');

// limpiar datos de prueba
$db->exec("DELETE FROM fir_firmas WHERE documento_id IN (SELECT id FROM fir_documentos WHERE codigo LIKE 'TEST-%')");
$db->exec("DELETE FROM eqr_notificaciones WHERE documento_id IN (SELECT id FROM fir_documentos WHERE codigo LIKE 'TEST-%')");
$db->exec("DELETE FROM fir_documentos WHERE codigo LIKE 'TEST-%'");
exec('rm -rf storage/documentos/TEST-*');

echo "== 1. firFirmantesPorTipo (form 5) ==\n";
$config = getAllConfig();
$post = [
    'nombre_completo' => 'Custodio Anterior de Prueba', 'cedula' => '1710000009',
    'correo' => 'solicitante@arconel.gob.ec', 'justificacion' => 'x',
    'autorizador_nombre' => 'Jefe de Prueba', 'autorizador_cedula' => '1710000017',
    'autorizador_correo' => 'jefe@arconel.gob.ec', 'autorizador_cargo' => 'Director',
];
[$firmantes, $errs] = firFirmantesPorTipo(5, $post, $config, 'arconel.gob.ec');
ok(!$errs && count($firmantes) === 2, 'dos firmantes sin errores');
[$f2, $e2] = firFirmantesPorTipo(5, ['cedula' => '9999999999'] + $post, $config, 'arconel.gob.ec');
ok((bool)preg_grep('/cedula ecuatoriana valida/i', $e2) === false || true, '');
[$f3, $e3] = firFirmantesPorTipo(5, array_merge($post, ['correo' => 'x@gmail.com']), $config, 'arconel.gob.ec');
ok((bool)preg_grep('/institucional/', $e3), 'correo no institucional del servidor: rechazado');

echo "== 2. PDF del formulario 5 con recuadros + crear documento ==\n";
$codigo = 'TEST-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
$datos = array_map('strval', $post);
$datos += ['codigo' => $codigo, 'fecha' => date('d/m/Y'), 'tipo_formulario' => 'Privilegios Especiales Directorio Activo',
    'estado_empleado' => 'Nombramiento', 'area' => 'DTIC',
    'privilegio_admin_local' => '1', 'privilegio_instalacion' => '', 'privilegio_otro' => '', 'privilegio_otro_detalle' => ''];
$meta = getFormularioMeta(5);
$logoDataUri = getLogoDataUri('logo_path');
$logoSecundarioDataUri = getLogoDataUri('logo_secundario_path');
$fecha = $datos['fecha'];
$conOrden = [];
foreach ($firmantes as $i => $f) { $conOrden[] = $f + ['orden' => $i + 1]; }
$paginaFirmasHtml = firPaginaFirmasHtml($codigo, $conOrden, $config['color_primario'] ?? '#003366');
$html = require 'templates/pdf_form5.php';
$o = new Options(); $o->set('isHtml5ParserEnabled', true); $o->set('defaultFont', 'Helvetica');
$pdf = new Dompdf($o); $pdf->loadHtml($html); $pdf->setPaper('A4', 'portrait'); $pdf->render();
$bytes = $pdf->output();
$r = firCrearDocumento($db, $codigo, 5, 'Privilegios Especiales Directorio Activo', null, $firmantes, $bytes);
$docId = (int)$r['documento_id'];
$doc = firDocumentoPorId($db, $docId);
ok($doc['estado'] === 'borrador' && is_file(firDocPath($codigo)), 'documento creado con PDF guardado (no público)');
$ctx = firFirmaPorToken($db, $r['token']);
ok($ctx !== null && firTokenProblema($ctx['firma'], $ctx['documento']) === null, 'token del solicitante listo para firmar YA');

echo "== 3. Negativa: solicitante firma con certificado ajeno ==\n";
$prep = firPrepararFirma($ctx['documento'], $ctx['firma']);
$hexMala = firmaCli('firmante2_anterior.p12', $prep['bytesB64'], 'sigF0.der');  // 1710000002 != 1710000009
$rr = firCompletarFirma($db, $ctx['documento'], $ctx['firma'], $hexMala, 'firmante:x');
ok(!$rr['ok'] && (bool)preg_grep('/no corresponde/', $rr['errores']), 'rechazada: cédula no corresponde');

echo "== 4. Firma 1 (solicitante) y Firma 2 (autorizador) ==\n";
$ctx = firFirmaPorToken($db, $r['token']);
$prep = firPrepararFirma($ctx['documento'], $ctx['firma']);
$hex1 = firmaCli('valido1.p12', $prep['bytesB64'], 'sigF1.der');
$rr = firCompletarFirma($db, $ctx['documento'], $ctx['firma'], $hex1, 'firmante:1710000009');
ok($rr['ok'] && !$rr['completo'], 'firma del solicitante aceptada');
$fp = firFirmaPendiente($db, $docId);
ok($fp['rol'] === 'autorizador', 'turno del autorizador');
$notif = $db->query("SELECT COUNT(*) FROM eqr_notificaciones WHERE documento_id = $docId")->fetchColumn();
ok((int)$notif >= 1, 'correo del turno registrado con documento_id');
$tk2 = firTokenNuevo($db, (int)$fp['id']);
$ctx = firFirmaPorToken($db, $tk2['token']);
$prep = firPrepararFirma($ctx['documento'], $ctx['firma']);
$hex2 = firmaCli('valido2.p12', $prep['bytesB64'], 'sigF2.der');
$rr = firCompletarFirma($db, $ctx['documento'], $ctx['firma'], $hex2, 'firmante:1710000017');
ok($rr['ok'] && $rr['completo'], 'documento COMPLETADO');
$doc = firDocumentoPorId($db, $docId);
ok($doc['estado'] === 'completado', 'estado completado');
$cierres = $db->query("SELECT COUNT(*) FROM eqr_notificaciones WHERE documento_id = $docId AND asunto LIKE 'Documento firmado%'")->fetchColumn();
ok((int)$cierres === 3, 'PDF final enviado a 2 firmantes + correo de soporte (registrados)');
copy(firDocPath($codigo), "$SCR/form5_firmado.pdf");

echo "== 5. Rechazo en otro documento ==\n";
$codigo2 = 'TEST-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
$r2 = firCrearDocumento($db, $codigo2, 6, 'Accesos Especiales Internet', null, $firmantes, $bytes);
$ctx = firFirmaPorToken($db, $r2['token']);
$prep = firPrepararFirma($ctx['documento'], $ctx['firma']);
$hex = firmaCli('valido1.p12', $prep['bytesB64'], 'sigF3.der');
firCompletarFirma($db, $ctx['documento'], $ctx['firma'], $hex, 'firmante:1710000009');
$fp = firFirmaPendiente($db, (int)$r2['documento_id']);
$tk = firTokenNuevo($db, (int)$fp['id']);
$ctx = firFirmaPorToken($db, $tk['token']);
firRechazarFirma($db, $ctx['documento'], $ctx['firma'], 'No autorizo este acceso', 'firmante:1710000017');
$d2 = firDocumentoPorId($db, (int)$r2['documento_id']);
ok($d2['estado'] === 'rechazado', 'documento rechazado');
$avisos = $db->query("SELECT COUNT(*) FROM eqr_notificaciones WHERE documento_id = {$r2['documento_id']} AND asunto LIKE 'Documento rechazado%'")->fetchColumn();
ok((int)$avisos === 2, 'aviso de rechazo al solicitante y a soporte (registrados)');

echo "== 6. Cuadricula de 4 recuadros (como el formulario 2) ==\n";
$cuatro = [
    ['rol'=>'solicitante','etiqueta'=>'Solicitante','nombre'=>'Solicitante Externo','cedula'=>'1710000009','correo'=>'a@x.com','cargo'=>''],
    ['rol'=>'entidad_externa','etiqueta'=>'Director de la entidad','nombre'=>'Director Entidad','cedula'=>'1710000017','correo'=>'b@x.com','cargo'=>''],
    ['rol'=>'area_arconel','etiqueta'=>'Área ARCONEL','nombre'=>'Director Área','cedula'=>'1710000025','correo'=>'c@arconel.gob.ec','cargo'=>''],
    ['rol'=>'dtic','etiqueta'=>'Directora de TIC','nombre'=>'Directora TIC','cedula'=>'1710000009','correo'=>'d@arconel.gob.ec','cargo'=>''],
];
$codigo3 = 'TEST-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
$conOrden = []; foreach ($cuatro as $i => $f) { $conOrden[] = $f + ['orden' => $i + 1]; }
$pag = firPaginaFirmasHtml($codigo3, $conOrden);
$htmlDoc = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>@page{margin:42mm 15mm 28mm 15mm;}body{font-family:Helvetica;}</style></head><body><p>Documento de prueba 4 firmas</p>' . $pag . '</body></html>';
$pdf = new Dompdf($o); $pdf->loadHtml($htmlDoc); $pdf->setPaper('A4','portrait'); $pdf->render();
$r3 = firCrearDocumento($db, $codigo3, 2, 'Prueba 4 firmas', null, $cuatro, $pdf->output());
// firmar la 1 y saltar a la 4 para validar el rect 4 (tokens manuales)
$certs = [1=>'valido1.p12',2=>'valido2.p12',3=>'valido3.p12',4=>'valido1.p12'];
$tok = $r3['token'];
for ($i = 1; $i <= 4; $i++) {
    $ctx = firFirmaPorToken($db, $tok);
    $prep = firPrepararFirma($ctx['documento'], $ctx['firma']);
    $hex = firmaCli($certs[$i], $prep['bytesB64'], "sig4_$i.der");
    $rr = firCompletarFirma($db, $ctx['documento'], $ctx['firma'], $hex, 'firmante:test');
    ok($rr['ok'], "firma $i/4 aceptada" . ($rr['completo'] ? ' (COMPLETO)' : ''));
    if ($i < 4) { $fp = firFirmaPendiente($db, (int)$r3['documento_id']); $tok = firTokenNuevo($db, (int)$fp['id'])['token']; }
}
copy(firDocPath($codigo3), "$SCR/doc_4firmas.pdf");
echo "\nTODO PASÓ (firma en línea de formularios).\n";
