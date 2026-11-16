<?php
/**
 * Comun del modulo Equipos Rentados (admin): sesion, CSRF, helpers y layout.
 * Todas las paginas admin/eqr_*.php empiezan con: require __DIR__.'/eqr_common.php';
 */
declare(strict_types=1);

// Cookie de sesion HttpOnly: debe fijarse ANTES de iniciar la sesion.
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    session_start();
}
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/eqr_schema.php';

if (empty($_SESSION['admin_logged'])) {
    header('Location: login.php');
    exit;
}

/** Escapa para HTML. */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** Token CSRF de la sesion (se crea una vez). */
function eqrCsrfToken(): string
{
    if (empty($_SESSION['eqr_csrf'])) {
        $_SESSION['eqr_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['eqr_csrf'];
}

/** Campo oculto para formularios. */
function eqrCsrfCampo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(eqrCsrfToken()) . '">';
}

/** Verifica el token en un POST; corta con 403 si no coincide. */
function eqrCsrfVerificar(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { return; }
    $t = (string)($_POST['csrf'] ?? '');
    if ($t === '' || !hash_equals(eqrCsrfToken(), $t)) {
        http_response_code(403);
        exit('Solicitud rechazada (token de seguridad inválido). Vuelva atrás y recargue la página.');
    }
}

/** Valida una cedula ecuatoriana (10 digitos, modulo 10). */
function eqrValidarCedula(string $c): bool
{
    if (!preg_match('/^\d{10}$/', $c)) { return false; }
    $prov = (int)substr($c, 0, 2);
    if (($prov < 1 || $prov > 24) && $prov !== 30) { return false; }
    if ((int)$c[2] > 5) { return false; }
    $suma = 0;
    for ($i = 0; $i < 9; $i++) {
        $d = (int)$c[$i];
        if ($i % 2 === 0) { $d *= 2; if ($d > 9) { $d -= 9; } }
        $suma += $d;
    }
    $ver = (10 - ($suma % 10)) % 10;
    return $ver === (int)$c[9];
}

/** Registra una accion en la bitacora (solo inserciones). */
function eqrBitacora(?int $equipoId, ?int $tramiteId, string $accion, array $detalle = []): void
{
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO eqr_bitacora (equipo_id, tramite_id, accion, detalle, usuario, ip)
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $equipoId, $tramiteId, $accion,
        json_encode($detalle, JSON_UNESCAPED_UNICODE),
        'admin:' . ($_SESSION['admin_nombre'] ?? 'desconocido'),
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

/** Mensaje flash entre redirecciones. */
function eqrFlash(?string $msg = null, string $tipo = 'success'): ?array
{
    if ($msg !== null) {
        $_SESSION['eqr_flash'] = ['msg' => $msg, 'tipo' => $tipo];
        return null;
    }
    $f = $_SESSION['eqr_flash'] ?? null;
    unset($_SESSION['eqr_flash']);
    return $f;
}

/** Comprueba si el modulo ya fue migrado (existe eqr_contratos). */
function eqrMigrado(): bool
{
    try {
        getDB()->query("SELECT 1 FROM eqr_contratos LIMIT 1");
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

// ------------------------------------------------------------------- layout
function eqrHeader(string $titulo, string $activo = ''): void
{
    $config = getAllConfig();
    $flash = eqrFlash();
    $menu = [
        'dashboard' => ['eqr_dashboard.php', 'bi-speedometer2', 'Tablero'],
        'contratos' => ['eqr_contratos.php', 'bi-file-earmark-ruled', 'Contratos'],
        'equipos'   => ['eqr_equipos.php', 'bi-laptop', 'Equipos'],
        'importar'  => ['eqr_importar.php', 'bi-upload', 'Importar CSV'],
    ];
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> — Equipos Rentados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 250px; }
        body { background: #f0f2f5; }
        .sidebar { width: var(--sidebar-width); min-height: 100vh; background: #14532d;
                   position: fixed; top: 0; left: 0; color: #fff; padding-top: 1rem; }
        .sidebar .brand { padding: .8rem 1.3rem; border-bottom: 1px solid rgba(255,255,255,.12); }
        .sidebar .nav-link { color: rgba(255,255,255,.75); padding: .7rem 1.3rem; display: block;
                             text-decoration: none; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #fff; background: rgba(255,255,255,.12); }
        .main { margin-left: var(--sidebar-width); padding: 1.6rem 2rem; }
        .card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
        .stat { font-size: 1.8rem; font-weight: 700; color: #14532d; }
        .table td, .table th { font-size: .9rem; }
        .badge-estado { font-size: .78rem; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="brand">
        <h6 class="mb-0"><i class="bi bi-laptop"></i> Equipos Rentados</h6>
        <small class="text-white-50"><?= e($config['nombre_institucion'] ?? 'ARCONEL') ?></small>
    </div>
    <nav class="mt-3">
        <?php foreach ($menu as $k => [$href, $icon, $label]): ?>
            <a href="<?= e($href) ?>" class="nav-link <?= $k === $activo ? 'active' : '' ?>">
                <i class="bi <?= e($icon) ?>"></i> <?= e($label) ?>
            </a>
        <?php endforeach; ?>
        <hr class="border-light mx-3">
        <a href="index.php" class="nav-link"><i class="bi bi-arrow-left"></i> Panel principal</a>
        <a href="logout.php" class="nav-link text-warning"><i class="bi bi-box-arrow-left"></i> Cerrar sesión</a>
    </nav>
</div>
<div class="main">
    <h4 class="mb-3"><?= e($titulo) ?></h4>
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
            <?= e($flash['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php
}

function eqrFooter(): void
{
    ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
    <?php
}

/** Etiqueta de estado de equipo. */
function eqrBadgeEstado(string $estado): string
{
    $map = [
        'en_stock' => ['secondary', 'En stock'],
        'asignado' => ['success', 'Asignado'],
        'en_reparacion' => ['warning text-dark', 'En reparación'],
        'retirado' => ['dark', 'Retirado'],
    ];
    [$cls, $txt] = $map[$estado] ?? ['light text-dark', $estado];
    return '<span class="badge badge-estado bg-' . $cls . '">' . e($txt) . '</span>';
}
