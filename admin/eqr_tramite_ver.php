<?php
/**
 * Detalle de un tramite: estado de cada firma, acta, y acciones del admin:
 * retomar la firma (borrador), reenviar enlace, anular, descartar borrador.
 */
require __DIR__ . '/eqr_common.php';
if (!eqrMigrado()) { header('Location: eqr_migrar.php'); exit; }
require_once __DIR__ . '/../includes/eqr/flujo.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$tramite = eqrTramitePorId($db, $id);
if (!$tramite) { eqrFlash('Trámite no encontrado.', 'error'); header('Location: eqr_tramites.php'); exit; }

$usuario = 'admin:' . ($_SESSION['admin_nombre'] ?? 'desconocido');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    eqrCsrfVerificar();
    $accion = (string)($_POST['accion'] ?? '');
    try {
        if ($accion === 'reenviar') {
            $r = eqrReenviarEnlace($db, $tramite, $usuario);
            eqrFlash($r['ok'] ? 'Enlace reenviado a ' . $r['correo'] . ' (el anterior quedó sin efecto).'
                              : 'No se pudo reenviar: ' . $r['error'], $r['ok'] ? 'success' : 'error');
        } elseif ($accion === 'anular') {
            $motivo = trim((string)($_POST['motivo'] ?? ''));
            if (mb_strlen($motivo) < 5) {
                eqrFlash('Escriba el motivo de la anulación.', 'error');
            } elseif (!in_array($tramite['estado'], ['borrador', 'en_firma'], true)) {
                eqrFlash('Solo se puede anular un trámite en borrador o en firma.', 'error');
            } else {
                eqrAnularTramite($db, $tramite, $motivo, $usuario);
                eqrFlash('Trámite anulado. Los enlaces de firma pendientes quedaron sin efecto.');
            }
        } elseif ($accion === 'descartar') {
            eqrDescartarBorrador($db, $tramite, $usuario);
            eqrFlash('Borrador descartado.');
            header('Location: eqr_tramites.php?tab=borrador');
            exit;
        } elseif ($accion === 'retorno') {
            eqrRegistrarRetorno($db, $tramite, $usuario);
            eqrFlash('Retorno registrado: el equipo ya no consta como fuera de las instalaciones.');
        }
    } catch (Throwable $ex) {
        eqrFlash('Error: ' . $ex->getMessage(), 'error');
    }
    header('Location: eqr_tramite_ver.php?id=' . $id);
    exit;
}

$firmas = eqrFirmasDeTramite($db, $id);
$pendiente = eqrFirmaPendiente($db, $id);

$bit = $db->prepare("SELECT * FROM eqr_bitacora WHERE tramite_id = ? ORDER BY id DESC LIMIT 30");
$bit->execute([$id]);
$bitacora = $bit->fetchAll();

$notifs = $db->prepare("SELECT * FROM eqr_notificaciones WHERE tramite_id = ? ORDER BY id DESC LIMIT 20");
$notifs->execute([$id]);
$notifs = $notifs->fetchAll();

$tipos = ['asignacion' => 'Asignación', 'traspaso' => 'Traspaso', 'devolucion' => 'Devolución',
          'salida' => 'Salida', 'carga_inicial' => 'Carga inicial'];
$rolesLegibles = [
    'administrador' => 'Administrador del contrato', 'custodio_anterior' => 'Custodio anterior (entrega)',
    'nuevo_custodio' => 'Nuevo custodio (recibe)', 'custodio' => 'Custodio que devuelve',
    'jefe_inmediato' => 'Jefe inmediato',
];
$estadosFirma = ['en_espera' => ['secondary', 'En espera'], 'pendiente' => ['warning text-dark', 'Pendiente (su turno)'],
                 'firmado' => ['success', 'Firmado'], 'rechazado' => ['danger', 'Rechazado']];
$estadosTramite = ['borrador' => ['secondary', 'Borrador'], 'en_firma' => ['warning text-dark', 'En firma'],
                   'completado' => ['success', 'Completado'], 'rechazado' => ['danger', 'Rechazado'],
                   'anulado' => ['dark', 'Anulado']];
[$clsT, $txtT] = $estadosTramite[$tramite['estado']] ?? ['light', $tramite['estado']];

eqrHeader('Trámite ' . $tramite['codigo'], 'tramites');
?>
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-1"><?= e($tipos[$tramite['tipo']] ?? $tramite['tipo']) ?> —
            <span class="badge bg-<?= $clsT ?>"><?= e($txtT) ?></span></h5>
        <div class="text-muted small">Creado el <?= e(substr((string)$tramite['created_at'], 0, 16)) ?>
            por <?= e($tramite['creado_por']) ?></div>
    </div>
    <div>
        <?php if (is_file(eqrRutaActaAbs($tramite))): ?>
            <a class="btn btn-outline-primary" href="eqr_acta_ver.php?id=<?= $id ?>">
                <i class="bi bi-download"></i> Descargar acta</a>
            <a class="btn btn-outline-secondary" href="eqr_acta_ver.php?id=<?= $id ?>&inline=1" target="_blank">
                <i class="bi bi-eye"></i> Ver acta</a>
        <?php endif; ?>
        <a class="btn btn-outline-secondary" href="eqr_tramites.php"><i class="bi bi-arrow-left"></i> Bandeja</a>
    </div>
</div>

<div class="row g-3">
<div class="col-lg-7">
    <div class="card p-3 mb-3">
        <h6>Datos del trámite</h6>
        <table class="table table-sm mb-0">
            <tr><th style="width:32%">Equipo</th>
                <td><?= e($tramite['equipo_codigo']) ?> — <?= e($tramite['equipo_marca']) ?>
                    <?= e($tramite['equipo_modelo']) ?> (serie <?= e($tramite['equipo_serie']) ?>)
                    <a class="small" href="eqr_equipo_ficha.php?id=<?= (int)$tramite['equipo_id'] ?>">ficha</a></td></tr>
            <?php if ($tramite['entrega_nombre']): ?>
                <tr><th>Entrega</th><td><?= e($tramite['entrega_nombre']) ?> (C.C. <?= e($tramite['entrega_cedula']) ?>)
                    — <?= e((string)$tramite['entrega_correo']) ?></td></tr>
            <?php endif; ?>
            <?php if ($tramite['recibe_nombre']): ?>
                <tr><th>Recibe</th><td><?= e($tramite['recibe_nombre']) ?> (C.C. <?= e($tramite['recibe_cedula']) ?>)
                    — <?= e((string)$tramite['recibe_correo']) ?></td></tr>
            <?php endif; ?>
            <tr><th>Administrador del contrato</th>
                <td><?= e($tramite['admin_contrato_nombre']) ?> (C.C. <?= e($tramite['admin_contrato_cedula']) ?>)</td></tr>
            <?php if ($tramite['tipo'] === 'salida'): ?>
                <tr><th>Jefe inmediato (autoriza)</th>
                    <td><?= e((string)$tramite['jefe_nombre']) ?> (C.C. <?= e((string)$tramite['jefe_cedula']) ?>)
                        — <?= e((string)$tramite['jefe_correo']) ?></td></tr>
                <tr><th>Motivo de la salida</th><td><?= e((string)$tramite['salida_motivo']) ?></td></tr>
                <tr><th>Período</th>
                    <td>Sale el <?= e((string)$tramite['salida_fecha']) ?> —
                        retorno estimado: <?= e((string)($tramite['salida_fecha_retorno'] ?: 'indefinido')) ?>
                        <?= $tramite['salida_lugar'] ? ' — Lugar: ' . e($tramite['salida_lugar']) : '' ?></td></tr>
                <?php if ($tramite['salida_retorno_registrado']): ?>
                    <tr><th>Retorno registrado</th>
                        <td class="text-success"><?= e(substr((string)$tramite['salida_retorno_registrado'], 0, 16)) ?></td></tr>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($tramite['motivo']): ?><tr><th>Motivo</th><td><?= e($tramite['motivo']) ?></td></tr><?php endif; ?>
            <?php if ($tramite['motivo_rechazo_anulacion']): ?>
                <tr><th>Motivo del rechazo / anulación</th>
                    <td class="text-danger"><?= e($tramite['motivo_rechazo_anulacion']) ?></td></tr>
            <?php endif; ?>
            <?php if ($tramite['hash_actual']): ?>
                <tr><th>SHA-256 del acta</th><td><code class="small"><?= e($tramite['hash_actual']) ?></code></td></tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="card p-3 mb-3">
        <h6>Firmas (en orden)</h6>
        <?php foreach ($firmas as $f): [$cls, $txt] = $estadosFirma[$f['estado']] ?? ['light', $f['estado']]; ?>
            <div class="border rounded p-2 mb-2">
                <span class="badge bg-<?= $cls ?>"><?= e($txt) ?></span>
                <strong><?= (int)$f['orden'] ?>. <?= e($rolesLegibles[$f['rol']] ?? $f['rol']) ?>:</strong>
                <?= e($f['nombre']) ?> (C.C. <?= e($f['cedula']) ?>)
                <div class="small text-muted">
                    <?php if ($f['estado'] === 'firmado'): ?>
                        Firmado el <?= e((string)$f['fecha_firma']) ?> —
                        certificado: <?= e((string)$f['cert_cn']) ?> (<?= e((string)$f['cert_emisor']) ?>)
                    <?php elseif ($f['estado'] === 'pendiente'): ?>
                        Correo: <?= e($f['correo']) ?> ·
                        <?= $f['token_expira'] ? 'El enlace vence el ' . e(substr((string)$f['token_expira'], 0, 16)) : 'Pendiente de firma desde el panel (administrador).' ?>
                    <?php elseif ($f['estado'] === 'rechazado'): ?>
                        Motivo: <?= e((string)$f['motivo_rechazo']) ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card p-3 mb-3">
        <h6>Acciones</h6>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($pendiente && $pendiente['rol'] === 'administrador'
                      && in_array($tramite['estado'], ['borrador', 'en_firma'], true)): ?>
                <a class="btn btn-success" href="eqr_tramite_firmar.php?id=<?= $id ?>">
                    <i class="bi bi-pen"></i> Firmar como administrador</a>
            <?php endif; ?>
            <?php if ($pendiente && $pendiente['rol'] !== 'administrador' && $tramite['estado'] === 'en_firma'): ?>
                <form method="post" class="d-inline">
                    <?= eqrCsrfCampo() ?><input type="hidden" name="accion" value="reenviar">
                    <button class="btn btn-outline-primary"><i class="bi bi-envelope-arrow-up"></i>
                        Reenviar enlace a <?= e($pendiente['nombre']) ?></button>
                </form>
            <?php endif; ?>
            <?php if ($tramite['tipo'] === 'salida' && $tramite['estado'] === 'completado'
                      && $tramite['salida_retorno_registrado'] === null): ?>
                <form method="post" class="d-inline"
                      onsubmit="return confirm('¿Registrar el retorno del equipo a las instalaciones?');">
                    <?= eqrCsrfCampo() ?><input type="hidden" name="accion" value="retorno">
                    <button class="btn btn-primary"><i class="bi bi-box-arrow-in-left"></i>
                        Registrar retorno del equipo</button>
                </form>
            <?php endif; ?>
            <?php if ($tramite['estado'] === 'borrador'): ?>
                <form method="post" class="d-inline"
                      onsubmit="return confirm('¿Descartar este borrador? Se elimina el trámite y su acta sin firmas.');">
                    <?= eqrCsrfCampo() ?><input type="hidden" name="accion" value="descartar">
                    <button class="btn btn-outline-danger"><i class="bi bi-trash"></i> Descartar borrador</button>
                </form>
            <?php endif; ?>
        </div>
        <?php if (in_array($tramite['estado'], ['borrador', 'en_firma'], true)): ?>
            <hr>
            <form method="post" class="row g-2 align-items-end"
                  onsubmit="return confirm('¿Anular el trámite? Los enlaces de firma quedarán sin efecto y el custodio no cambiará.');">
                <?= eqrCsrfCampo() ?><input type="hidden" name="accion" value="anular">
                <div class="col-md-8">
                    <label class="form-label small">Motivo de la anulación</label>
                    <input name="motivo" class="form-control form-control-sm" required minlength="5" maxlength="500">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-outline-dark btn-sm"><i class="bi bi-x-octagon"></i> Anular trámite</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="col-lg-5">
    <div class="card p-3 mb-3">
        <h6>Correos enviados</h6>
        <?php if (!$notifs): ?><p class="text-muted small mb-0">Todavía no hay correos de este trámite.</p>
        <?php else: ?>
            <?php foreach ($notifs as $n): ?>
                <div class="small border-bottom py-1">
                    <?= $n['resultado'] === 'enviado'
                        ? '<span class="badge bg-success">enviado</span>'
                        : '<span class="badge bg-danger">fallido</span>' ?>
                    <?= e($n['destinatario']) ?> — <?= e($n['asunto']) ?>
                    <span class="text-muted"><?= e(substr((string)$n['fecha'], 0, 16)) ?></span>
                    <?php if ($n['error']): ?><div class="text-danger"><?= e($n['error']) ?></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="card p-3">
        <h6>Bitácora</h6>
        <?php foreach ($bitacora as $b): ?>
            <div class="small border-bottom py-1">
                <strong><?= e($b['accion']) ?></strong>
                <span class="text-muted"><?= e(substr((string)$b['fecha'], 0, 16)) ?> · <?= e($b['usuario']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</div>
<?php eqrFooter();
