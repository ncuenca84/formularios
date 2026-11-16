<?php
/** Bandeja de tramites: pestañas por estado. */
require __DIR__ . '/eqr_common.php';
if (!eqrMigrado()) { header('Location: eqr_migrar.php'); exit; }
require_once __DIR__ . '/../includes/eqr/flujo.php';

$db = getDB();

$pestanas = [
    'en_firma' => ['En firma', 'warning'],
    'borrador' => ['Borradores', 'secondary'],
    'completado' => ['Completados', 'success'],
    'rechazado' => ['Rechazados', 'danger'],
    'anulado' => ['Anulados', 'dark'],
    'carga_inicial' => ['Carga inicial', 'info'],
];
$tab = (string)($_GET['tab'] ?? 'en_firma');
if (!isset($pestanas[$tab])) { $tab = 'en_firma'; }

$conteos = [];
foreach ($pestanas as $k => $v) {
    if ($k === 'carga_inicial') {
        $conteos[$k] = (int)$db->query("SELECT COUNT(*) FROM eqr_tramites WHERE tipo = 'carga_inicial'")->fetchColumn();
    } else {
        $conteos[$k] = (int)$db->query("SELECT COUNT(*) FROM eqr_tramites
            WHERE estado = " . $db->quote($k) . " AND tipo <> 'carga_inicial'")->fetchColumn();
    }
}

if ($tab === 'carga_inicial') {
    $stmt = $db->query("SELECT t.*, e.codigo_interno equipo_codigo, e.marca equipo_marca, e.modelo equipo_modelo
                        FROM eqr_tramites t JOIN eqr_equipos e ON e.id = t.equipo_id
                        WHERE t.tipo = 'carga_inicial' ORDER BY t.id DESC LIMIT 300");
} else {
    $stmt = $db->prepare("SELECT t.*, e.codigo_interno equipo_codigo, e.marca equipo_marca, e.modelo equipo_modelo,
                            (SELECT CONCAT(f.nombre, '|', f.orden) FROM eqr_firmas f
                             WHERE f.tramite_id = t.id AND f.estado = 'pendiente' ORDER BY f.orden LIMIT 1) turno
                          FROM eqr_tramites t JOIN eqr_equipos e ON e.id = t.equipo_id
                          WHERE t.estado = ? AND t.tipo <> 'carga_inicial' ORDER BY t.id DESC LIMIT 300");
    $stmt->execute([$tab]);
}
$tramites = $stmt->fetchAll();

$tipos = ['asignacion' => 'Asignación', 'traspaso' => 'Traspaso', 'devolucion' => 'Devolución',
          'salida' => 'Salida', 'carga_inicial' => 'Carga inicial'];

eqrHeader('Trámites de equipos', 'tramites');
?>
<div class="mb-3">
    <a href="eqr_tramite_nuevo.php" class="btn btn-success"><i class="bi bi-plus-lg"></i> Nuevo trámite</a>
</div>

<ul class="nav nav-tabs mb-3">
    <?php foreach ($pestanas as $k => [$nombre, $color]): ?>
        <li class="nav-item">
            <a class="nav-link <?= $k === $tab ? 'active' : '' ?>" href="eqr_tramites.php?tab=<?= e($k) ?>">
                <?= e($nombre) ?> <span class="badge bg-<?= e($color) ?>"><?= $conteos[$k] ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<div class="card p-3">
    <?php if (!$tramites): ?>
        <p class="text-muted mb-0">No hay trámites en esta pestaña.</p>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-hover table-sm mb-0">
            <thead><tr><th>Código</th><th>Tipo</th><th>Equipo</th><th>Entrega</th><th>Recibe</th>
                <th><?= $tab === 'en_firma' ? 'Turno de firma' : 'Fecha' ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($tramites as $t): ?>
                <tr>
                    <td><strong><?= e($t['codigo']) ?></strong></td>
                    <td><?= e($tipos[$t['tipo']] ?? $t['tipo']) ?></td>
                    <td><?= e($t['equipo_codigo']) ?><br><small class="text-muted"><?= e($t['equipo_marca']) ?> <?= e($t['equipo_modelo']) ?></small></td>
                    <td><small><?= e((string)($t['entrega_nombre'] ?: ($t['tipo'] === 'asignacion' ? 'DTIC' : '—'))) ?></small></td>
                    <td><small><?= e((string)($t['recibe_nombre'] ?: ($t['tipo'] === 'devolucion' ? 'DTIC' : '—'))) ?></small></td>
                    <td><small><?php
                        if ($tab === 'en_firma' && !empty($t['turno'])) {
                            [$nom, $ord] = explode('|', $t['turno']);
                            echo 'Firma ' . (int)$ord . ': ' . e($nom);
                        } else {
                            echo e(substr((string)$t['created_at'], 0, 16));
                        }
                    ?></small></td>
                    <td class="text-end">
                        <?php if ($t['tipo'] !== 'carga_inicial'): ?>
                            <a class="btn btn-sm btn-outline-primary" href="eqr_tramite_ver.php?id=<?= (int)$t['id'] ?>">
                                <i class="bi bi-eye"></i> Ver</a>
                        <?php else: ?>
                            <a class="btn btn-sm btn-outline-secondary" href="eqr_equipo_ficha.php?id=<?= (int)$t['equipo_id'] ?>">
                                <i class="bi bi-laptop"></i> Equipo</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
<?php eqrFooter();
