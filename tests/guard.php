<?php
/**
 * Base comun de las pruebas automatizadas del sistema de firma en linea.
 *
 * ADVERTENCIA: las pruebas usan la base de datos configurada en
 * config/database.php y borran los datos de prueba (codigos TEST-%,
 * contratos CONTRATO-PRUEBA%). NUNCA las ejecute contra produccion.
 * Por eso exigen la variable de entorno PRUEBAS_OK=1:
 *
 *   PRUEBAS_OK=1 php tests/ejecutar_pruebas.php
 *
 * Requisitos: MariaDB/MySQL local con el esquema instalado, PHP 8 con
 * openssl, y Node.js (para firmar con los certificados de prueba con el
 * MISMO modulo JS que usa el navegador). El servidor de produccion no
 * necesita nada de esto.
 */
declare(strict_types=1);

if (getenv('PRUEBAS_OK') !== '1') {
    fwrite(STDERR, "Estas pruebas borran datos de prueba de la base configurada.\n" .
        "Ejecute SOLO en un entorno de desarrollo:  PRUEBAS_OK=1 php " . ($argv[0] ?? 'tests/...') . "\n");
    exit(2);
}

chdir(dirname(__DIR__));
define('PRUEBAS_TMP', __DIR__ . '/tmp');
define('PRUEBAS_CERTS', __DIR__ . '/certs');
define('PRUEBAS_CERTS_VALIDOS', PRUEBAS_TMP . '/certs_validos');
@mkdir(PRUEBAS_TMP, 0775, true);

/** Certificados de prueba con cedulas VALIDAS (modulo 10): se generan una vez. */
function pruebasGenerarCertsValidos(): void
{
    if (is_file(PRUEBAS_CERTS_VALIDOS . '/valido1.p12')) { return; }
    @mkdir(PRUEBAS_CERTS_VALIDOS, 0775, true);
    exec('cd tools/firma-js && node src/gen_certs_validos.js ' . escapeshellarg(PRUEBAS_CERTS_VALIDOS) . ' 2>&1', $o, $rc);
    if ($rc !== 0) {
        fwrite(STDERR, "No se pudieron generar los certificados de prueba (se requiere Node):\n" . implode("\n", $o) . "\n");
        exit(2);
    }
}

/** Ruta de un .p12 de prueba (primero los de cedula valida, luego los clasicos). */
function pruebasCert(string $p12): string
{
    foreach ([PRUEBAS_CERTS_VALIDOS, PRUEBAS_CERTS] as $dir) {
        if (is_file("$dir/$p12")) { return "$dir/$p12"; }
    }
    throw new RuntimeException("Certificado de prueba no encontrado: $p12");
}

/** Firma bytes con un .p12 de prueba usando el MISMO modulo JS del navegador. */
function firmaCli(string $p12, string $bytesB64, string $salida): string
{
    $bin = PRUEBAS_TMP . '/tosign.bin';
    $der = PRUEBAS_TMP . '/' . $salida;
    file_put_contents($bin, base64_decode($bytesB64));
    $cmd = 'cd tools/firma-js && node src/sign_cli.js ' . escapeshellarg(pruebasCert($p12))
         . ' prueba123 ' . escapeshellarg($bin) . ' ' . escapeshellarg($der) . ' 2>&1';
    exec($cmd, $o, $rc);
    if ($rc !== 0) { throw new RuntimeException('sign_cli falló: ' . implode('|', $o)); }
    return bin2hex((string)file_get_contents($der));
}

function ok(bool $cond, string $msg): void
{
    echo ($cond ? '  [OK] ' : '  [FALLO] ') . $msg . "\n";
    if (!$cond) { exit(1); }
}

pruebasGenerarCertsValidos();
