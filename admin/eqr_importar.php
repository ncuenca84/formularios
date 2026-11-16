<?php
/**
 * Importador CSV de equipos (carga inicial de los 60 equipos ya entregados).
 *
 * Flujo en dos pasos:
 *   1) Subir el CSV -> vista previa con los errores por fila. Nada se guarda.
 *   2) Confirmar -> importa dentro de UNA transaccion: equipos + personas +
 *      tramite carga_inicial completado (con referencia del acta fisica) + bitacora.
 */
require __DIR__ . '/eqr_common.php';
if (!eqrMigrado()) { header('Location: eqr_migrar.php'); exit; }

$db = getDB();
$contratos = $db->query("SELECT id, numero FROM eqr_contratos WHERE activo = 1 ORDER BY id DESC")->fetchAll();

const EQR_CSV_COLS = [
    'codigo_interno', 'tipo', 'marca', 'modelo', 'procesador', 'ram', 'almacenamiento',
    'pantalla', 'serie', 'cargador_entregado', 'serie_cargador', 'estado_fisico',
    'estado_operativo', 'observaciones', 'custodio_cedula', 'custodio_nombre',
    'custodio_correo', 'custodio_cargo', 'custodio_area', 'referencia_acta',
];

$tmpDir = __DIR__ . '/../storage/tmp';
@mkdir($tmpDir, 0775, true);

/** Lee y valida el CSV; devuelve [filas, erroresGlobales]. */
function eqrLeerCsv(string $path, PDO $db): array
{
    $fh = fopen($path, 'r');
    if (!$fh) { return [[], ['No se pudo leer el archivo.']]; }

    $contenido = stream_get_contents($fh);
    rewind($fh);
    // normalizar BOM
    $primera = fgets($fh);
    if ($primera === false) { return [[], ['El archivo está vacío.']]; }
    $primera = preg_replace('/^\xEF\xBB\xBF/', '', $primera);
    $sep = (substr_count($primera, ';') > substr_count($primera, ',')) ? ';' : ',';
    rewind($fh);
    if (strpos($contenido, "\xEF\xBB\xBF") === 0) { fseek($fh, 3); }

    $cab = fgetcsv($fh, 0, $sep);
    $cab = array_map(fn($c) => strtolower(trim((string)$c)), $cab ?: []);
    $faltan = array_diff(['codigo_interno', 'serie'], $cab);
    if ($faltan) {
        return [[], ['Faltan columnas obligatorias en la cabecera: ' . implode(', ', $faltan)
            . '. Descargue la plantilla para ver el formato.']];
    }

    // duplicados ya en BD
    $codigosBD = array_column($db->query("SELECT codigo_interno FROM eqr_equipos")->fetchAll(), 'codigo_interno');
    $seriesBD = array_column($db->query("SELECT serie FROM eqr_equipos")->fetchAll(), 'serie');
    $codigosBD = array_map('strtoupper', $codigosBD);
    $seriesBD = array_map('strtoupper', $seriesBD);

    $filas = [];
    $vistosCod = [];
    $vistosSerie = [];
    $n = 1;
    while (($row = fgetcsv($fh, 0, $sep)) !== false) {
        $n++;
        if (count($row) === 1 && trim((string)$row[0]) === '') { continue; }
        $f = [];
        foreach ($cab as $i => $col) {
            if (in_array($col, EQR_CSV_COLS, true)) {
                $f[$col] = trim((string)($row[$i] ?? ''));
            }
        }
        foreach (EQR_CSV_COLS as $c) { $f[$c] = $f[$c] ?? ''; }
        $f['codigo_interno'] = strtoupper($f['codigo_interno']);
        $f['serie'] = strtoupper($f['serie']);
        $f['custodio_cedula'] = preg_replace('/\D/', '', $f['custodio_cedula']);

        $err = [];
        if ($f['codigo_interno'] === '') { $err[] = 'código interno vacío'; }
        elseif (in_array($f['codigo_interno'], $codigosBD, true)) { $err[] = 'el código ya existe en el sistema'; }
        elseif (isset($vistosCod[$f['codigo_interno']])) { $err[] = 'código repetido en el archivo (fila ' . $vistosCod[$f['codigo_interno']] . ')'; }

        if ($f['serie'] === '') { $err[] = 'serie vacía'; }
        elseif (in_array($f['serie'], $seriesBD, true)) { $err[] = 'la serie ya existe en el sistema'; }
        elseif (isset($vistosSerie[$f['serie']])) { $err[] = 'serie repetida en el archivo (fila ' . $vistosSerie[$f['serie']] . ')'; }

        $avisos = [];
        $conCustodio = $f['custodio_cedula'] !== '' || $f['custodio_nombre'] !== '';
        if ($conCustodio) {
            if ($f['custodio_cedula'] !== '' && !eqrValidarCedula($f['custodio_cedula'])) {
                // Excel suele quitar el 0 inicial de las cedulas: probar con el 0 de vuelta.
                if (strlen($f['custodio_cedula']) === 9 && eqrValidarCedula('0' . $f['custodio_cedula'])) {
                    $f['custodio_cedula'] = '0' . $f['custodio_cedula'];
                    $avisos[] = 'se restauró el 0 inicial de la cédula';
                } else {
                    $err[] = 'cédula del custodio inválida';
                }
            }
            if ($f['custodio_cedula'] === '') {
                $avisos[] = 'sin cédula (complétela luego en la ficha del equipo)';
            }
            if ($f['custodio_nombre'] === '') { $err[] = 'nombre del custodio vacío'; }
            if ($f['custodio_correo'] !== '' && !filter_var($f['custodio_correo'], FILTER_VALIDATE_EMAIL)) {
                $err[] = 'correo del custodio inválido';
            }
        }
        $f['_fila'] = $n;
        $f['_errores'] = $err;
        $f['_avisos'] = $avisos;
        $f['_con_custodio'] = $conCustodio;
        $vistosCod[$f['codigo_interno']] = $n;
        $vistosSerie[$f['serie']] = $n;
        $filas[] = $f;
    }
    fclose($fh);
    if (!$filas) { return [[], ['El archivo no contiene filas de datos.']]; }
    return [$filas, []];
}

$paso = 'subir';
$filas = [];
$erroresGlobales = [];
$resultado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    eqrCsrfVerificar();
    $accion = (string)($_POST['accion'] ?? '');

    if ($accion === 'previsualizar') {
        $contratoId = (int)($_POST['contrato_id'] ?? 0);
        if ($contratoId <= 0) {
            $erroresGlobales[] = 'Seleccione el contrato.';
        } elseif (empty($_FILES['csv']['tmp_name']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
            $erroresGlobales[] = 'Suba un archivo CSV.';
        } else {
            [$filas, $erroresGlobales] = eqrLeerCsv($_FILES['csv']['tmp_name'], $db);
            if (!$erroresGlobales) {
                $token = bin2hex(random_bytes(16));
                file_put_contents("$tmpDir/import_$token.json", json_encode([
                    'contrato_id' => $contratoId, 'filas' => $filas, 'ts' => time(),
                ]));
                $_SESSION['eqr_import_token'] = $token;
                $paso = 'previsualizar';
            }
        }
    }

    if ($accion === 'confirmar') {
        $token = (string)($_POST['token'] ?? '');
        $okTok = $token !== '' && hash_equals((string)($_SESSION['eqr_import_token'] ?? ''), $token);
        $file = "$tmpDir/import_$token.json";
        if (!$okTok || !is_file($file)) {
            $erroresGlobales[] = 'La vista previa expiró. Vuelva a subir el archivo.';
        } else {
            $datos = json_decode((string)file_get_contents($file), true);
            $contratoId = (int)$datos['contrato_id'];
            $filas = $datos['filas'];
            $conError = array_filter($filas, fn($f) => !empty($f['_errores']));
            if ($conError) {
                $erroresGlobales[] = 'El archivo tiene filas con errores: corríjalas y vuelva a subirlo.';
                $paso = 'previsualizar';
            } else {
                $db->beginTransaction();
                try {
                    $insEq = $db->prepare("INSERT INTO eqr_equipos
                        (contrato_id, codigo_interno, tipo, marca, modelo, procesador, ram,
                         almacenamiento, pantalla, serie, cargador_entregado, serie_cargador,
                         estado_fisico, estado_operativo, observaciones, estado,
                         custodio_cedula, custodio_nombre, custodio_correo, custodio_cargo, custodio_area)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                    $insPer = $db->prepare("INSERT INTO eqr_personas (cedula, nombres, correo, cargo, area)
                        VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE nombres=VALUES(nombres),
                        correo=VALUES(correo), cargo=VALUES(cargo), area=VALUES(area)");
                    $insTr = $db->prepare("INSERT INTO eqr_tramites
                        (codigo, tipo, equipo_id, contrato_id, estado,
                         recibe_cedula, recibe_nombre, recibe_correo, recibe_cargo, recibe_area,
                         referencia_acta_fisica, creado_por, completado_en)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
                    $insBit = $db->prepare("INSERT INTO eqr_bitacora (equipo_id, tramite_id, accion, detalle, usuario, ip)
                        VALUES (?,?,?,?,?,?)");

                    $nOk = 0;
                    foreach ($filas as $i => $f) {
                        $conC = !empty($f['_con_custodio']);
                        $insEq->execute([
                            $contratoId, $f['codigo_interno'],
                            $f['tipo'] !== '' ? $f['tipo'] : 'Computador portátil',
                            $f['marca'], $f['modelo'], $f['procesador'], $f['ram'],
                            $f['almacenamiento'], $f['pantalla'], $f['serie'],
                            in_array(strtolower($f['cargador_entregado']), ['no','0',''], true) ? 0 : 1,
                            $f['serie_cargador'], $f['estado_fisico'], $f['estado_operativo'],
                            $f['observaciones'], $conC ? 'asignado' : 'en_stock',
                            ($conC && $f['custodio_cedula'] !== '') ? $f['custodio_cedula'] : null,
                            $conC ? $f['custodio_nombre'] : null,
                            $conC ? $f['custodio_correo'] : null,
                            $conC ? $f['custodio_cargo'] : null,
                            $conC ? $f['custodio_area'] : null,
                        ]);
                        $eqId = (int)$db->lastInsertId();
                        $trId = null;
                        if ($conC) {
                            if ($f['custodio_cedula'] !== '') {
                                $insPer->execute([$f['custodio_cedula'], $f['custodio_nombre'],
                                    $f['custodio_correo'], $f['custodio_cargo'], $f['custodio_area']]);
                            }
                            $codTr = sprintf('TRM-%s-CI%04d', date('Ymd'), $i + 1);
                            $insTr->execute([$codTr, 'carga_inicial', $eqId, $contratoId, 'completado',
                                $f['custodio_cedula'] !== '' ? $f['custodio_cedula'] : null,
                                $f['custodio_nombre'], $f['custodio_correo'],
                                $f['custodio_cargo'], $f['custodio_area'],
                                $f['referencia_acta'] !== '' ? $f['referencia_acta'] : null,
                                'admin:' . ($_SESSION['admin_nombre'] ?? ''),
                            ]);
                            $trId = (int)$db->lastInsertId();
                        }
                        $insBit->execute([$eqId, $trId, 'carga_inicial_csv',
                            json_encode(['codigo' => $f['codigo_interno'], 'fila' => $f['_fila'],
                                         'acta_fisica' => $f['referencia_acta']], JSON_UNESCAPED_UNICODE),
                            'admin:' . ($_SESSION['admin_nombre'] ?? ''),
                            $_SERVER['REMOTE_ADDR'] ?? null]);
                        $nOk++;
                    }
                    $db->commit();
                    @unlink($file);
                    unset($_SESSION['eqr_import_token']);
                    eqrFlash("Importación completada: $nOk equipo(s) cargado(s).");
                    header('Location: eqr_equipos.php');
                    exit;
                } catch (Throwable $ex) {
                    $db->rollBack();
                    $erroresGlobales[] = 'La importación se canceló por un error (no se guardó nada): ' . $ex->getMessage();
                    $paso = 'previsualizar';
                }
            }
        }
    }
}

eqrHeader('Importar equipos por CSV', 'importar');
?>
<?php if ($erroresGlobales): ?>
    <div class="alert alert-danger"><?php foreach ($erroresGlobales as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<?php if ($paso === 'subir'): ?>
<div class="card p-4" style="max-width:760px">
    <h6>Paso 1 de 2: suba el archivo CSV</h6>
    <p class="text-muted small">Para la carga inicial de los equipos ya entregados con actas en papel.
    Cada fila con custodio crea el equipo como <strong>Asignado</strong> y un trámite
    «carga inicial» completado. Las filas sin custodio quedan <strong>En stock</strong>.
    La cédula del custodio y la referencia del acta física son <strong>opcionales</strong>:
    puede importar sin ellas y completar la cédula más adelante en la ficha del equipo
    (hará falta cuando esa persona deba firmar trámites en línea).</p>
    <p><a class="btn btn-outline-primary btn-sm" href="eqr_plantilla_csv.php">
        <i class="bi bi-download"></i> Descargar plantilla CSV</a></p>
    <?php if (!$contratos): ?>
        <div class="alert alert-warning">Primero registre un <a href="eqr_contratos.php?nuevo=1">contrato activo</a>.</div>
    <?php else: ?>
    <form method="post" enctype="multipart/form-data">
        <?= eqrCsrfCampo() ?>
        <input type="hidden" name="accion" value="previsualizar">
        <div class="mb-3">
            <label class="form-label">Contrato al que pertenecen los equipos *</label>
            <select name="contrato_id" class="form-select" required>
                <?php foreach ($contratos as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= e($c['numero']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Archivo CSV *</label>
            <input type="file" name="csv" class="form-control" accept=".csv,text/csv" required>
            <small class="text-muted">Separado por comas o punto y coma; UTF-8 (la plantilla ya lo está).</small>
        </div>
        <button class="btn btn-primary"><i class="bi bi-search"></i> Ver vista previa (no guarda nada)</button>
    </form>
    <?php endif; ?>
</div>

<?php elseif ($paso === 'previsualizar'):
    $conErr = array_filter($filas, fn($f) => !empty($f['_errores']));
    $sinErr = count($filas) - count($conErr);
    $conAviso = count(array_filter($filas, fn($f) => empty($f['_errores']) && !empty($f['_avisos'])));
?>
<div class="card p-4 mb-3">
    <h6>Paso 2 de 2: revise y confirme</h6>
    <p>
        <span class="badge bg-success"><?= $sinErr ?> fila(s) correctas</span>
        <span class="badge bg-danger"><?= count($conErr) ?> fila(s) con errores</span>
        <?php if ($conAviso): ?>
            <span class="badge bg-warning text-dark"><?= $conAviso ?> fila(s) con avisos (se importan igual)</span>
        <?php endif; ?>
    </p>
    <?php if (!$conErr): ?>
        <form method="post" class="d-inline">
            <?= eqrCsrfCampo() ?>
            <input type="hidden" name="accion" value="confirmar">
            <input type="hidden" name="token" value="<?= e($_SESSION['eqr_import_token'] ?? '') ?>">
            <button class="btn btn-success"><i class="bi bi-check-lg"></i> Confirmar importación</button>
        </form>
    <?php else: ?>
        <div class="alert alert-warning mb-2">Corrija las filas marcadas en su archivo y vuelva a subirlo.
        No se importará nada mientras haya errores.</div>
    <?php endif; ?>
    <a href="eqr_importar.php" class="btn btn-outline-secondary">Volver a subir</a>
</div>
<div class="card p-3">
    <div class="table-responsive"><table class="table table-sm">
        <thead><tr><th>Fila</th><th>Código</th><th>Equipo</th><th>Serie</th><th>Custodio</th><th>Acta física</th><th>Resultado</th></tr></thead>
        <tbody>
        <?php foreach ($filas as $f): ?>
            <tr class="<?= $f['_errores'] ? 'table-danger' : '' ?>">
                <td><?= (int)$f['_fila'] ?></td>
                <td><?= e($f['codigo_interno']) ?></td>
                <td><?= e($f['marca']) ?> <?= e($f['modelo']) ?></td>
                <td><code><?= e($f['serie']) ?></code></td>
                <td><?= $f['_con_custodio'] ? e($f['custodio_nombre']) . '<br><small>' . e($f['custodio_cedula']) . '</small>' : '<span class="text-muted">en stock</span>' ?></td>
                <td><?= e($f['referencia_acta']) ?: '—' ?></td>
                <td><?php
                    if ($f['_errores']) {
                        echo '<span class="text-danger">' . e(implode('; ', $f['_errores'])) . '</span>';
                    } elseif (!empty($f['_avisos'])) {
                        echo '<span class="text-success">OK</span> <span class="text-warning-emphasis"><i class="bi bi-exclamation-triangle"></i> '
                            . e(implode('; ', $f['_avisos'])) . '</span>';
                    } else {
                        echo '<span class="text-success">OK</span>';
                    }
                ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php endif; ?>
<?php eqrFooter();
