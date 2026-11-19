<?php
/** Prueba de punta a punta de la Fase 2 (traspaso con 3 firmas + negativa). CLI local. */
declare(strict_types=1);
require __DIR__ . '/guard.php';
require 'includes/eqr/flujo.php';
$db = getDB();
$SCR = PRUEBAS_TMP;
require_once 'config/eqr_schema.php';
eqrMigrar($db);   // idempotente: garantiza columnas nuevas (p. ej. fecha_acta)


// ---------- 0. datos de prueba limpios ----------
$db->exec("DELETE FROM eqr_firmas WHERE tramite_id IN (SELECT id FROM eqr_tramites WHERE codigo LIKE 'TRM-%' AND equipo_id IN (SELECT id FROM eqr_equipos WHERE codigo_interno LIKE 'TEST-%'))");
$db->exec("DELETE FROM eqr_bitacora WHERE equipo_id IN (SELECT id FROM eqr_equipos WHERE codigo_interno LIKE 'TEST-%')");
$db->exec("DELETE FROM eqr_notificaciones WHERE tramite_id IN (SELECT id FROM eqr_tramites WHERE equipo_id IN (SELECT id FROM eqr_equipos WHERE codigo_interno LIKE 'TEST-%'))");
$db->exec("DELETE FROM eqr_tramites WHERE equipo_id IN (SELECT id FROM eqr_equipos WHERE codigo_interno LIKE 'TEST-%')");
$db->exec("DELETE FROM eqr_equipos WHERE codigo_interno LIKE 'TEST-%'");
$db->exec("DELETE FROM eqr_contratos WHERE numero = 'CONTRATO-PRUEBA-F2'");
exec("rm -rf storage/actas/TRM-*");

$textos = require 'config/eqr_textos_default.php';
$db->prepare("INSERT INTO eqr_contratos (numero, proceso, objeto, proveedor, total_equipos,
    admin_nombre, admin_cedula, admin_cargo, admin_correo, admin_calidad,
    texto_compromisos, texto_consideraciones, texto_aceptacion, activo)
    VALUES ('CONTRATO-PRUEBA-F2','PROC-PRUEBA','ARRENDAMIENTO DE PRUEBA','PROVEEDOR PRUEBA',5,
    'Administrador Contrato de Prueba','1710000001','Director TIC de Prueba','admin.prueba@arconel.gob.ec',
    'Administrador del Contrato', ?, ?, ?, 0)")
   ->execute([$textos['compromisos'], $textos['consideraciones'], $textos['aceptacion']]);
$contratoId = (int)$db->lastInsertId();
$contrato = $db->query("SELECT * FROM eqr_contratos WHERE id = $contratoId")->fetch();

$db->prepare("INSERT INTO eqr_equipos (contrato_id, codigo_interno, tipo, marca, modelo, procesador, ram,
    almacenamiento, pantalla, serie, estado, custodio_cedula, custodio_nombre, custodio_correo, custodio_cargo, custodio_area)
    VALUES (?, 'TEST-1001', 'Computador portátil', 'DELL', 'Latitude 5430', 'i7', '32 GB', '512 GB', '14\"',
    'SERIE-F2-1', 'asignado', '1710000002', 'Custodio Anterior de Prueba', 'anterior@arconel.gob.ec', 'Analista', 'DTIC')")
   ->execute([$contratoId]);
$equipoId = (int)$db->lastInsertId();
$equipo = $db->query("SELECT * FROM eqr_equipos WHERE id = $equipoId")->fetch();
echo "== Datos de prueba creados ==\n";

// ---------- 1. crear TRASPASO ----------
echo "== 1. Crear traspaso (borrador + acta) ==\n";
$datos = [
    'entrega_cedula' => '1710000002', 'entrega_nombre' => 'Custodio Anterior de Prueba',
    'entrega_correo' => 'anterior@arconel.gob.ec', 'entrega_cargo' => 'Analista', 'entrega_area' => 'DTIC',
    'recibe_cedula' => '1710000003', 'recibe_nombre' => 'Custodio Nuevo de Prueba',
    'recibe_correo' => 'nuevo@arconel.gob.ec', 'recibe_cargo' => 'Especialista', 'recibe_area' => 'DTIC',
    'motivo' => 'Prueba de punta a punta', 'estado_fisico_reportado' => 'Buen estado',
    'accesorios' => 'Cargador', 'observaciones' => '',
    'fecha_acta' => '2026-09-05',   // fecha contractual elegida por el admin
];
$tramiteId = eqrCrearTramite($db, 'traspaso', $equipo, $contrato, $datos, 'admin:prueba');
$tramite = eqrTramitePorId($db, $tramiteId);
ok($tramite['estado'] === 'borrador', 'tramite en borrador');
ok(is_file(eqrRutaActaAbs($tramite)), 'acta generada: ' . $tramite['codigo']);
exec('pdftotext ' . escapeshellarg(eqrRutaActaAbs($tramite)) . ' - 2>/dev/null', $oFA);
ok(strpos(implode("\n", $oFA), '5 de septiembre de 2026') !== false,
   'el cuerpo del acta lleva la FECHA elegida por el admin (no la de hoy)');
unset($oFA);
$firmas = eqrFirmasDeTramite($db, $tramiteId);
ok(count($firmas) === 3 && $firmas[0]['estado'] === 'pendiente' && $firmas[1]['estado'] === 'en_espera', '3 firmas en orden, la 1 pendiente');
try { eqrGenerarActa($tramite, $equipo, $contrato, []); ok(false, 'no debe regenerar'); }
catch (Throwable $e) { ok(true, 'el acta NO se regenera: ' . $e->getMessage()); }

// ---------- 2. PRUEBA NEGATIVA: admin con certificado de cedula ajena ----------
echo "== 2. Negativa: certificado con cédula que no es la del administrador ==\n";
$f1 = eqrFirmaPendiente($db, $tramiteId);
$prep = eqrPrepararFirma($tramite, $f1);
$hexMala = firmaCli('firmante2_anterior.p12', $prep['bytesB64'], 'sig_mala.der');  // cedula 1710000002
$r = eqrCompletarFirma($db, $tramite, $f1, $hexMala, 'admin:prueba');
ok(!$r['ok'], 'rechazada por el servidor');
ok((bool)preg_grep('/no corresponde/', $r['errores']), 'motivo: cédula no corresponde');
$f1b = eqrFirmaPendiente($db, $tramiteId);
ok((int)$f1b['intentos_fallidos'] === 1, 'intento fallido contabilizado');

// ---------- 3. Firma 1: administrador (correcta) ----------
echo "== 3. Firma 1: administrador ==\n";
$prep = eqrPrepararFirma($tramite, $f1b);
$hex1 = firmaCli('firmante1_admin.p12', $prep['bytesB64'], 'sig1.der');  // cedula 1710000001
$r = eqrCompletarFirma($db, $tramite, $f1b, $hex1, 'admin:prueba');
ok($r['ok'] && !$r['completo'], 'firma 1 aceptada, circuito sigue');
$tramite = eqrTramitePorId($db, $tramiteId);
ok($tramite['estado'] === 'en_firma', 'tramite en firma');
$f2 = eqrFirmaPendiente($db, $tramiteId);
ok($f2 && $f2['rol'] === 'custodio_anterior', 'turno: custodio anterior');
$notifFallida = $db->query("SELECT COUNT(*) FROM eqr_notificaciones WHERE tramite_id = $tramiteId AND resultado='fallido'")->fetchColumn();
ok((int)$notifFallida >= 1, 'correo registrado como fallido (SMTP sin configurar), tramite sigue vivo');

// ---------- 4. Firma 2 por TOKEN (custodio anterior) ----------
echo "== 4. Firma 2: custodio anterior vía token ==\n";
$tk2 = eqrTokenNuevo($db, (int)$f2['id']);   // equivale a "reenviar enlace"
$ctx = eqrFirmaPorToken($db, $tk2['token']);
ok($ctx !== null && eqrTokenProblema($ctx['firma'], $ctx['tramite']) === null, 'token válido y es su turno');
$prep = eqrPrepararFirma($ctx['tramite'], $ctx['firma']);
$hex2 = firmaCli('firmante2_anterior.p12', $prep['bytesB64'], 'sig2.der');
$r = eqrCompletarFirma($db, $ctx['tramite'], $ctx['firma'], $hex2, 'firmante:1710000002');
ok($r['ok'] && !$r['completo'], 'firma 2 aceptada');
$eqMid = $db->query("SELECT custodio_cedula FROM eqr_equipos WHERE id = $equipoId")->fetchColumn();
ok($eqMid === '1710000002', 'el custodio TODAVÍA no cambia (regla clave)');
$ctx2 = eqrFirmaPorToken($db, $tk2['token']);
ok($ctx2 !== null && eqrTokenProblema($ctx2['firma'], $ctx2['tramite']) !== null, 'el enlace sigue vivo pero ya NO permite firmar de nuevo');

// ---------- 5. Firma 3 (nuevo custodio) y cierre ----------
echo "== 5. Firma 3: nuevo custodio y cierre ==\n";
$f3 = eqrFirmaPendiente($db, $tramiteId);
$tk3 = eqrTokenNuevo($db, (int)$f3['id']);
$ctx = eqrFirmaPorToken($db, $tk3['token']);
$prep = eqrPrepararFirma($ctx['tramite'], $ctx['firma']);
$hex3 = firmaCli('firmante3_nuevo.p12', $prep['bytesB64'], 'sig3.der');
$r = eqrCompletarFirma($db, $ctx['tramite'], $ctx['firma'], $hex3, 'firmante:1710000003');
ok($r['ok'] && $r['completo'], 'firma 3 aceptada y tramite COMPLETO');
$tramite = eqrTramitePorId($db, $tramiteId);
ok($tramite['estado'] === 'completado', 'estado completado');
$eqFin = $db->query("SELECT estado, custodio_cedula, custodio_nombre FROM eqr_equipos WHERE id = $equipoId")->fetch();
ok($eqFin['custodio_cedula'] === '1710000003' && $eqFin['estado'] === 'asignado', 'custodio cambiado al NUEVO al completarse');
$bit = $db->query("SELECT COUNT(*) FROM eqr_bitacora WHERE tramite_id = $tramiteId AND accion='tramite_completado'")->fetchColumn();
ok((int)$bit === 1, 'bitácora del cierre');
$cierres = $db->query("SELECT COUNT(*) FROM eqr_notificaciones WHERE tramite_id = $tramiteId AND asunto LIKE 'Trámite completado%'")->fetchColumn();
ok((int)$cierres === 3, 'alerta de cierre a admin + 2 firmantes (registradas)');
copy(eqrRutaActaAbs($tramite), "$SCR/acta_traspaso_final.pdf");
echo "  acta final: $SCR/acta_traspaso_final.pdf (" . filesize("$SCR/acta_traspaso_final.pdf") . " bytes)\n";

// ---------- 6. DEVOLUCION con rechazo ----------
echo "== 6. Devolución rechazada por el custodio ==\n";
$equipo = $db->query("SELECT * FROM eqr_equipos WHERE id = $equipoId")->fetch();
$datosDev = [
    'entrega_cedula' => '1710000003', 'entrega_nombre' => 'Custodio Nuevo de Prueba',
    'entrega_correo' => 'nuevo@arconel.gob.ec', 'entrega_cargo' => 'Especialista', 'entrega_area' => 'DTIC',
];
$devId = eqrCrearTramite($db, 'devolucion', $equipo, $contrato, $datosDev, 'admin:prueba');
$dev = eqrTramitePorId($db, $devId);
$fA = eqrFirmaPendiente($db, $devId);
$prep = eqrPrepararFirma($dev, $fA);
$hexA = firmaCli('firmante1_admin.p12', $prep['bytesB64'], 'sigA.der');
$r = eqrCompletarFirma($db, $dev, $fA, $hexA, 'admin:prueba');
if (!$r["ok"]) { print_r($r); }
ok($r["ok"], "firma admin de la devolución aceptada");
$fB = eqrFirmaPendiente($db, $devId);
$tkB = eqrTokenNuevo($db, (int)$fB['id']);
$ctx = eqrFirmaPorToken($db, $tkB['token']);
eqrRechazarFirma($db, $ctx['tramite'], $ctx['firma'], 'No estoy de acuerdo con el estado reportado', 'firmante:1710000003');
$dev = eqrTramitePorId($db, $devId);
ok($dev['estado'] === 'rechazado', 'tramite devolución RECHAZADO');
$eqSigue = $db->query("SELECT estado, custodio_cedula FROM eqr_equipos WHERE id = $equipoId")->fetch();
ok($eqSigue['custodio_cedula'] === '1710000003' && $eqSigue['estado'] === 'asignado', 'el equipo sigue con su custodio');
$avisoAdmin = $db->query("SELECT COUNT(*) FROM eqr_notificaciones WHERE tramite_id = $devId AND asunto LIKE 'Trámite rechazado%'")->fetchColumn();
ok((int)$avisoAdmin === 1, 'aviso de rechazo al administrador (registrado)');

// ---------- 7. Equipo con tramite abierto no admite otro ----------
echo "== 7. Bloqueo de doble trámite ==\n";
$ab = eqrTramiteAbierto($db, $equipoId);
ok($ab === null, 'tras cierre/rechazo no quedan trámites abiertos');

echo "\nTODO PASÓ.\n";
