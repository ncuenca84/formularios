<?php
/** Migracion del modulo Equipos Rentados: idempotente, no borra datos. */
require __DIR__ . '/eqr_common.php';

$log = null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    eqrCsrfVerificar();
    try {
        require_once __DIR__ . '/../config/fir_schema.php';
        $log = array_merge(eqrMigrar(getDB()), firMigrar(getDB()));
    } catch (Throwable $ex) {
        $error = $ex->getMessage();
    }
}

eqrHeader('Migración del módulo', '');
?>
<div class="card p-4" style="max-width:720px">
    <p>Crea (o verifica) las tablas del módulo de Equipos Rentados:
    <code>eqr_contratos</code>, <code>eqr_equipos</code>, <code>eqr_personas</code>,
    <code>eqr_tramites</code>, <code>eqr_firmas</code>, <code>eqr_bitacora</code>,
    <code>eqr_notificaciones</code>, más el encabezado institucional de las actas
    (fila 8 en <code>formularios_meta</code>).</p>
    <p class="text-muted small"><i class="bi bi-shield-check"></i> Es seguro ejecutarla
    las veces que haga falta: no borra ni modifica datos existentes.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger">Error: <?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($log): ?>
        <div class="alert alert-success">
            <?php foreach ($log as $l): ?><div><?= e($l) ?></div><?php endforeach; ?>
        </div>
        <a href="eqr_dashboard.php" class="btn btn-success">Ir al tablero</a>
    <?php else: ?>
        <form method="post">
            <?= eqrCsrfCampo() ?>
            <button class="btn btn-primary"><i class="bi bi-database-add"></i> Ejecutar migración</button>
        </form>
    <?php endif; ?>
</div>
<?php eqrFooter();
