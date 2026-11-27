<?php
/** Tablero del modulo Equipos Rentados. */
require __DIR__ . '/eqr_common.php';

if (!eqrMigrado()) { header('Location: eqr_migrar.php'); exit; }

$db = getDB();
$porEstado = ['en_stock' => 0, 'asignado' => 0, 'en_reparacion' => 0, 'retirado' => 0];
foreach ($db->query("SELECT estado, COUNT(*) c FROM eqr_equipos GROUP BY estado") as $r) {
    $porEstado[$r['estado']] = (int)$r['c'];
}
$fuera = (int)$db->query("SELECT COUNT(*) FROM eqr_equipos WHERE fuera_instalaciones = 1")->fetchColumn();
$contratos = (int)$db->query("SELECT COUNT(*) FROM eqr_contratos WHERE activo = 1")->fetchColumn();
$enFirma = (int)$db->query("SELECT COUNT(*) FROM eqr_tramites WHERE estado = 'en_firma'")->fetchColumn();
$ultimos = $db->query("SELECT b.*, eq.codigo_interno FROM eqr_bitacora b
                       LEFT JOIN eqr_equipos eq ON eq.id = b.equipo_id
                       ORDER BY b.id DESC LIMIT 10")->fetchAll();

eqrHeader('Tablero', 'dashboard');
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2"><div class="card p-3"><div class="stat"><?= $porEstado['en_stock'] ?></div><small class="text-muted">En stock</small></div></div>
    <div class="col-6 col-md-2"><div class="card p-3"><div class="stat text-success"><?= $porEstado['asignado'] ?></div><small class="text-muted">Asignados</small></div></div>
    <div class="col-6 col-md-2"><div class="card p-3"><div class="stat text-warning"><?= $porEstado['en_reparacion'] ?></div><small class="text-muted">En reparación</small></div></div>
    <div class="col-6 col-md-2"><div class="card p-3"><div class="stat text-dark"><?= $porEstado['retirado'] ?></div><small class="text-muted">Retirados</small></div></div>
    <div class="col-6 col-md-2"><div class="card p-3"><div class="stat text-primary"><?= $fuera ?></div><small class="text-muted">Fuera de instalaciones</small></div></div>
    <div class="col-6 col-md-2"><div class="card p-3"><div class="stat text-info"><?= $enFirma ?></div><small class="text-muted">Trámites en firma</small></div></div>
</div>

<?php if ($contratos === 0): ?>
    <div class="alert alert-info">
        <strong>Primer paso:</strong> registre el contrato de arrendamiento en
        <a href="eqr_contratos.php">Contratos</a>. Luego cargue los equipos uno a uno o con el
        <a href="eqr_importar.php">importador CSV</a>.
    </div>
<?php endif; ?>

<div class="card p-3">
    <h6><i class="bi bi-clock-history"></i> Última actividad</h6>
    <?php if (!$ultimos): ?>
        <p class="text-muted mb-0">Sin actividad registrada todavía.</p>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-sm mb-0">
            <thead><tr><th>Fecha</th><th>Acción</th><th>Equipo</th><th>Usuario</th></tr></thead>
            <tbody>
            <?php foreach ($ultimos as $u): ?>
                <tr>
                    <td><?= e($u['fecha']) ?></td>
                    <td><?= e($u['accion']) ?></td>
                    <td><?= e($u['codigo_interno'] ?? '—') ?></td>
                    <td><?= e($u['usuario']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
<?php eqrFooter();
