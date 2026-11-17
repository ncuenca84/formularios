<?php
/**
 * Bandeja de FORMULARIOS firmados en linea (fir_documentos): estado de las
 * firmas, reenviar enlace, anular y descargar el PDF.
 */
require __DIR__ . '/eqr_common.php';
require_once __DIR__ . '/../includes/eqr/fir_flujo.php';

$db = getDB();
try { $db->query("SELECT 1 FROM fir_documentos LIMIT 1"); }
catch (Throwable $e) { header('Location: eqr_migrar.php'); exit; }

$usuario = 'admin:' . ($_SESSION['admin_nombre'] ?? 'desconocido');

// ------------------------------------------------------------------ acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    eqrCsrfVerificar();
    $id = (int)($_POST['id'] ?? 0);
    $doc = firDocumentoPorId($db, $id);
    $accion = (string)($_POST['accion'] ?? '');
    try {
        if (!$doc) {
            eqrFlash('Documento no encontrado.', 'error');
        } elseif ($accion === 'reenviar') {
            $r = firReenviarEnlace($db, $doc, $usuario);
            eqrFlash($r['ok'] ? 'Enlace reenviado a ' . $r['correo'] . ' (el anterior quedó sin efecto).'
                              : 'No se pudo reenviar: ' . $r['error'], $r['ok'] ? 'success' : 'error');
        } elseif ($accion === 'anular') {
            $motivo = trim((string)($_POST['motivo'] ?? ''));
            if (mb_strlen($motivo) < 5) {
                eqrFlash('Escriba el motivo de la anulación.', 'error');
            } elseif (!in_array($doc['estado'], ['borrador', 'en_firma'], true)) {
                eqrFlash('Solo se puede anular un documento en borrador o en firma.', 'error');
            } else {
                firAnularDocumento($db, $doc, $motivo, $usuario);
                eqrFlash('Documento anulado. Los enlaces pendientes quedaron sin efecto.');
            }
        }
    } catch (Throwable $ex) {
        eqrFlash('Error: ' . $ex->getMessage(), 'error');
    }
    header('Location: eqr_documentos.php?tab=' . urlencode((string)($_POST['tab'] ?? 'en_firma')));
    exit;
}

// ------------------------------------------------------------------ descarga
if (isset($_GET['descargar'])) {
    $doc = firDocumentoPorId($db, (int)$_GET['descargar']);
    $path = $doc ? firDocPath($doc['codigo']) : '';
    if (!$doc || !is_file($path)) { http_response_code(404); exit('Documento no encontrado.'); }
    header('Content-Type: application/pdf');
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: ' . (isset($_GET['inline']) ? 'inline' : 'attachment') .
           '; filename="' . preg_replace('/[^A-Za-z0-9\-]/', '_', $doc['codigo']) . '.pdf"');
    readfile($path);
    exit;
}

// -------------------------------------------------------------------- listado
$pestanas = [
    'en_firma' => ['En firma', 'warning'], 'borrador' => ['Sin primera firma', 'secondary'],
    'completado' => ['Completados', 'success'], 'rechazado' => ['Rechazados', 'danger'],
    'anulado' => ['Anulados', 'dark'],
];
$tab = (string)($_GET['tab'] ?? 'en_firma');
if (!isset($pestanas[$tab])) { $tab = 'en_firma'; }
$conteos = [];
foreach ($pestanas as $k => $v) {
    $conteos[$k] = (int)$db->query("SELECT COUNT(*) FROM fir_documentos WHERE estado = " . $db->quote($k))->fetchColumn();
}
$stmt = $db->prepare("SELECT d.*,
        (SELECT CONCAT(f.nombre, '|', f.etiqueta, '|', f.correo) FROM fir_firmas f
         WHERE f.documento_id = d.id AND f.estado = 'pendiente' ORDER BY f.orden LIMIT 1) turno
    FROM fir_documentos d WHERE d.estado = ? ORDER BY d.id DESC LIMIT 300");
$stmt->execute([$tab]);
$docs = $stmt->fetchAll();

$detalleId = (int)($_GET['ver'] ?? 0);
$detalle = $detalleId ? firDocumentoPorId($db, $detalleId) : null;
$firmasDet = $detalle ? firFirmasDe($db, $detalleId) : [];
$estadosFirma = ['en_espera' => ['secondary', 'En espera'], 'pendiente' => ['warning text-dark', 'Pendiente'],
                 'firmado' => ['success', 'Firmado'], 'rechazado' => ['danger', 'Rechazado']];

eqrHeader('Formularios en firma', 'documentos');
?>
<p class="text-muted small">Formularios institucionales (1 a 6) que se firman en línea. El solicitante
firma al enviar el formulario; los demás reciben su enlace por correo, en orden. Al completarse,
el PDF firmado llega a todos y al correo de soporte.</p>

<ul class="nav nav-tabs mb-3">
    <?php foreach ($pestanas as $k => [$nombre, $color]): ?>
        <li class="nav-item">
            <a class="nav-link <?= $k === $tab ? 'active' : '' ?>" href="eqr_documentos.php?tab=<?= e($k) ?>">
                <?= e($nombre) ?> <span class="badge bg-<?= e($color) ?>"><?= $conteos[$k] ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<?php if ($detalle): ?>
<div class="card p-3 mb-3">
    <div class="d-flex justify-content-between flex-wrap gap-2">
        <h6><?= e($detalle['titulo']) ?> — <?= e($detalle['codigo']) ?></h6>
        <div>
            <a class="btn btn-sm btn-outline-primary" href="eqr_documentos.php?descargar=<?= $detalleId ?>">
                <i class="bi bi-download"></i> Descargar PDF</a>
            <a class="btn btn-sm btn-outline-secondary" target="_blank"
               href="eqr_documentos.php?descargar=<?= $detalleId ?>&inline=1"><i class="bi bi-eye"></i> Ver</a>
            <a class="btn btn-sm btn-outline-secondary" href="eqr_documentos.php?tab=<?= e($tab) ?>">Cerrar</a>
        </div>
    </div>
    <?php if ($detalle['motivo_rechazo_anulacion']): ?>
        <div class="alert alert-danger small py-2"><?= e($detalle['motivo_rechazo_anulacion']) ?></div>
    <?php endif; ?>
    <?php foreach ($firmasDet as $f): [$cls, $txt] = $estadosFirma[$f['estado']] ?? ['light', $f['estado']]; ?>
        <div class="border rounded p-2 mb-1 small">
            <span class="badge bg-<?= $cls ?>"><?= e($txt) ?></span>
            <strong><?= (int)$f['orden'] ?>. <?= e($f['etiqueta'] ?: $f['rol']) ?>:</strong>
            <?= e($f['nombre']) ?> (C.C. <?= e($f['cedula']) ?>) — <?= e($f['correo']) ?>
            <?php if ($f['estado'] === 'firmado'): ?>
                <span class="text-muted">— firmado el <?= e((string)$f['fecha_firma']) ?>
                (<?= e((string)$f['cert_emisor']) ?>)</span>
            <?php elseif ($f['estado'] === 'pendiente' && $f['token_expira']): ?>
                <span class="text-muted">— enlace vence el <?= e(substr((string)$f['token_expira'], 0, 16)) ?></span>
            <?php elseif ($f['estado'] === 'rechazado'): ?>
                <span class="text-danger">— <?= e((string)$f['motivo_rechazo']) ?></span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card p-3">
    <?php if (!$docs): ?>
        <p class="text-muted mb-0">No hay documentos en esta pestaña.</p>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-hover table-sm mb-0">
            <thead><tr><th>Código</th><th>Formulario</th><th><?= $tab === 'en_firma' ? 'Turno de firma' : 'Fecha' ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($docs as $d): ?>
                <tr>
                    <td><strong><?= e($d['codigo']) ?></strong></td>
                    <td><?= e($d['titulo']) ?></td>
                    <td><small><?php
                        if ($tab === 'en_firma' && !empty($d['turno'])) {
                            [$nom, $eti, $cor] = array_pad(explode('|', $d['turno']), 3, '');
                            echo e($nom) . ' <span class="text-muted">(' . e($eti) . ' — ' . e($cor) . ')</span>';
                        } else {
                            echo e(substr((string)$d['created_at'], 0, 16));
                        }
                    ?></small></td>
                    <td class="text-end" style="white-space:nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="eqr_documentos.php?tab=<?= e($tab) ?>&ver=<?= (int)$d['id'] ?>">
                            <i class="bi bi-eye"></i> Ver</a>
                        <?php if (in_array($d['estado'], ['borrador', 'en_firma'], true)): ?>
                            <form method="post" class="d-inline">
                                <?= eqrCsrfCampo() ?>
                                <input type="hidden" name="accion" value="reenviar">
                                <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                <input type="hidden" name="tab" value="<?= e($tab) ?>">
                                <button class="btn btn-sm btn-outline-success" title="Reenviar enlace al firmante de turno">
                                    <i class="bi bi-envelope-arrow-up"></i></button>
                            </form>
                            <form method="post" class="d-inline"
                                  onsubmit="var m=prompt('Motivo de la anulación:'); if(!m) return false;
                                            this.motivo.value=m; return true;">
                                <?= eqrCsrfCampo() ?>
                                <input type="hidden" name="accion" value="anular">
                                <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                <input type="hidden" name="tab" value="<?= e($tab) ?>">
                                <input type="hidden" name="motivo" value="">
                                <button class="btn btn-sm btn-outline-dark" title="Anular">
                                    <i class="bi bi-x-octagon"></i></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
<?php eqrFooter();
