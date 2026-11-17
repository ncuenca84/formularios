<?php
/**
 * Actas y pagos del contrato de arrendamiento.
 *
 * Genera en PDF los documentos que exige la clausula sexta y decima quinta
 * del contrato (p. ej. ARCONEL-005-2026):
 *   - Acta de entrega-recepcion INICIAL de los equipos (primer pago)
 *   - Acta de entrega-recepcion PARCIAL mensual
 *   - Acta de entrega-recepcion DEFINITIVA (cierre del contrato)
 *   - Informe de conformidad del servicio mensual + solicitud de pago
 *
 * El PDF se descarga prellenado con los datos del contrato; el administrador
 * lo revisa, lo firma con FirmaEC y lo tramita por los canales habituales.
 */
require __DIR__ . '/eqr_common.php';
if (!eqrMigrado()) { header('Location: eqr_migrar.php'); exit; }
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$db = getDB();

// Si el servidor aun no tiene las columnas economicas (version anterior del
// esquema), llevar a la migracion para que las agregue con un clic.
try {
    $db->query("SELECT precio_unitario FROM eqr_contratos LIMIT 1");
} catch (Throwable $e) {
    header('Location: eqr_migrar.php');
    exit;
}

$tiposDoc = [
    'acta_inicial'    => 'Acta de entrega-recepción INICIAL de los equipos',
    'acta_parcial'    => 'Acta de entrega-recepción PARCIAL (mensual)',
    'informe_pago'    => 'Informe de conformidad del servicio y solicitud de pago (mensual)',
    'acta_definitiva' => 'Acta de entrega-recepción DEFINITIVA (cierre del contrato)',
];

$contratos = $db->query("SELECT * FROM eqr_contratos ORDER BY activo DESC, id DESC")->fetchAll();

// ------------------------------------------------------------- generar PDF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    eqrCsrfVerificar();

    $contratoId = (int)($_POST['contrato_id'] ?? 0);
    $tipoDoc = (string)($_POST['tipo_doc'] ?? '');
    $contrato = null;
    foreach ($contratos as $c) { if ((int)$c['id'] === $contratoId) { $contrato = $c; break; } }

    $errores = [];
    if (!$contrato) { $errores[] = 'Seleccione un contrato válido.'; }
    if (!isset($tiposDoc[$tipoDoc])) { $errores[] = 'Seleccione el tipo de documento.'; }

    if (!$errores) {
        // Cantidad de equipos: lo que escriba el admin o, si lo deja vacio,
        // los equipos del contrato que no estan retirados.
        $cantidad = (int)($_POST['cantidad_equipos'] ?? 0);
        if ($cantidad <= 0) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM eqr_equipos WHERE contrato_id = ? AND estado <> 'retirado'");
            $stmt->execute([$contratoId]);
            $cantidad = (int)$stmt->fetchColumn();
            if ($cantidad <= 0) { $cantidad = (int)$contrato['total_equipos']; }
        }

        $datosDoc = [
            'tipo_doc' => $tipoDoc,
            'titulo_doc' => $tiposDoc[$tipoDoc],
            'fecha_doc' => ($_POST['fecha_doc'] ?? '') ?: date('Y-m-d'),
            'lugar' => trim((string)($_POST['lugar'] ?? 'Quito')),
            'numero_doc' => trim((string)($_POST['numero_doc'] ?? '')),
            'periodo_desde' => (string)($_POST['periodo_desde'] ?? ''),
            'periodo_hasta' => (string)($_POST['periodo_hasta'] ?? ''),
            'cantidad_equipos' => $cantidad,
            'nro_factura' => trim((string)($_POST['nro_factura'] ?? '')),
            'sla_cumplido' => (string)($_POST['sla_cumplido'] ?? 'si'),
            'observaciones' => trim((string)($_POST['observaciones'] ?? '')),
        ];

        $config = getAllConfig();
        $meta = getFormularioMeta(20);
        $logoDataUri = getLogoDataUri('logo_path');
        $logoSecundarioDataUri = getLogoDataUri('logo_secundario_path');

        $html = require __DIR__ . '/../templates/pdf_eqr_contrato_doc.php';

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $options->set('isFontSubsettingEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        eqrBitacora(null, null, 'doc_contrato_generado', [
            'contrato' => $contrato['numero'],
            'tipo' => $tipoDoc,
            'periodo' => $datosDoc['periodo_desde'] . ' / ' . $datosDoc['periodo_hasta'],
            'cantidad_equipos' => $cantidad,
        ]);

        $sufijo = $datosDoc['periodo_desde'] !== '' ? '_' . $datosDoc['periodo_desde'] : '_' . $datosDoc['fecha_doc'];
        $nombreArchivo = strtoupper($tipoDoc) . '_' . preg_replace('/[^A-Za-z0-9\-]/', '_', $contrato['numero']) . $sufijo . '.pdf';
        $dompdf->stream($nombreArchivo, ['Attachment' => true]);
        exit;
    }
}

// -------------------------------------------------------------------- vista
$contratoActivo = null;
foreach ($contratos as $c) { if (!empty($c['activo'])) { $contratoActivo = $c; break; } }
$equiposActivos = 0;
if ($contratoActivo) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM eqr_equipos WHERE contrato_id = ? AND estado <> 'retirado'");
    $stmt->execute([(int)$contratoActivo['id']]);
    $equiposActivos = (int)$stmt->fetchColumn();
}

eqrHeader('Actas y pagos del contrato', 'pagos');
?>

<?php if (!empty($errores)): ?>
    <div class="alert alert-danger"><?php foreach ($errores as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<?php if (!$contratos): ?>
    <div class="alert alert-warning">Primero registre el contrato en
        <a href="eqr_contratos.php">Contratos</a>.</div>
<?php else: ?>

<div class="card p-4 mb-4">
    <h6>Generar documento del contrato</h6>
    <p class="text-muted small mb-3">
        Estos son los documentos que el contrato exige en su cláusula sexta (forma de pago) y décima
        quinta (entregas-recepciones). El PDF se descarga prellenado con los datos del contrato:
        revíselo, fírmelo con FirmaEC y tramítelo como siempre (Quipux / correo institucional).
        El pago mensual se calcula automáticamente: <strong>cantidad de equipos × valor unitario mensual</strong>.
    </p>
    <form method="post">
        <?= eqrCsrfCampo() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Contrato *</label>
                <select name="contrato_id" class="form-select" required>
                    <?php foreach ($contratos as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= !empty($c['activo']) ? 'selected' : '' ?>>
                            <?= e($c['numero']) ?> — <?= e($c['proveedor']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Tipo de documento *</label>
                <select name="tipo_doc" id="tipo_doc" class="form-select" required>
                    <?php foreach ($tiposDoc as $k => $v): ?>
                        <option value="<?= e($k) ?>"><?= e($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha del documento</label>
                <input name="fecha_doc" type="date" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Lugar</label>
                <input name="lugar" class="form-control" value="Quito">
            </div>
            <div class="col-md-6">
                <label class="form-label">Número del acta / informe (opcional)</label>
                <input name="numero_doc" class="form-control" placeholder="Ej: 001-2026 (numeración interna de la DTIC)">
            </div>
            <div class="col-md-3 campo-periodo">
                <label class="form-label">Período facturado: desde</label>
                <input name="periodo_desde" type="date" class="form-control">
            </div>
            <div class="col-md-3 campo-periodo">
                <label class="form-label">Período facturado: hasta</label>
                <input name="periodo_hasta" type="date" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Cantidad de equipos</label>
                <input name="cantidad_equipos" type="number" min="0" class="form-control"
                       value="<?= $equiposActivos ?: '' ?>" placeholder="<?= $equiposActivos ?: 60 ?>">
                <div class="form-text">Equipos efectivamente arrendados en el período.
                    Registrados y activos ahora: <strong><?= $equiposActivos ?></strong>.</div>
            </div>
            <div class="col-md-3 campo-pago">
                <label class="form-label">N° de factura del proveedor</label>
                <input name="nro_factura" class="form-control" placeholder="Ej: 001-001-000001234">
            </div>
            <div class="col-md-3 campo-pago">
                <label class="form-label">¿Se cumplieron los niveles de servicio (SLA)?</label>
                <select name="sla_cumplido" class="form-select">
                    <option value="si">Sí, sin observaciones</option>
                    <option value="con_observaciones">Sí, con observaciones (detállelas abajo)</option>
                </select>
            </div>
            <div class="col-md-12">
                <label class="form-label">Observaciones / incidencias del período (opcional)</label>
                <textarea name="observaciones" class="form-control" rows="3"
                          placeholder="Ej: El equipo REN-0012 fue repuesto el 10/10/2026 dentro de las 48 horas previstas."></textarea>
            </div>
        </div>
        <div class="mt-3">
            <button class="btn btn-success"><i class="bi bi-file-earmark-pdf"></i> Generar PDF</button>
        </div>
    </form>
</div>

<div class="card p-3">
    <h6>¿Qué documento corresponde en cada momento?</h6>
    <table class="table table-sm mb-0">
        <tbody>
        <tr><td><strong>Acta inicial</strong></td>
            <td>Una sola vez, al recibir la totalidad de los equipos a conformidad. Habilita el primer pago.
                La firman el administrador del contrato y el representante legal del contratista.</td></tr>
        <tr><td><strong>Cada mes</strong></td>
            <td>1) El proveedor entrega factura e informe mensual de cumplimiento. 2) Usted genera el
                <strong>informe de conformidad y solicitud de pago</strong> y el <strong>acta parcial</strong>
                del mes. El pago no debe exceder 30 días desde el cumplimiento de requisitos.</td></tr>
        <tr><td><strong>Acta definitiva</strong></td>
            <td>Al terminar el contrato, una vez retirados los equipos, ejecutado el borrado seguro
                bajo coordinación de la DTIC y entregados los certificados de borrado.</td></tr>
        </tbody>
    </table>
</div>

<script>
// Muestra los campos de factura/SLA solo cuando aplican
document.getElementById('tipo_doc').addEventListener('change', function () {
    var esPago = this.value === 'informe_pago';
    var esPeriodo = this.value === 'informe_pago' || this.value === 'acta_parcial';
    document.querySelectorAll('.campo-pago').forEach(function (el) { el.style.display = esPago ? '' : 'none'; });
    document.querySelectorAll('.campo-periodo').forEach(function (el) { el.style.display = esPeriodo ? '' : 'none'; });
});
document.getElementById('tipo_doc').dispatchEvent(new Event('change'));
</script>

<?php endif; ?>
<?php eqrFooter();
