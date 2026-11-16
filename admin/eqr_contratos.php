<?php
/** Contratos de arrendamiento: listado, crear y editar. */
require __DIR__ . '/eqr_common.php';
if (!eqrMigrado()) { header('Location: eqr_migrar.php'); exit; }

$db = getDB();
$textosDefault = require __DIR__ . '/../config/eqr_textos_default.php';

// ------------------------------------------------------------------ guardar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    eqrCsrfVerificar();
    $id = (int)($_POST['id'] ?? 0);
    $campos = [
        'numero' => trim((string)($_POST['numero'] ?? '')),
        'proceso' => trim((string)($_POST['proceso'] ?? '')),
        'objeto' => trim((string)($_POST['objeto'] ?? '')),
        'proveedor' => trim((string)($_POST['proveedor'] ?? '')),
        'fecha_inicio' => ($_POST['fecha_inicio'] ?? '') ?: null,
        'fecha_fin' => ($_POST['fecha_fin'] ?? '') ?: null,
        'total_equipos' => (int)($_POST['total_equipos'] ?? 0),
        'admin_nombre' => trim((string)($_POST['admin_nombre'] ?? '')),
        'admin_cedula' => preg_replace('/\D/', '', (string)($_POST['admin_cedula'] ?? '')),
        'admin_cargo' => trim((string)($_POST['admin_cargo'] ?? '')),
        'admin_correo' => trim((string)($_POST['admin_correo'] ?? '')),
        'admin_calidad' => trim((string)($_POST['admin_calidad'] ?? '')),
        'precio_unitario' => (float)str_replace(',', '.', (string)($_POST['precio_unitario'] ?? '0')),
        'valor_total' => (float)str_replace(',', '.', (string)($_POST['valor_total'] ?? '0')),
        'plazo_dias' => (int)($_POST['plazo_dias'] ?? 0),
        'contratista_ruc' => preg_replace('/\D/', '', (string)($_POST['contratista_ruc'] ?? '')),
        'contratista_representante' => trim((string)($_POST['contratista_representante'] ?? '')),
        'contratista_correo' => trim((string)($_POST['contratista_correo'] ?? '')),
        'contratista_telefono' => trim((string)($_POST['contratista_telefono'] ?? '')),
        'contratista_direccion' => trim((string)($_POST['contratista_direccion'] ?? '')),
        'texto_compromisos' => (string)($_POST['texto_compromisos'] ?? ''),
        'texto_consideraciones' => (string)($_POST['texto_consideraciones'] ?? ''),
        'texto_aceptacion' => (string)($_POST['texto_aceptacion'] ?? ''),
        'activo' => isset($_POST['activo']) ? 1 : 0,
    ];

    $errores = [];
    if ($campos['numero'] === '') { $errores[] = 'El número de contrato es obligatorio.'; }
    if ($campos['admin_nombre'] === '') { $errores[] = 'El nombre del administrador del contrato es obligatorio.'; }
    if (!eqrValidarCedula($campos['admin_cedula'])) { $errores[] = 'La cédula del administrador no es una cédula ecuatoriana válida.'; }
    if ($campos['admin_correo'] === '' || !filter_var($campos['admin_correo'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo del administrador no es válido.';
    }

    if (!$errores) {
        try {
            if ($id > 0) {
                $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($campos)));
                $stmt = $db->prepare("UPDATE eqr_contratos SET $set WHERE id = :id");
                $stmt->execute($campos + ['id' => $id]);
                eqrBitacora(null, null, 'contrato_editado', ['contrato_id' => $id, 'numero' => $campos['numero']]);
                eqrFlash('Contrato actualizado.');
            } else {
                $cols = implode(', ', array_keys($campos));
                $ph = implode(', ', array_map(fn($k) => ":$k", array_keys($campos)));
                $stmt = $db->prepare("INSERT INTO eqr_contratos ($cols) VALUES ($ph)");
                $stmt->execute($campos);
                eqrBitacora(null, null, 'contrato_creado', ['numero' => $campos['numero']]);
                eqrFlash('Contrato registrado.');
            }
            header('Location: eqr_contratos.php');
            exit;
        } catch (PDOException $ex) {
            $errores[] = (strpos($ex->getMessage(), 'Duplicate') !== false)
                ? 'Ya existe un contrato con ese número.'
                : 'Error al guardar: ' . $ex->getMessage();
        }
    }
    $form = $campos + ['id' => $id];
}

// -------------------------------------------------------------------- datos
$editar = (int)($_GET['editar'] ?? 0);
$nuevo = isset($_GET['nuevo']);
if (!isset($form)) {
    if ($editar > 0) {
        $stmt = $db->prepare("SELECT * FROM eqr_contratos WHERE id = ?");
        $stmt->execute([$editar]);
        $form = $stmt->fetch() ?: null;
        if (!$form) { eqrFlash('Contrato no encontrado.', 'error'); header('Location: eqr_contratos.php'); exit; }
    } elseif ($nuevo) {
        $form = [
            'id' => 0, 'numero' => '', 'proceso' => '', 'objeto' => 'ARRENDAMIENTO DE EQUIPOS DE CÓMPUTO',
            'proveedor' => '', 'fecha_inicio' => '', 'fecha_fin' => '', 'total_equipos' => 60,
            'admin_nombre' => '', 'admin_cedula' => '', 'admin_cargo' => '',
            'admin_correo' => '', 'admin_calidad' => 'Custodio Administrativo / Servidor autorizado DTIC',
            'precio_unitario' => '0', 'valor_total' => '0', 'plazo_dias' => 0,
            'contratista_ruc' => '', 'contratista_representante' => '', 'contratista_correo' => '',
            'contratista_telefono' => '', 'contratista_direccion' => '',
            'texto_compromisos' => $textosDefault['compromisos'],
            'texto_consideraciones' => $textosDefault['consideraciones'],
            'texto_aceptacion' => $textosDefault['aceptacion'],
            'activo' => 1,
        ];
    }
}
$lista = $db->query("SELECT c.*, (SELECT COUNT(*) FROM eqr_equipos e WHERE e.contrato_id = c.id) equipos
                     FROM eqr_contratos c ORDER BY c.activo DESC, c.id DESC")->fetchAll();

eqrHeader('Contratos de arrendamiento', 'contratos');
?>

<?php if (isset($form) && $form): ?>
    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger"><?php foreach ($errores as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div>
    <?php endif; ?>
    <div class="card p-4 mb-4">
        <h6><?= $form['id'] ? 'Editar contrato' : 'Nuevo contrato' ?></h6>
        <form method="post">
            <?= eqrCsrfCampo() ?>
            <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Número de contrato *</label>
                    <input name="numero" class="form-control" required value="<?= e($form['numero']) ?>" placeholder="Ej: ARCONEL-005-2026">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Proceso</label>
                    <input name="proceso" class="form-control" value="<?= e($form['proceso']) ?>" placeholder="Ej: SIE-ARCONEL-2026-003">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Proveedor (contratista)</label>
                    <input name="proveedor" class="form-control" value="<?= e($form['proveedor']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Objeto</label>
                    <input name="objeto" class="form-control" value="<?= e($form['objeto']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Total de equipos</label>
                    <input name="total_equipos" type="number" min="0" class="form-control" value="<?= (int)$form['total_equipos'] ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Inicio</label>
                    <input name="fecha_inicio" type="date" class="form-control" value="<?= e((string)$form['fecha_inicio']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fin</label>
                    <input name="fecha_fin" type="date" class="form-control" value="<?= e((string)$form['fecha_fin']) ?>">
                </div>
            </div>

            <h6 class="mt-4">Datos económicos y del contratista</h6>
            <p class="text-muted small">Se usan para generar las actas de entrega-recepción y los
            informes de conformidad / solicitud de pago del contrato (cláusula sexta).</p>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Valor unitario mensual (USD)</label>
                    <input name="precio_unitario" class="form-control" value="<?= e((string)($form['precio_unitario'] ?? '0')) ?>" placeholder="Ej: 48.88">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Valor total sin IVA (USD)</label>
                    <input name="valor_total" class="form-control" value="<?= e((string)($form['valor_total'] ?? '0')) ?>" placeholder="Ej: 35193.60">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Plazo (días)</label>
                    <input name="plazo_dias" type="number" min="0" class="form-control" value="<?= (int)($form['plazo_dias'] ?? 0) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">RUC del contratista</label>
                    <input name="contratista_ruc" class="form-control" maxlength="13" value="<?= e((string)($form['contratista_ruc'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Representante legal</label>
                    <input name="contratista_representante" class="form-control" value="<?= e((string)($form['contratista_representante'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Correo del contratista</label>
                    <input name="contratista_correo" class="form-control" value="<?= e((string)($form['contratista_correo'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Teléfono del contratista</label>
                    <input name="contratista_telefono" class="form-control" value="<?= e((string)($form['contratista_telefono'] ?? '')) ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Dirección del contratista</label>
                    <input name="contratista_direccion" class="form-control" value="<?= e((string)($form['contratista_direccion'] ?? '')) ?>">
                </div>
            </div>

            <h6 class="mt-4">Administrador del contrato / Custodio Administrativo</h6>
            <p class="text-muted small">Estos datos se copian a cada trámite al crearlo. Si cambia la persona,
            edítelos aquí: los trámites nuevos usarán a la nueva y las actas ya emitidas no se alteran.</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nombre completo *</label>
                    <input name="admin_nombre" class="form-control" required value="<?= e($form['admin_nombre']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cédula *</label>
                    <input name="admin_cedula" class="form-control" required maxlength="10" value="<?= e($form['admin_cedula']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cargo</label>
                    <input name="admin_cargo" class="form-control" value="<?= e($form['admin_cargo']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Correo institucional *</label>
                    <input name="admin_correo" type="email" class="form-control" required value="<?= e($form['admin_correo']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Calidad</label>
                    <input name="admin_calidad" class="form-control" value="<?= e($form['admin_calidad']) ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="activo" id="activo" <?= !empty($form['activo']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="activo">Contrato activo</label>
                    </div>
                </div>
            </div>

            <h6 class="mt-4">Textos de las cláusulas del acta</h6>
            <p class="text-muted small">Vienen prellenados con el acta modelo aprobada. Si los edita, revise los
            cambios con el área legal antes de usarlos en producción.</p>
            <div class="mb-3">
                <label class="form-label">Compromisos del usuario</label>
                <textarea name="texto_compromisos" class="form-control" rows="8"><?= e((string)$form['texto_compromisos']) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Consideraciones sobre el equipo en arrendamiento</label>
                <textarea name="texto_consideraciones" class="form-control" rows="6"><?= e((string)$form['texto_consideraciones']) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Aceptación</label>
                <textarea name="texto_aceptacion" class="form-control" rows="6"><?= e((string)$form['texto_aceptacion']) ?></textarea>
            </div>

            <button class="btn btn-success"><i class="bi bi-save"></i> Guardar contrato</button>
            <a href="eqr_contratos.php" class="btn btn-outline-secondary">Cancelar</a>
        </form>
    </div>
<?php else: ?>
    <div class="mb-3">
        <a href="eqr_contratos.php?nuevo=1" class="btn btn-success"><i class="bi bi-plus-lg"></i> Nuevo contrato</a>
    </div>
<?php endif; ?>

<div class="card p-3">
    <h6>Contratos registrados</h6>
    <?php if (!$lista): ?>
        <p class="text-muted mb-0">Todavía no hay contratos.</p>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-hover mb-0">
            <thead><tr><th>Número</th><th>Proceso</th><th>Administrador</th><th>Equipos</th><th>Vigencia</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($lista as $c): ?>
                <tr>
                    <td><strong><?= e($c['numero']) ?></strong></td>
                    <td><?= e($c['proceso']) ?></td>
                    <td><?= e($c['admin_nombre']) ?><br><small class="text-muted">C.C. <?= e($c['admin_cedula']) ?></small></td>
                    <td><?= (int)$c['equipos'] ?> / <?= (int)$c['total_equipos'] ?></td>
                    <td><small><?= e((string)$c['fecha_inicio']) ?> — <?= e((string)$c['fecha_fin']) ?></small></td>
                    <td><?= $c['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?></td>
                    <td><a class="btn btn-sm btn-outline-primary" href="eqr_contratos.php?editar=<?= (int)$c['id'] ?>"><i class="bi bi-pencil"></i> Editar</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
<?php eqrFooter();
