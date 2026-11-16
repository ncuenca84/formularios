<?php
/** Ficha del equipo: datos tecnicos + historial de tramites y bitacora. */
require __DIR__ . '/eqr_common.php';
if (!eqrMigrado()) { header('Location: eqr_migrar.php'); exit; }

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT e.*, c.numero contrato FROM eqr_equipos e
                      JOIN eqr_contratos c ON c.id = e.contrato_id WHERE e.id = ?");
$stmt->execute([$id]);
$eq = $stmt->fetch();
if (!$eq) { eqrFlash('Equipo no encontrado.', 'error'); header('Location: eqr_equipos.php'); exit; }

$stmt = $db->prepare("SELECT * FROM eqr_tramites WHERE equipo_id = ? ORDER BY id DESC");
$stmt->execute([$id]);
$tramites = $stmt->fetchAll();

$stmt = $db->prepare("SELECT * FROM eqr_bitacora WHERE equipo_id = ? ORDER BY id DESC LIMIT 100");
$stmt->execute([$id]);
$bitacora = $stmt->fetchAll();

$badgeTramite = function (string $s): string {
    $map = ['borrador'=>'secondary','en_firma'=>'info','completado'=>'success','rechazado'=>'danger','anulado'=>'dark'];
    return '<span class="badge bg-' . ($map[$s] ?? 'light') . '">' . e(ucfirst(str_replace('_',' ',$s))) . '</span>';
};

eqrHeader('Ficha del equipo ' . $eq['codigo_interno'], 'equipos');
?>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card p-3">
            <h6><i class="bi bi-laptop"></i> Datos técnicos
                <a class="btn btn-sm btn-outline-primary float-end" href="eqr_equipos.php?editar=<?= (int)$eq['id'] ?>"><i class="bi bi-pencil"></i> Editar</a></h6>
            <table class="table table-sm mb-0">
                <tr><th>Código interno</th><td><?= e($eq['codigo_interno']) ?></td></tr>
                <tr><th>Contrato</th><td><?= e($eq['contrato']) ?></td></tr>
                <tr><th>Tipo</th><td><?= e($eq['tipo']) ?></td></tr>
                <tr><th>Marca / Modelo</th><td><?= e($eq['marca']) ?> <?= e($eq['modelo']) ?></td></tr>
                <tr><th>Procesador</th><td><?= e($eq['procesador']) ?></td></tr>
                <tr><th>RAM / Disco</th><td><?= e($eq['ram']) ?> / <?= e($eq['almacenamiento']) ?></td></tr>
                <tr><th>Pantalla</th><td><?= e($eq['pantalla']) ?></td></tr>
                <tr><th>Serie</th><td><code><?= e($eq['serie']) ?></code></td></tr>
                <tr><th>Cargador</th><td><?= $eq['cargador_entregado'] ? 'Sí' : 'No' ?>
                    <?= $eq['serie_cargador'] ? ' — ' . e($eq['serie_cargador']) : '' ?></td></tr>
                <tr><th>Estado</th><td><?= eqrBadgeEstado($eq['estado']) ?>
                    <?= $eq['fuera_instalaciones'] ? ' <span class="badge bg-primary">Fuera de instalaciones</span>' : '' ?></td></tr>
                <?php if ($eq['custodio_nombre']): ?>
                <tr><th>Custodio</th><td><?= e($eq['custodio_nombre']) ?><br>
                    <small class="text-muted">C.C. <?= e((string)$eq['custodio_cedula']) ?> ·
                    <?= e((string)$eq['custodio_area']) ?> · <?= e((string)$eq['custodio_correo']) ?></small></td></tr>
                <?php endif; ?>
                <?php if ($eq['observaciones']): ?>
                <tr><th>Observaciones</th><td><?= e($eq['observaciones']) ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card p-3 mb-3">
            <h6><i class="bi bi-journal-text"></i> Trámites del equipo</h6>
            <?php if (!$tramites): ?>
                <p class="text-muted mb-0">Sin trámites todavía.</p>
            <?php else: ?>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead><tr><th>Código</th><th>Tipo</th><th>Estado</th><th>Recibe / Custodio</th><th>Fecha</th></tr></thead>
                    <tbody>
                    <?php foreach ($tramites as $t): ?>
                        <tr>
                            <td><code><?= e($t['codigo']) ?></code>
                                <?= $t['referencia_acta_fisica'] ? '<br><small class="text-muted">Acta física: ' . e($t['referencia_acta_fisica']) . '</small>' : '' ?></td>
                            <td><?= e(ucfirst(str_replace('_', ' ', $t['tipo']))) ?></td>
                            <td><?= $badgeTramite($t['estado']) ?></td>
                            <td><?= e((string)$t['recibe_nombre']) ?: '—' ?></td>
                            <td><small><?= e((string)$t['created_at']) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endif; ?>
        </div>
        <div class="card p-3">
            <h6><i class="bi bi-clock-history"></i> Bitácora</h6>
            <?php if (!$bitacora): ?>
                <p class="text-muted mb-0">Sin registros.</p>
            <?php else: ?>
                <div class="table-responsive" style="max-height:360px; overflow:auto"><table class="table table-sm mb-0">
                    <thead><tr><th>Fecha</th><th>Acción</th><th>Usuario</th></tr></thead>
                    <tbody>
                    <?php foreach ($bitacora as $b): ?>
                        <tr>
                            <td class="text-nowrap"><small><?= e($b['fecha']) ?></small></td>
                            <td><?= e($b['accion']) ?></td>
                            <td><small><?= e($b['usuario']) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="mt-3"><a href="eqr_equipos.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver al listado</a></div>
<?php eqrFooter();
