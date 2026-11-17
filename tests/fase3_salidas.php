<?php
/** Prueba de punta a punta Fase 3: salida de equipo (formulario 7). */
declare(strict_types=1);
require __DIR__ . '/guard.php';
require 'includes/eqr/flujo.php';
$db = getDB();
$SCR = PRUEBAS_TMP;

// ---- limpiar y preparar ----
$db->exec("DELETE FROM eqr_firmas WHERE tramite_id IN (SELECT id FROM eqr_tramites WHERE equipo_id IN (SELECT id FROM eqr_equipos WHERE codigo_interno LIKE 'TEST-%'))");
$db->exec("DELETE FROM eqr_notificaciones WHERE tramite_id IN (SELECT id FROM eqr_tramites WHERE equipo_id IN (SELECT id FROM eqr_equipos WHERE codigo_interno LIKE 'TEST-%'))");
$db->exec("DELETE FROM eqr_bitacora WHERE equipo_id IN (SELECT id FROM eqr_equipos WHERE codigo_interno LIKE 'TEST-%')");
$db->exec("DELETE FROM eqr_tramites WHERE equipo_id IN (SELECT id FROM eqr_equipos WHERE codigo_interno LIKE 'TEST-%')");
$db->exec("DELETE FROM eqr_equipos WHERE codigo_interno LIKE 'TEST-%'");
$db->exec("DELETE FROM eqr_contratos WHERE numero = 'CONTRATO-PRUEBA-F3'");
exec('rm -rf storage/actas/TRM-*');
$tx = require 'config/eqr_textos_default.php';
$db->prepare("INSERT INTO eqr_contratos (numero, objeto, proveedor, total_equipos,
    admin_nombre, admin_cedula, admin_cargo, admin_correo, admin_calidad,
    texto_compromisos, texto_consideraciones, texto_aceptacion, activo)
    VALUES ('CONTRATO-PRUEBA-F3','P','P',5,'Admin de Prueba','1710000001','Dir','admin@arconel.gob.ec','Administrador', ?, ?, ?, 0)")
   ->execute([$tx['compromisos'], $tx['consideraciones'], $tx['aceptacion']]);
$contratoId = (int)$db->lastInsertId();
$db->prepare("INSERT INTO eqr_equipos (contrato_id, codigo_interno, tipo, marca, modelo, serie, cargador_entregado, estado,
    custodio_cedula, custodio_nombre, custodio_correo, custodio_cargo, custodio_area)
    VALUES (?, 'TEST-3001', 'Computador portátil', 'DELL', 'Latitude 5430', 'SERIE-F3-1', 1, 'asignado',
    '1710000002', 'Custodio Anterior de Prueba', 'custodio@arconel.gob.ec', 'Analista', 'DTIC')")
   ->execute([$contratoId]);
$equipoId = (int)$db->lastInsertId();
echo "== 1. Verificación cédula + código (neutral) ==\n";
ok(eqrEquipoDeCustodio($db, '1710000002', 'TEST-3001') !== null, 'par correcto encontrado');
ok(eqrEquipoDeCustodio($db, '1710000003', 'TEST-3001') === null, 'cédula ajena: NO revela nada');
ok(eqrEquipoDeCustodio($db, '1710000002', 'TEST-9999') === null, 'código equivocado: NO revela nada');
$equipo = $db->query("SELECT * FROM eqr_equipos WHERE id = $equipoId")->fetch();
ok(eqrSalidaProblema($db, $equipo) === null, 'el equipo puede salir');

echo "== 2. Crear salida (custodio firma de inmediato) ==\n";
$contrato = $db->query("SELECT * FROM eqr_contratos WHERE id = $contratoId")->fetch();
$datos = [
    'entrega_cedula' => '1710000002', 'entrega_nombre' => 'Custodio Anterior de Prueba',
    'entrega_correo' => 'custodio@arconel.gob.ec', 'entrega_cargo' => 'Analista', 'entrega_area' => 'DTIC',
    'jefe_cedula' => '1710000003', 'jefe_nombre' => 'Jefe Inmediato de Prueba',
    'jefe_correo' => 'jefe@arconel.gob.ec', 'jefe_cargo' => 'Director de Área',
    'salida_motivo' => 'Teletrabajo', 'salida_fecha' => date('Y-m-d'),
    'salida_fecha_retorno' => date('Y-m-d', time() + 30 * 86400), 'salida_lugar' => 'Domicilio, Quito',
    'accesorios' => 'Cargador', 'observaciones' => 'Sin novedades',
];
$r = eqrCrearSalida($db, $equipo, $contrato, $datos, null);
$tramiteId = (int)$r['tramite_id'];
$tramite = eqrTramitePorId($db, $tramiteId);
ok($tramite['estado'] === 'borrador' && $tramite['tipo'] === 'salida', 'trámite salida en borrador');
$firmas = eqrFirmasDeTramite($db, $tramiteId);
ok(count($firmas) === 2 && $firmas[0]['rol'] === 'custodio' && $firmas[1]['rol'] === 'jefe_inmediato', '2 firmas: custodio y jefe');
ok(is_file(eqrRutaActaAbs($tramite)), 'acta de salida generada');
$ctx = eqrFirmaPorToken($db, $r['token']);
ok($ctx !== null && eqrTokenProblema($ctx['firma'], $ctx['tramite']) === null, 'token del custodio listo para firmar YA');

echo "== 3. Firma del custodio ==\n";
$prep = eqrPrepararFirma($ctx['tramite'], $ctx['firma']);
$hex = firmaCli('firmante2_anterior.p12', $prep['bytesB64'], 'sigS1.der');
$rr = eqrCompletarFirma($db, $ctx['tramite'], $ctx['firma'], $hex, 'firmante:1710000002');
ok($rr['ok'] && !$rr['completo'], 'firma del custodio aceptada');
$f2 = eqrFirmaPendiente($db, $tramiteId);
ok($f2['rol'] === 'jefe_inmediato', 'turno: jefe inmediato (correo registrado, aunque falle SMTP)');
$eqMid = $db->query("SELECT fuera_instalaciones FROM eqr_equipos WHERE id = $equipoId")->fetchColumn();
ok((string)$eqMid === '0', 'el equipo AÚN no está marcado fuera');

echo "== 4. Firma del jefe y cierre ==\n";
$tk2 = eqrTokenNuevo($db, (int)$f2['id']);
$ctx = eqrFirmaPorToken($db, $tk2['token']);
$prep = eqrPrepararFirma($ctx['tramite'], $ctx['firma']);
$hex = firmaCli('firmante3_nuevo.p12', $prep['bytesB64'], 'sigS2.der');
$rr = eqrCompletarFirma($db, $ctx['tramite'], $ctx['firma'], $hex, 'firmante:1710000003');
ok($rr['ok'] && $rr['completo'], 'firma del jefe aceptada: trámite COMPLETO');
$eq = $db->query("SELECT estado, fuera_instalaciones, fecha_retorno_estimada, custodio_cedula FROM eqr_equipos WHERE id = $equipoId")->fetch();
ok((string)$eq['fuera_instalaciones'] === '1', 'equipo marcado FUERA de las instalaciones');
ok($eq['fecha_retorno_estimada'] === $datos['salida_fecha_retorno'], 'fecha de retorno estimada guardada');
ok($eq['custodio_cedula'] === '1710000002' && $eq['estado'] === 'asignado', 'el custodio NO cambió');
$equipo = $db->query("SELECT * FROM eqr_equipos WHERE id = $equipoId")->fetch();
ok(eqrSalidaProblema($db, $equipo) !== null, 'una segunda salida queda bloqueada mientras esté fuera');

echo "== 5. Registrar retorno ==\n";
$tramite = eqrTramitePorId($db, $tramiteId);
eqrRegistrarRetorno($db, $tramite, 'admin:prueba');
$eq = $db->query("SELECT fuera_instalaciones, fecha_retorno_estimada FROM eqr_equipos WHERE id = $equipoId")->fetch();
ok((string)$eq['fuera_instalaciones'] === '0' && $eq['fecha_retorno_estimada'] === null, 'retorno registrado: equipo dentro');
try { eqrRegistrarRetorno($db, eqrTramitePorId($db, $tramiteId), 'admin:prueba'); ok(false, 'no debe registrar dos veces'); }
catch (Throwable $e) { ok(true, 'doble retorno rechazado'); }

echo "== 6. Bloqueo con trámite de custodia abierto ==\n";
$idT = eqrCrearTramite($db, 'devolucion', $equipo, $contrato, [
    'entrega_cedula' => '1710000002', 'entrega_nombre' => 'Custodio Anterior de Prueba',
    'entrega_correo' => 'custodio@arconel.gob.ec', 'entrega_cargo' => 'Analista', 'entrega_area' => 'DTIC',
], 'admin:prueba');
$equipo = $db->query("SELECT * FROM eqr_equipos WHERE id = $equipoId")->fetch();
ok(eqrSalidaProblema($db, $equipo) !== null, 'salida bloqueada: hay una devolución en curso');
eqrDescartarBorrador($db, eqrTramitePorId($db, $idT), 'admin:prueba');

copy(eqrRutaActaAbs(eqrTramitePorId($db, $tramiteId)), "$SCR/acta_salida_final.pdf");
echo "\nTODO PASÓ (Fase 3). Acta: $SCR/acta_salida_final.pdf\n";
