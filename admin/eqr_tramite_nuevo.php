<?php
/**
 * Asistente "Nuevo tramite" (asignacion, traspaso, devolucion).
 *  Paso 1: tipo + equipo (solo los elegibles segun las reglas 3.2)
 *  Paso 2: datos de las personas (con autocompletado por cedula)
 *  Crear: genera el tramite en BORRADOR con su acta y lleva a la firma
 *         del administrador (eqr_tramite_firmar.php).
 */
require __DIR__ . '/eqr_common.php';
if (!eqrMigrado()) { header('Location: eqr_migrar.php'); exit; }
require_once __DIR__ . '/../includes/eqr/flujo.php';

$db = getDB();
$errores = [];

$contrato = $db->query("SELECT * FROM eqr_contratos WHERE activo = 1 ORDER BY id DESC LIMIT 1")->fetch();
if (!$contrato) {
    eqrFlash('Primero registre un contrato activo.', 'error');
    header('Location: eqr_contratos.php');
    exit;
}

$tiposTramite = [
    'asignacion' => ['Asignación', 'La DTIC entrega un equipo EN STOCK a un funcionario. Firman: administrador y nuevo custodio.'],
    'traspaso'   => ['Traspaso', 'Un equipo asignado pasa de un funcionario a otro. Firman: administrador, custodio anterior y nuevo custodio.'],
    'devolucion' => ['Devolución', 'El custodio devuelve el equipo a la DTIC (vuelve a stock). Firman: administrador y custodio que devuelve.'],
    'reposicion' => ['Reposición', 'El proveedor repuso el equipo (robo o daño) y se re-emite el acta al MISMO custodio con los datos nuevos. Actualice primero la ficha del equipo (modelo y serie). Firman: administrador y custodio.'],
];

$dominio = getConfig('eqr_dominio_correo', 'arconel.gob.ec');

// ----------------------------------------------------- equipos elegibles
$equipos = $db->prepare("SELECT e.*, (SELECT COUNT(*) FROM eqr_tramites t WHERE t.equipo_id = e.id
                            AND t.tipo IN ('asignacion','traspaso','devolucion','reposicion')
                            AND t.estado IN ('borrador','en_firma')) abiertos
                         FROM eqr_equipos e WHERE e.contrato_id = ? AND e.estado IN ('en_stock','asignado')
                         ORDER BY e.codigo_interno");
$equipos->execute([(int)$contrato['id']]);
$equipos = $equipos->fetchAll();

$paso = 1;
$tipo = '';
$equipoSel = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    eqrCsrfVerificar();
    $accion = (string)($_POST['accion'] ?? '');
    $tipo = (string)($_POST['tipo'] ?? '');
    $equipoId = (int)($_POST['equipo_id'] ?? 0);

    if (!isset($tiposTramite[$tipo])) { $errores[] = 'Seleccione el tipo de trámite.'; }
    foreach ($equipos as $eq) { if ((int)$eq['id'] === $equipoId) { $equipoSel = $eq; break; } }
    if (!$equipoSel) { $errores[] = 'Seleccione el equipo.'; }

    // reglas de elegibilidad
    if (!$errores) {
        if ((int)$equipoSel['abiertos'] > 0) {
            $errores[] = 'Ese equipo ya tiene un trámite de custodia abierto. Ciérrelo o anúlelo primero.';
        } elseif ($tipo === 'asignacion' && $equipoSel['estado'] !== 'en_stock') {
            $errores[] = 'Para una asignación el equipo debe estar EN STOCK.';
        } elseif (in_array($tipo, ['traspaso', 'devolucion', 'reposicion'], true) && $equipoSel['estado'] !== 'asignado') {
            $errores[] = 'Para un trámite de ' . $tipo . ' el equipo debe estar ASIGNADO.';
        } elseif (in_array($tipo, ['traspaso', 'devolucion'], true) && empty($equipoSel['custodio_cedula'])) {
            $errores[] = 'El custodio actual de ese equipo no tiene cédula registrada. Complétela primero en la ficha del equipo (Equipos → Editar), porque la firma se valida contra la cédula.';
        }
    }

    if (!$errores && $accion === 'datos') {
        $paso = 2;
    }

    if (!$errores && $accion === 'crear') {
        $paso = 2;
        $limpia = fn($k) => trim((string)($_POST[$k] ?? ''));
        $datos = [
            'motivo' => $limpia('motivo'),
            'estado_fisico_reportado' => $limpia('estado_fisico_reportado'),
            'accesorios' => $limpia('accesorios'),
            'observaciones' => $limpia('observaciones'),
        ];

        $validaPersona = function (string $pref, string $titulo) use (&$errores, $limpia, $dominio) {
            $ced = preg_replace('/\D/', '', $limpia($pref . 'cedula'));
            if (strlen($ced) === 9 && eqrValidarCedula('0' . $ced)) { $ced = '0' . $ced; }
            $correo = $limpia($pref . 'correo');
            if (!eqrValidarCedula($ced)) { $errores[] = "La cédula de \"$titulo\" no es válida (la firma se valida contra ella)."; }
            if ($limpia($pref . 'nombre') === '') { $errores[] = "Escriba los apellidos y nombres de \"$titulo\"."; }
            if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)
                || !str_ends_with(strtolower($correo), '@' . strtolower($dominio))) {
                $errores[] = "El correo de \"$titulo\" debe ser institucional (@$dominio): ahí llega su enlace de firma.";
            }
            return [
                $pref . 'cedula' => $ced,
                $pref . 'nombre' => $limpia($pref . 'nombre'),
                $pref . 'correo' => $correo,
                $pref . 'cargo' => $limpia($pref . 'cargo'),
                $pref . 'area' => $limpia($pref . 'area'),
            ];
        };

        if ($tipo === 'asignacion') {
            $datos += $validaPersona('recibe_', 'Nuevo custodio');
        } elseif ($tipo === 'reposicion') {
            $datos += $validaPersona('recibe_', 'Custodio que recibe la reposición');
        } elseif ($tipo === 'traspaso') {
            $datos += $validaPersona('entrega_', 'Custodio anterior');
            $datos += $validaPersona('recibe_', 'Nuevo custodio');
            if (!$errores && $datos['entrega_cedula'] !== $equipoSel['custodio_cedula']) {
                $errores[] = 'La cédula del custodio anterior no coincide con el custodio registrado del equipo.';
            }
            if (!$errores && $datos['entrega_cedula'] === $datos['recibe_cedula']) {
                $errores[] = 'El custodio anterior y el nuevo no pueden ser la misma persona.';
            }
        } elseif ($tipo === 'devolucion') {
            $datos += $validaPersona('entrega_', 'Custodio que devuelve');
            if (!$errores && $datos['entrega_cedula'] !== $equipoSel['custodio_cedula']) {
                $errores[] = 'La cédula del custodio que devuelve no coincide con el custodio registrado del equipo.';
            }
        }

        if (!$errores) {
            try {
                $usuario = 'admin:' . ($_SESSION['admin_nombre'] ?? 'desconocido');
                $tramiteId = eqrCrearTramite($db, $tipo, $equipoSel, $contrato, $datos, $usuario);
                header('Location: eqr_tramite_firmar.php?id=' . $tramiteId);
                exit;
            } catch (Throwable $ex) {
                $errores[] = 'No se pudo crear el trámite: ' . $ex->getMessage();
            }
        }
    }
}

eqrHeader('Nuevo trámite', 'tramites');
?>
<?php if ($errores): ?>
    <div class="alert alert-danger"><?php foreach ($errores as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div>
<?php endif; ?>
<?php if (trim(getConfig('eqr_url_base')) === ''): ?>
    <div class="alert alert-warning small">
        <i class="bi bi-exclamation-triangle"></i> Falta la <strong>URL base</strong> en
        <a href="eqr_config.php">Configuración</a>: sin ella, los enlaces de firma de los correos
        pueden salir incompletos.
    </div>
<?php endif; ?>

<?php
$hayDelegado = trim((string)($contrato['delegado_cedula'] ?? '')) !== '';
$firmanteDtic = $hayDelegado
    ? ['nombre' => $contrato['delegado_nombre'], 'cedula' => $contrato['delegado_cedula'],
       'rol' => 'delegado del administrador del contrato']
    : ['nombre' => $contrato['admin_nombre'], 'cedula' => $contrato['admin_cedula'],
       'rol' => 'administrador del contrato'];
?>
<div class="alert alert-light border small mb-3">
    <i class="bi bi-info-circle"></i> La primera firma del acta es del
    <strong><?= e($firmanteDtic['rol']) ?></strong>:
    <strong><?= e($firmanteDtic['nombre']) ?></strong> (C.C. <?= e($firmanteDtic['cedula']) ?>).
    La cédula de su certificado .p12 debe coincidir, o la firma será rechazada.
    Después, el sistema envía el enlace de firma a cada persona, una por una y en orden.
    <?php if ($hayDelegado): ?>
        <br><span class="text-muted">Delegación configurada en el contrato
        (<?= e((string)$contrato['delegado_memorando'] ?: 'sin referencia de memorando') ?>).
        Los documentos de pago del contrato siguen a nombre de <?= e($contrato['admin_nombre']) ?>.</span>
    <?php endif; ?>
</div>

<?php if ($paso === 1): ?>
<div class="card p-4" style="max-width:860px">
    <h6>Paso 1 de 3: tipo de trámite y equipo</h6>
    <form method="post">
        <?= eqrCsrfCampo() ?>
        <input type="hidden" name="accion" value="datos">
        <div class="mb-3">
            <?php foreach ($tiposTramite as $k => [$nombre, $desc]): ?>
                <div class="form-check mb-2">
                    <input class="form-check-input tipo-radio" type="radio" name="tipo" id="tipo_<?= e($k) ?>"
                           value="<?= e($k) ?>" <?= $tipo === $k ? 'checked' : '' ?> required>
                    <label class="form-check-label" for="tipo_<?= e($k) ?>">
                        <strong><?= e($nombre) ?></strong> — <span class="text-muted small"><?= e($desc) ?></span>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mb-3">
            <label class="form-label">Equipo *</label>
            <select name="equipo_id" id="equipo_id" class="form-select" required>
                <option value="">— Elija primero el tipo de trámite —</option>
                <?php foreach ($equipos as $eq): ?>
                    <option value="<?= (int)$eq['id'] ?>" data-estado="<?= e($eq['estado']) ?>"
                            data-abiertos="<?= (int)$eq['abiertos'] ?>" hidden>
                        <?= e($eq['codigo_interno']) ?> — <?= e($eq['marca']) ?> <?= e($eq['modelo']) ?>
                        (Serie <?= e($eq['serie']) ?>)<?=
                        $eq['estado'] === 'asignado' ? ' — Custodio: ' . e((string)$eq['custodio_nombre']) : ' — EN STOCK'
                        ?><?= (int)$eq['abiertos'] > 0 ? ' [TIENE TRÁMITE ABIERTO]' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Asignación: solo equipos en stock. Traspaso y devolución: solo equipos
                asignados y sin otro trámite abierto.</div>
        </div>
        <button class="btn btn-success">Continuar <i class="bi bi-arrow-right"></i></button>
        <a href="eqr_tramites.php" class="btn btn-outline-secondary">Cancelar</a>
    </form>
</div>
<script>
(function () {
    var sel = document.getElementById('equipo_id');
    function filtra() {
        var tipo = (document.querySelector('.tipo-radio:checked') || {}).value || '';
        var quiere = tipo === 'asignacion' ? 'en_stock' : (tipo ? 'asignado' : '');
        var primero = null;
        for (var i = 0; i < sel.options.length; i++) {
            var o = sel.options[i];
            if (!o.value) { continue; }
            var visible = quiere !== '' && o.dataset.estado === quiere && o.dataset.abiertos === '0';
            o.hidden = !visible;
            o.disabled = !visible;
            if (visible && !primero) { primero = o; }
        }
        sel.value = '';
    }
    document.querySelectorAll('.tipo-radio').forEach(function (r) { r.addEventListener('change', filtra); });
    filtra();
})();
</script>

<?php else: // paso 2
    // Solo conservar lo tecleado cuando se vuelve por un error de validacion;
    // al entrar por primera vez (accion=datos) se PRELLENA todo lo posible.
    $reenvio = (($_POST['accion'] ?? '') === 'crear');
    $custodioActual = [
        'cedula' => (string)$equipoSel['custodio_cedula'], 'nombre' => (string)$equipoSel['custodio_nombre'],
        'correo' => (string)$equipoSel['custodio_correo'], 'cargo' => (string)$equipoSel['custodio_cargo'],
        'area' => (string)$equipoSel['custodio_area'],
    ];
    $v = fn($k) => e((string)($_POST[$k] ?? ''));
?>
<div class="card p-4" style="max-width:920px">
    <h6>Paso 2 de 3: datos del trámite — <?= e($tiposTramite[$tipo][0]) ?> ·
        <?= e($equipoSel['codigo_interno']) ?> (<?= e($equipoSel['marca']) ?> <?= e($equipoSel['modelo']) ?>,
        serie <?= e($equipoSel['serie']) ?>)</h6>
    <form method="post">
        <?= eqrCsrfCampo() ?>
        <input type="hidden" name="accion" value="crear">
        <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
        <input type="hidden" name="equipo_id" value="<?= (int)$equipoSel['id'] ?>">

        <?php if (in_array($tipo, ['traspaso', 'devolucion'], true)): ?>
            <h6 class="mt-3 text-success"><?= $tipo === 'devolucion' ? 'Custodio que devuelve' : 'Custodio anterior (entrega)' ?></h6>
            <p class="text-muted small mb-2">Se toma del custodio registrado del equipo. Verifique su correo:
                ahí le llegará el enlace de firma.</p>
            <div class="row g-2">
                <div class="col-md-2"><label class="form-label">Cédula *</label>
                    <input name="entrega_cedula" class="form-control" maxlength="10" required readonly
                           value="<?= e($custodioActual['cedula']) ?>"></div>
                <div class="col-md-4"><label class="form-label">Apellidos y nombres *</label>
                    <input name="entrega_nombre" class="form-control" required value="<?= $reenvio ? $v('entrega_nombre') : e($custodioActual['nombre']) ?>"></div>
                <div class="col-md-3"><label class="form-label">Correo institucional *</label>
                    <input name="entrega_correo" type="email" class="form-control" required
                           placeholder="usuario@<?= e($dominio) ?>" value="<?= $reenvio ? $v('entrega_correo') : e($custodioActual['correo']) ?>"></div>
                <div class="col-md-3"><label class="form-label">Cargo</label>
                    <input name="entrega_cargo" class="form-control" value="<?= $reenvio ? $v('entrega_cargo') : e($custodioActual['cargo']) ?>"></div>
                <div class="col-md-6"><label class="form-label">Coordinación / Dirección</label>
                    <input name="entrega_area" class="form-control" value="<?= $reenvio ? $v('entrega_area') : e($custodioActual['area']) ?>"></div>
            </div>
        <?php endif; ?>

        <?php if (in_array($tipo, ['asignacion', 'traspaso', 'reposicion'], true)):
            // En una reposicion el que recibe es el MISMO custodio actual (prellenado)
            $pre = fn($k) => $reenvio ? $v('recibe_' . $k)
                : ($tipo === 'reposicion' ? e($custodioActual[$k] ?? '') : '');
        ?>
            <h6 class="mt-4 text-success"><?= $tipo === 'reposicion'
                ? 'Custodio que recibe la reposición (prellenado con el custodio actual)'
                : 'Nuevo custodio (recibe)' ?></h6>
            <p class="text-muted small mb-2">Escriba la cédula: si la persona ya existe en el sistema,
                sus datos se completan solos.</p>
            <div class="row g-2">
                <div class="col-md-2"><label class="form-label">Cédula *</label>
                    <input name="recibe_cedula" id="recibe_cedula" class="form-control" maxlength="10"
                           inputmode="numeric" required value="<?= $pre('cedula') ?>"></div>
                <div class="col-md-4"><label class="form-label">Apellidos y nombres *</label>
                    <input name="recibe_nombre" id="recibe_nombre" class="form-control" required value="<?= $pre('nombre') ?>"></div>
                <div class="col-md-3"><label class="form-label">Correo institucional *</label>
                    <input name="recibe_correo" id="recibe_correo" type="email" class="form-control" required
                           placeholder="usuario@<?= e($dominio) ?>" value="<?= $pre('correo') ?>"></div>
                <div class="col-md-3"><label class="form-label">Cargo</label>
                    <input name="recibe_cargo" id="recibe_cargo" class="form-control" value="<?= $pre('cargo') ?>"></div>
                <div class="col-md-6"><label class="form-label">Coordinación / Dirección</label>
                    <input name="recibe_area" id="recibe_area" class="form-control" value="<?= $pre('area') ?>"></div>
            </div>
        <?php endif; ?>

        <h6 class="mt-4 text-success">Condiciones del equipo</h6>
        <div class="row g-2">
            <div class="col-md-6"><label class="form-label">Estado físico reportado</label>
                <input name="estado_fisico_reportado" class="form-control"
                       value="<?= $reenvio ? $v('estado_fisico_reportado') : e((string)$equipoSel['estado_fisico']) ?>"></div>
            <div class="col-md-6"><label class="form-label">Accesorios que se entregan</label>
                <input name="accesorios" class="form-control"
                       value="<?= $reenvio ? $v('accesorios') : (!empty($equipoSel['cargador_entregado']) ? 'Cargador' : '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Motivo <?= $tipo === 'reposicion' ? '(aparece en el acta)' : '(opcional)' ?></label>
                <input name="motivo" class="form-control"
                       value="<?= $reenvio ? $v('motivo') : ($tipo === 'reposicion' ? 'Reposición por robo del equipo anterior, serie: ' : '') ?>"
                       placeholder="Ej: cambio de área del funcionario"></div>
            <div class="col-md-6"><label class="form-label">Observaciones (opcional)</label>
                <input name="observaciones" class="form-control" value="<?= $v('observaciones') ?>"></div>
        </div>

        <div class="mt-4">
            <button class="btn btn-success"><i class="bi bi-file-earmark-text"></i>
                Generar el acta y pasar a mi firma</button>
            <a href="eqr_tramite_nuevo.php" class="btn btn-outline-secondary">Volver</a>
        </div>
        <p class="text-muted small mt-2">Al continuar se genera el acta y el trámite queda en
            <strong>borrador</strong>. Nada se envía a nadie hasta que usted la firme en el paso 3.</p>
    </form>
</div>
<script>
(function () {
    var ced = document.getElementById('recibe_cedula');
    if (!ced) { return; }
    ced.addEventListener('blur', function () {
        var c = ced.value.replace(/\D/g, '');
        if (c.length < 9) { return; }
        fetch('eqr_personas_api.php?cedula=' + encodeURIComponent(c))
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok || !d.persona) { return; }
                var p = d.persona;
                var pon = function (id, val) {
                    var el = document.getElementById(id);
                    if (el && !el.value) { el.value = val || ''; }
                };
                pon('recibe_nombre', p.nombres);
                pon('recibe_correo', p.correo);
                pon('recibe_cargo', p.cargo);
                pon('recibe_area', p.area);
            })
            .catch(function () {});
    });
})();
</script>
<?php endif; ?>
<?php eqrFooter();
