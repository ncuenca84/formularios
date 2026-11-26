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
ok(eqrEquipoDeCustodio($db, '1710000002', 'serie-f3-1') !== null, 'también funciona con la SERIE del equipo (la que el funcionario conoce)');
ok(eqrEquipoDeCustodio($db, '1710000003', 'TEST-3001') === null, 'cédula ajena: NO revela nada');
ok(eqrEquipoDeCustodio($db, '1710000003', 'SERIE-F3-1') === null, 'serie con cédula ajena: NO revela nada');
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

echo "== 4b. QR de validación en portería ==\n";
$tQr = eqrTramitePorId($db, $tramiteId);
ok(!empty($tQr['verif_token']) && strlen($tQr['verif_token']) === 32, 'la salida nace con token de verificación');
$rQr = eqrEstadoSalidaQr($db, $tQr['verif_token']);
ok($rQr['encontrada'] && $rQr['autorizada'], 'el guardia ve SALIDA AUTORIZADA (completada y vigente)');
$rMal = eqrEstadoSalidaQr($db, str_repeat('0', 32));
ok(!$rMal['encontrada'], 'un token inventado NO revela nada');

echo "== 5. Registrar retorno ==\n";
$tramite = eqrTramitePorId($db, $tramiteId);
eqrRegistrarRetorno($db, $tramite, 'admin:prueba');
$eq = $db->query("SELECT fuera_instalaciones, fecha_retorno_estimada FROM eqr_equipos WHERE id = $equipoId")->fetch();
ok((string)$eq['fuera_instalaciones'] === '0' && $eq['fecha_retorno_estimada'] === null, 'retorno registrado: equipo dentro');
$rQr = eqrEstadoSalidaQr($db, $tQr['verif_token']);
ok(!$rQr['autorizada'] && stripos($rQr['motivo'], 'RETORNO') !== false, 'tras el retorno el QR dice NO AUTORIZADA (ya se usó)');
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

echo "== 7. Cierre automático al vencer el plazo de retorno ==\n";
// Se simula la situación: la salida sigue abierta y la fecha estimada ya pasó.
$ayer = date('Y-m-d', time() - 86400);
$db->exec("UPDATE eqr_tramites SET salida_retorno_registrado = NULL, salida_fecha_retorno = '$ayer' WHERE id = $tramiteId");
$db->exec("UPDATE eqr_equipos SET fuera_instalaciones = 1, fecha_retorno_estimada = '$ayer' WHERE id = $equipoId");
ok(eqrCerrarSalidasVencidas($db) >= 1, 'la salida vencida se detecta y se cierra sola');
$eq = $db->query("SELECT fuera_instalaciones, fecha_retorno_estimada FROM eqr_equipos WHERE id = $equipoId")->fetch();
ok((string)$eq['fuera_instalaciones'] === '0' && $eq['fecha_retorno_estimada'] === null,
   'al vencer el plazo el equipo vuelve SOLO al estado normal');
$t = eqrTramitePorId($db, $tramiteId);
ok($t['salida_retorno_registrado'] !== null, 'el retorno quedó registrado en el trámite');
$u = $db->query("SELECT usuario FROM eqr_bitacora WHERE tramite_id = $tramiteId
                 AND accion = 'retorno_registrado' ORDER BY id DESC LIMIT 1")->fetchColumn();
ok($u === 'sistema:plazo_vencido', 'la bitácora dice que lo registró el sistema por plazo vencido');
$rQr = eqrEstadoSalidaQr($db, $tQr['verif_token']);
ok(!$rQr['autorizada'] && mb_stripos($rQr['motivo'], 'VENCI') !== false,
   'el QR del guardia dice NO AUTORIZADA: VENCIÓ (no "ya se usó")');
$equipo = $db->query("SELECT * FROM eqr_equipos WHERE id = $equipoId")->fetch();
ok(eqrSalidaProblema($db, $equipo) === null, 'puede iniciarse una NUEVA salida sin trámite de ingreso');
ok(eqrCerrarSalidasVencidas($db) === 0, 'no queda nada por cerrar (idempotente)');

echo "== 8. Salida de un BIEN institucional (no rentado) ==\n";
require_once 'config/eqr_schema.php';
eqrMigrar($db);   // idempotente: garantiza el registro interno BIENES-ARCONEL
$sol = ['cedula' => '1710000002', 'nombre' => 'Custodio Anterior de Prueba',
        'correo' => 'custodio@arconel.gob.ec', 'cargo' => 'Analista', 'area' => 'DTIC'];
$rb = eqrEquipoBienInstitucional($db, 'test-bien-0001', $sol);
ok(isset($rb['equipo']) && $rb['equipo']['codigo_interno'] === 'TEST-BIEN-0001',
   'bien registrado (sombra) con el código normalizado a mayúsculas');
ok($rb['contrato']['numero'] === 'BIENES-ARCONEL' && (string)$rb['contrato']['activo'] === '0',
   'bajo el registro interno BIENES-ARCONEL (inactivo: no interfiere con el arrendamiento)');
$idBien = (int)$rb['equipo']['id'];
$rb2 = eqrEquipoBienInstitucional($db, 'TEST-BIEN-0001', $sol);
ok(isset($rb2['equipo']) && (int)$rb2['equipo']['id'] === $idBien, 'una segunda solicitud reutiliza el mismo registro');
$rMalB = eqrEquipoBienInstitucional($db, 'TEST-3001', $sol);
ok(isset($rMalB['error']) && mb_stripos($rMalB['error'], 'RENTADO') !== false,
   'un código de equipo rentado se rechaza con aviso de usar la otra opción');

$datosB = [
    'entrega_cedula' => $sol['cedula'], 'entrega_nombre' => $sol['nombre'],
    'entrega_correo' => $sol['correo'], 'entrega_cargo' => $sol['cargo'], 'entrega_area' => $sol['area'],
    'jefe_cedula' => '1710000003', 'jefe_nombre' => 'Jefe Inmediato de Prueba',
    'jefe_correo' => 'jefe@arconel.gob.ec', 'jefe_cargo' => 'Director de Área',
    'salida_motivo' => 'Comisión de servicios', 'salida_fecha' => date('Y-m-d'),
    'salida_fecha_retorno' => date('Y-m-d', time() + 7 * 86400), 'salida_lugar' => 'Guayaquil',
    'accesorios' => 'Cargador y mouse', 'observaciones' => '',
];
$rS = eqrCrearSalida($db, $rb['equipo'], $rb['contrato'], $datosB, null);
$ctx = eqrFirmaPorToken($db, $rS['token']);
$prep = eqrPrepararFirma($ctx['tramite'], $ctx['firma']);
$hex = firmaCli('firmante2_anterior.p12', $prep['bytesB64'], 'sigB1.der');
$rr = eqrCompletarFirma($db, $ctx['tramite'], $ctx['firma'], $hex, 'firmante:1710000002');
ok($rr['ok'] && !$rr['completo'], 'firma del funcionario aceptada en el acta del bien');
$fJ = eqrFirmaPendiente($db, (int)$rS['tramite_id']);
$tkJ = eqrTokenNuevo($db, (int)$fJ['id']);
$ctx = eqrFirmaPorToken($db, $tkJ['token']);
$prep = eqrPrepararFirma($ctx['tramite'], $ctx['firma']);
$hex = firmaCli('firmante3_nuevo.p12', $prep['bytesB64'], 'sigB2.der');
$rr = eqrCompletarFirma($db, $ctx['tramite'], $ctx['firma'], $hex, 'firmante:1710000003');
ok($rr['ok'] && $rr['completo'], 'firma del jefe: salida del bien COMPLETA');
$eqB = $db->query("SELECT fuera_instalaciones FROM eqr_equipos WHERE id = $idBien")->fetch();
ok((string)$eqB['fuera_instalaciones'] === '1', 'el bien quedó marcado FUERA de las instalaciones');
$tB = eqrTramitePorId($db, (int)$rS['tramite_id']);
$rQrB = eqrEstadoSalidaQr($db, $tB['verif_token']);
ok($rQrB['encontrada'] && $rQrB['autorizada'], 'el QR de portería del bien dice AUTORIZADA');
$rb3 = eqrEquipoBienInstitucional($db, 'TEST-BIEN-0001', $sol);
ok(isset($rb3['error']), 'mientras está fuera, una nueva salida del mismo bien se bloquea');
copy(eqrRutaActaAbs($tB), "$SCR/acta_salida_bien.pdf");
eqrRegistrarRetorno($db, $tB, 'admin:prueba');

copy(eqrRutaActaAbs(eqrTramitePorId($db, $tramiteId)), "$SCR/acta_salida_final.pdf");
echo "\nTODO PASÓ (Fase 3). Acta: $SCR/acta_salida_final.pdf\n";
