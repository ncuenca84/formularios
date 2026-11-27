<?php
/** Configuracion del modulo: dominio de correo, dias del enlace, URL base y prueba SMTP. */
require __DIR__ . '/eqr_common.php';
if (!eqrMigrado()) { header('Location: eqr_migrar.php'); exit; }
require_once __DIR__ . '/../includes/eqr/Notificador.php';

$db = getDB();
$resultadoPrueba = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    eqrCsrfVerificar();
    $accion = (string)($_POST['accion'] ?? '');

    if ($accion === 'guardar') {
        $valores = [
            'eqr_dominio_correo' => strtolower(trim((string)($_POST['eqr_dominio_correo'] ?? 'arconel.gob.ec'))),
            'eqr_dias_token' => (string)max(1, min(90, (int)($_POST['eqr_dias_token'] ?? 15))),
            'eqr_url_base' => rtrim(trim((string)($_POST['eqr_url_base'] ?? '')), '/'),
        ];
        $stmt = $db->prepare("INSERT INTO configuracion (clave, valor) VALUES (?, ?)
                              ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
        foreach ($valores as $k => $v) { $stmt->execute([$k, $v]); }
        eqrFlash('Configuración guardada.');
        header('Location: eqr_config.php');
        exit;
    }

    if ($accion === 'probar_correo') {
        $para = trim((string)($_POST['correo_prueba'] ?? ''));
        if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
            $resultadoPrueba = ['ok' => false, 'error' => 'Escriba un correo de destino válido.'];
        } else {
            $resultadoPrueba = (new Notificador())->correoPrueba($db, $para);
        }
    }
}

$cfg = getAllConfig();
$notif = new Notificador();

$ultimas = $db->query("SELECT * FROM eqr_notificaciones ORDER BY id DESC LIMIT 15")->fetchAll();

eqrHeader('Configuración del módulo', 'config');
?>
<div class="row g-3">
<div class="col-lg-6">
    <div class="card p-4 mb-3">
        <h6>Parámetros del módulo</h6>
        <form method="post">
            <?= eqrCsrfCampo() ?>
            <input type="hidden" name="accion" value="guardar">
            <div class="mb-3">
                <label class="form-label">Dominio de correo institucional permitido</label>
                <input name="eqr_dominio_correo" class="form-control"
                       value="<?= e($cfg['eqr_dominio_correo'] ?? 'arconel.gob.ec') ?>">
                <div class="form-text">Los firmantes solo pueden tener correos de este dominio.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Días de validez del enlace de firma</label>
                <input name="eqr_dias_token" type="number" min="1" max="90" class="form-control"
                       value="<?= e($cfg['eqr_dias_token'] ?? '15') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">URL base del sistema</label>
                <input name="eqr_url_base" class="form-control" placeholder="https://formularios.arconel.gob.ec"
                       value="<?= e($cfg['eqr_url_base'] ?? '') ?>">
                <div class="form-text"><strong>Obligatoria para los enlaces de firma</strong> que van en los
                    correos y para el QR de verificación de las actas.</div>
            </div>
            <button class="btn btn-success"><i class="bi bi-save"></i> Guardar</button>
        </form>
    </div>
</div>

<div class="col-lg-6">
    <div class="card p-4 mb-3">
        <h6>Correo SMTP institucional</h6>
        <?php if ($notif->configurado()): ?>
            <p class="small"><span class="badge bg-success">config/mail.php encontrado</span>
                El sistema enviará los correos por el SMTP configurado en el servidor.</p>
        <?php else: ?>
            <div class="alert alert-warning small">
                <strong>SMTP sin configurar.</strong> En el servidor, copie
                <code>config/mail.example.php</code> como <code>config/mail.php</code> y coloque la clave
                del buzón. Mientras tanto, los trámites funcionan igual pero los correos quedarán
                registrados como fallidos y usted deberá reenviar los enlaces manualmente.
            </div>
        <?php endif; ?>
        <form method="post" class="row g-2 align-items-end">
            <?= eqrCsrfCampo() ?>
            <input type="hidden" name="accion" value="probar_correo">
            <div class="col-8">
                <label class="form-label small">Enviar correo de prueba a:</label>
                <input name="correo_prueba" type="email" class="form-control form-control-sm"
                       placeholder="su.correo@arconel.gob.ec" required>
            </div>
            <div class="col-4">
                <button class="btn btn-outline-primary btn-sm"><i class="bi bi-envelope"></i> Probar</button>
            </div>
        </form>
        <?php if ($resultadoPrueba !== null): ?>
            <div class="alert alert-<?= $resultadoPrueba['ok'] ? 'success' : 'danger' ?> small mt-3 mb-0">
                <?= $resultadoPrueba['ok']
                    ? 'Correo de prueba enviado. Revise la bandeja de entrada (y la de spam).'
                    : 'Falló el envío: ' . e((string)$resultadoPrueba['error']) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card p-3">
        <h6>Últimos correos del módulo</h6>
        <?php if (!$ultimas): ?><p class="text-muted small mb-0">Sin envíos registrados.</p>
        <?php else: foreach ($ultimas as $n): ?>
            <div class="small border-bottom py-1">
                <?= $n['resultado'] === 'enviado'
                    ? '<span class="badge bg-success">enviado</span>'
                    : '<span class="badge bg-danger">fallido</span>' ?>
                <?= e($n['destinatario']) ?> — <?= e($n['asunto']) ?>
                <span class="text-muted"><?= e(substr((string)$n['fecha'], 0, 16)) ?></span>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
</div>
<?php eqrFooter();
