<?php
/** Equipos rentados: listado con filtros, crear y editar ficha tecnica. */
require __DIR__ . '/eqr_common.php';
if (!eqrMigrado()) { header('Location: eqr_migrar.php'); exit; }

$db = getDB();
$contratos = $db->query("SELECT id, numero FROM eqr_contratos ORDER BY activo DESC, id DESC")->fetchAll();

// ------------------------------------------------------------------ guardar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    eqrCsrfVerificar();
    $id = (int)($_POST['id'] ?? 0);
    $campos = [
        'contrato_id' => (int)($_POST['contrato_id'] ?? 0),
        'codigo_interno' => strtoupper(trim((string)($_POST['codigo_interno'] ?? ''))),
        'tipo' => trim((string)($_POST['tipo'] ?? '')),
        'marca' => trim((string)($_POST['marca'] ?? '')),
        'modelo' => trim((string)($_POST['modelo'] ?? '')),
        'procesador' => trim((string)($_POST['procesador'] ?? '')),
        'ram' => trim((string)($_POST['ram'] ?? '')),
        'almacenamiento' => trim((string)($_POST['almacenamiento'] ?? '')),
        'pantalla' => trim((string)($_POST['pantalla'] ?? '')),
        'serie' => strtoupper(trim((string)($_POST['serie'] ?? ''))),
        'cargador_entregado' => isset($_POST['cargador_entregado']) ? 1 : 0,
        'serie_cargador' => trim((string)($_POST['serie_cargador'] ?? '')),
        'estado_fisico' => trim((string)($_POST['estado_fisico'] ?? '')),
        'estado_operativo' => trim((string)($_POST['estado_operativo'] ?? '')),
        'observaciones' => trim((string)($_POST['observaciones'] ?? '')),
        'estado' => in_array($_POST['estado'] ?? '', ['en_stock','asignado','en_reparacion','retirado'], true)
                    ? $_POST['estado'] : 'en_stock',
    ];
    // custodio solo si el estado es asignado
    $cust = [
        'custodio_cedula' => preg_replace('/\D/', '', (string)($_POST['custodio_cedula'] ?? '')),
        'custodio_nombre' => trim((string)($_POST['custodio_nombre'] ?? '')),
        'custodio_correo' => trim((string)($_POST['custodio_correo'] ?? '')),
        'custodio_cargo' => trim((string)($_POST['custodio_cargo'] ?? '')),
        'custodio_area' => trim((string)($_POST['custodio_area'] ?? '')),
    ];

    $errores = [];
    if ($campos['contrato_id'] <= 0) { $errores[] = 'Seleccione el contrato.'; }
    if ($campos['codigo_interno'] === '') { $errores[] = 'El código interno es obligatorio (ej: REN-0001).'; }
    if ($campos['serie'] === '') { $errores[] = 'El número de serie es obligatorio.'; }
    if ($campos['estado'] === 'asignado') {
        if (!eqrValidarCedula($cust['custodio_cedula'])) { $errores[] = 'La cédula del custodio no es válida.'; }
        if ($cust['custodio_nombre'] === '') { $errores[] = 'El nombre del custodio es obligatorio si el equipo está asignado.'; }
    } else {
        $cust = array_fill_keys(array_keys($cust), null);
    }
    $campos = array_merge($campos, $cust);

    if (!$errores) {
        try {
            if ($id > 0) {
                $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($campos)));
                $stmt = $db->prepare("UPDATE eqr_equipos SET $set WHERE id = :id");
                $stmt->execute($campos + ['id' => $id]);
                eqrBitacora($id, null, 'equipo_editado', ['codigo' => $campos['codigo_interno']]);
                eqrFlash('Equipo actualizado.');
            } else {
                $cols = implode(', ', array_keys($campos));
                $ph = implode(', ', array_map(fn($k) => ":$k", array_keys($campos)));
                $stmt = $db->prepare("INSERT INTO eqr_equipos ($cols) VALUES ($ph)");
                $stmt->execute($campos);
                $id = (int)$db->lastInsertId();
                eqrBitacora($id, null, 'equipo_creado', ['codigo' => $campos['codigo_interno']]);
                // registrar persona para autocompletado
                if (!empty($campos['custodio_cedula'])) {
                    $db->prepare("INSERT INTO eqr_personas (cedula, nombres, correo, cargo, area)
                                  VALUES (?,?,?,?,?)
                                  ON DUPLICATE KEY UPDATE nombres=VALUES(nombres), correo=VALUES(correo),
                                  cargo=VALUES(cargo), area=VALUES(area)")
                       ->execute([$campos['custodio_cedula'], $campos['custodio_nombre'],
                                  $campos['custodio_correo'], $campos['custodio_cargo'], $campos['custodio_area']]);
                }
                eqrFlash('Equipo registrado.');
            }
            header('Location: eqr_equipos.php');
            exit;
        } catch (PDOException $ex) {
            $errores[] = (strpos($ex->getMessage(), 'Duplicate') !== false)
                ? 'Ya existe un equipo con ese código interno o número de serie.'
                : 'Error al guardar: ' . $ex->getMessage();
        }
    }
    $form = $campos + ['id' => $id];
}

// ------------------------------------------------------------- formulario
$editar = (int)($_GET['editar'] ?? 0);
$nuevo = isset($_GET['nuevo']);
if (!isset($form)) {
    if ($editar > 0) {
        $stmt = $db->prepare("SELECT * FROM eqr_equipos WHERE id = ?");
        $stmt->execute([$editar]);
        $form = $stmt->fetch() ?: null;
        if (!$form) { eqrFlash('Equipo no encontrado.', 'error'); header('Location: eqr_equipos.php'); exit; }
    } elseif ($nuevo) {
        // sugerir el siguiente codigo interno
        $max = (string)$db->query("SELECT codigo_interno FROM eqr_equipos
                                   WHERE codigo_interno REGEXP '^REN-[0-9]+$'
                                   ORDER BY CAST(SUBSTRING(codigo_interno,5) AS UNSIGNED) DESC LIMIT 1")->fetchColumn();
        $sig = $max ? sprintf('REN-%04d', (int)substr($max, 4) + 1) : 'REN-0001';
        $form = [
            'id' => 0, 'contrato_id' => $contratos[0]['id'] ?? 0, 'codigo_interno' => $sig,
            'tipo' => 'Computador portátil', 'marca' => '', 'modelo' => '', 'procesador' => '',
            'ram' => '', 'almacenamiento' => '', 'pantalla' => '', 'serie' => '',
            'cargador_entregado' => 1, 'serie_cargador' => '',
            'estado_fisico' => 'Equipo nuevo, sin golpes, fisuras, roturas, rayaduras visibles ni piezas faltantes al momento de la entrega al usuario final.',
            'estado_operativo' => 'Equipo encendido y operativo al momento de la entrega al usuario final.',
            'observaciones' => '', 'estado' => 'en_stock',
            'custodio_cedula' => '', 'custodio_nombre' => '', 'custodio_correo' => '',
            'custodio_cargo' => '', 'custodio_area' => '',
        ];
    }
}

// ---------------------------------------------------------------- listado
$filtros = [
    'estado' => (string)($_GET['f_estado'] ?? ''),
    'contrato' => (int)($_GET['f_contrato'] ?? 0),
    'q' => trim((string)($_GET['q'] ?? '')),
];
$where = [];
$par = [];
if (in_array($filtros['estado'], ['en_stock','asignado','en_reparacion','retirado'], true)) {
    $where[] = 'e.estado = ?'; $par[] = $filtros['estado'];
}
if ($filtros['contrato'] > 0) { $where[] = 'e.contrato_id = ?'; $par[] = $filtros['contrato']; }
if ($filtros['q'] !== '') {
    $where[] = '(e.codigo_interno LIKE ? OR e.serie LIKE ? OR e.marca LIKE ? OR e.modelo LIKE ?
                 OR e.custodio_nombre LIKE ? OR e.custodio_cedula LIKE ? OR e.custodio_area LIKE ?)';
    $like = '%' . $filtros['q'] . '%';
    array_push($par, $like, $like, $like, $like, $like, $like, $like);
}
$sqlW = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$stmt = $db->prepare("SELECT e.*, c.numero contrato FROM eqr_equipos e
                      JOIN eqr_contratos c ON c.id = e.contrato_id
                      $sqlW ORDER BY e.codigo_interno LIMIT 500");
$stmt->execute($par);
$lista = $stmt->fetchAll();

eqrHeader('Equipos', 'equipos');
?>

<?php if (isset($form) && $form): ?>
    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger"><?php foreach ($errores as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div>
    <?php endif; ?>
    <div class="card p-4 mb-4">
        <h6><?= $form['id'] ? 'Editar equipo ' . e($form['codigo_interno']) : 'Nuevo equipo' ?></h6>
        <?php if (!$contratos): ?>
            <div class="alert alert-warning">Primero registre un <a href="eqr_contratos.php?nuevo=1">contrato</a>.</div>
        <?php else: ?>
        <form method="post">
            <?= eqrCsrfCampo() ?>
            <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Contrato *</label>
                    <select name="contrato_id" class="form-select" required>
                        <?php foreach ($contratos as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= (int)$form['contrato_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['numero']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Código interno *</label>
                    <input name="codigo_interno" class="form-control" required value="<?= e($form['codigo_interno']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <input name="tipo" class="form-control" value="<?= e($form['tipo']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" id="selEstado" class="form-select">
                        <?php foreach (['en_stock'=>'En stock','asignado'=>'Asignado','en_reparacion'=>'En reparación','retirado'=>'Retirado'] as $k=>$v): ?>
                            <option value="<?= $k ?>" <?= $form['estado'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Marca</label><input name="marca" class="form-control" value="<?= e($form['marca']) ?>"></div>
                <div class="col-md-3"><label class="form-label">Modelo</label><input name="modelo" class="form-control" value="<?= e($form['modelo']) ?>"></div>
                <div class="col-md-3"><label class="form-label">Procesador</label><input name="procesador" class="form-control" value="<?= e($form['procesador']) ?>"></div>
                <div class="col-md-3"><label class="form-label">RAM</label><input name="ram" class="form-control" value="<?= e($form['ram']) ?>"></div>
                <div class="col-md-3"><label class="form-label">Almacenamiento</label><input name="almacenamiento" class="form-control" value="<?= e($form['almacenamiento']) ?>"></div>
                <div class="col-md-3"><label class="form-label">Pantalla</label><input name="pantalla" class="form-control" value="<?= e($form['pantalla']) ?>"></div>
                <div class="col-md-3"><label class="form-label">Número de serie *</label><input name="serie" class="form-control" required value="<?= e($form['serie']) ?>"></div>
                <div class="col-md-3">
                    <label class="form-label">Serie del cargador</label>
                    <input name="serie_cargador" class="form-control" value="<?= e((string)$form['serie_cargador']) ?>">
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" name="cargador_entregado" id="cargador" <?= !empty($form['cargador_entregado']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="cargador">Cargador entregado</label>
                    </div>
                </div>
                <div class="col-md-6"><label class="form-label">Estado físico</label><textarea name="estado_fisico" class="form-control" rows="2"><?= e((string)$form['estado_fisico']) ?></textarea></div>
                <div class="col-md-6"><label class="form-label">Estado operativo</label><textarea name="estado_operativo" class="form-control" rows="2"><?= e((string)$form['estado_operativo']) ?></textarea></div>
                <div class="col-12"><label class="form-label">Observaciones</label><textarea name="observaciones" class="form-control" rows="2"><?= e((string)$form['observaciones']) ?></textarea></div>
            </div>

            <div id="panelCustodio" class="mt-3" style="<?= $form['estado'] === 'asignado' ? '' : 'display:none' ?>">
                <h6>Custodio actual</h6>
                <p class="text-muted small">Solo para carga/corrección administrativa. Las asignaciones
                normales se harán con el trámite de asignación firmado (Fase 2).</p>
                <div class="row g-3">
                    <div class="col-md-2"><label class="form-label">Cédula</label><input name="custodio_cedula" maxlength="10" class="form-control" value="<?= e((string)$form['custodio_cedula']) ?>"></div>
                    <div class="col-md-3"><label class="form-label">Nombre</label><input name="custodio_nombre" class="form-control" value="<?= e((string)$form['custodio_nombre']) ?>"></div>
                    <div class="col-md-3"><label class="form-label">Correo</label><input name="custodio_correo" class="form-control" value="<?= e((string)$form['custodio_correo']) ?>"></div>
                    <div class="col-md-2"><label class="form-label">Cargo</label><input name="custodio_cargo" class="form-control" value="<?= e((string)$form['custodio_cargo']) ?>"></div>
                    <div class="col-md-2"><label class="form-label">Área</label><input name="custodio_area" class="form-control" value="<?= e((string)$form['custodio_area']) ?>"></div>
                </div>
            </div>

            <div class="mt-3">
                <button class="btn btn-success"><i class="bi bi-save"></i> Guardar equipo</button>
                <a href="eqr_equipos.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
        <script>
        document.getElementById('selEstado').addEventListener('change', function () {
            document.getElementById('panelCustodio').style.display = this.value === 'asignado' ? '' : 'none';
        });
        </script>
        <?php endif; ?>
    </div>
<?php else: ?>
    <form class="card p-3 mb-3" method="get">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Buscar</label>
                <input name="q" class="form-control" value="<?= e($filtros['q']) ?>" placeholder="código, serie, custodio…">
            </div>
            <div class="col-md-2">
                <label class="form-label">Estado</label>
                <select name="f_estado" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach (['en_stock'=>'En stock','asignado'=>'Asignado','en_reparacion'=>'En reparación','retirado'=>'Retirado'] as $k=>$v): ?>
                        <option value="<?= $k ?>" <?= $filtros['estado'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Contrato</label>
                <select name="f_contrato" class="form-select">
                    <option value="0">Todos</option>
                    <?php foreach ($contratos as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $filtros['contrato'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['numero']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Filtrar</button>
                <a href="eqr_equipos.php?nuevo=1" class="btn btn-success"><i class="bi bi-plus-lg"></i> Nuevo equipo</a>
            </div>
        </div>
    </form>
<?php endif; ?>

<div class="card p-3">
    <h6>Equipos (<?= count($lista) ?>)</h6>
    <?php if (!$lista): ?>
        <p class="text-muted mb-0">No hay equipos con esos criterios.</p>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-hover mb-0">
            <thead><tr><th>Código</th><th>Equipo</th><th>Serie</th><th>Estado</th><th>Custodio</th><th>Área</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($lista as $eq): ?>
                <tr>
                    <td><strong><?= e($eq['codigo_interno']) ?></strong></td>
                    <td><?= e($eq['marca']) ?> <?= e($eq['modelo']) ?><br><small class="text-muted"><?= e($eq['tipo']) ?></small></td>
                    <td><code><?= e($eq['serie']) ?></code></td>
                    <td><?= eqrBadgeEstado($eq['estado']) ?>
                        <?= $eq['fuera_instalaciones'] ? '<span class="badge bg-primary badge-estado">Fuera</span>' : '' ?></td>
                    <td><?= e((string)$eq['custodio_nombre']) ?: '—' ?><br><small class="text-muted"><?= e((string)$eq['custodio_cedula']) ?></small></td>
                    <td><?= e((string)$eq['custodio_area']) ?: '—' ?></td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="eqr_equipo_ficha.php?id=<?= (int)$eq['id'] ?>" title="Ficha"><i class="bi bi-card-list"></i></a>
                        <a class="btn btn-sm btn-outline-primary" href="eqr_equipos.php?editar=<?= (int)$eq['id'] ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
<?php eqrFooter();
