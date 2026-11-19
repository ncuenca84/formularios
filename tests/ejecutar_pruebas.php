<?php
/**
 * Ejecuta TODAS las pruebas automatizadas del sistema, cada suite en su
 * propio proceso. Uso (solo en desarrollo, nunca en produccion):
 *
 *   PRUEBAS_OK=1 php tests/ejecutar_pruebas.php
 */
declare(strict_types=1);
require __DIR__ . '/guard.php';

$suites = [
    'Equipos: tramites y firmas (fase 2)' => 'fase2_equipos.php',
    'Equipos: salidas y retorno (fase 3)' => 'fase3_salidas.php',
    'Formularios: firma en linea (fase 4)' => 'fase4_formularios.php',
];

$fallos = 0;
foreach ($suites as $nombre => $archivo) {
    echo "\n========== $nombre ==========\n";
    passthru('PRUEBAS_OK=1 ' . escapeshellarg(PHP_BINARY) . ' -d error_reporting=E_ALL^E_DEPRECATED '
        . escapeshellarg(__DIR__ . '/' . $archivo), $rc);
    if ($rc !== 0) { $fallos++; echo ">>> SUITE CON FALLOS: $archivo (código $rc)\n"; }
}

echo "\n==============================\n";
echo $fallos === 0 ? "TODAS LAS SUITES PASARON.\n" : "$fallos suite(s) con fallos.\n";
exit($fallos === 0 ? 0 : 1);
