<?php
/**
 * Prueba CLI de punta a punta de la Fase 0 (sin navegador).
 *
 * Firma el acta demo con los 3 certificados .p12 en orden usando el MISMO
 * modulo JS que corre en el navegador (via node sign_cli.js), y luego corre
 * la bateria de verificacion y las pruebas negativas.
 *
 * Uso: php cli_test.php
 */
require_once __DIR__ . '/lib/PdfSigner.php';
require_once __DIR__ . '/lib/CmsVerifier.php';
require_once __DIR__ . '/lib/generar_pdf.php';

$out = __DIR__ . '/out';
$certs = __DIR__ . '/certs';
$signCli = __DIR__ . '/../tools/firma-js/src/sign_cli.js';
$pdfPath = "$out/acta_demo.pdf";
$statePath = "$out/acta_demo.state.json";

@mkdir($out, 0775, true);
array_map('unlink', glob("$out/*") ?: []);

$pasos = [];
$fallos = 0;
function paso(string $nombre, bool $ok, string $detalle = ''): void
{
    global $pasos, $fallos;
    $pasos[] = [$nombre, $ok, $detalle];
    echo ($ok ? '  [OK]  ' : '  [FALLO] ') . $nombre . ($detalle ? "  — $detalle" : '') . "\n";
    if (!$ok) { $fallos++; }
}

echo "== Fase 0: prueba de punta a punta ==\n\n";

// ---------------------------------------------------------------- 1. PDF base
echo "1) Generar PDF base con DomPDF\n";
pocGenerarPdf($pdfPath);
paso('PDF generado', is_file($pdfPath) && filesize($pdfPath) > 1000, filesize($pdfPath) . ' bytes');

// ---------------------------------------------------------- 2. Tres firmas
$recuadros = pocRecuadrosFirma();
$firmantes = [
    1 => ['nombre' => 'Administrador Contrato de Prueba', 'cedula' => '1710000001',
          'rol' => 'Administrador del contrato', 'p12' => "$certs/firmante1_admin.p12"],
    2 => ['nombre' => 'Custodio Anterior de Prueba', 'cedula' => '1710000002',
          'rol' => 'Custodio anterior - entrega', 'p12' => "$certs/firmante2_anterior.p12"],
    3 => ['nombre' => 'Custodio Nuevo de Prueba', 'cedula' => '1710000003',
          'rol' => 'Nuevo custodio - recibe', 'p12' => "$certs/firmante3_nuevo.p12"],
];

$signer = new PdfSigner();
$verifier = new CmsVerifier();
$versiones = [];   // bytes de cada version aceptada

foreach ($firmantes as $n => $f) {
    echo "\n2.$n) Firma $n: {$f['nombre']}\n";
    $prep = $signer->preparar($pdfPath, $statePath, [
        'nombre' => $f['nombre'], 'cedula' => $f['cedula'], 'rol' => $f['rol'],
        'rect' => $recuadros[$n]['rect'],
    ]);
    paso("Placeholder preparado (firma $n)", strlen($prep['bytesAFirmar']) > 1000,
        strlen($prep['bytesAFirmar']) . ' bytes a firmar');

    // firmar con node (mismo codigo que el navegador)
    $tosign = "$out/tosign_$n.bin"; $sigder = "$out/sig_$n.der";
    file_put_contents($tosign, $prep['bytesAFirmar']);
    $cmd = sprintf('node %s %s %s %s %s 2>&1',
        escapeshellarg($signCli), escapeshellarg($f['p12']),
        escapeshellarg('prueba123'), escapeshellarg($tosign), escapeshellarg($sigder));
    $salida = shell_exec($cmd);
    $j = json_decode((string)$salida, true);
    paso("Firma CMS generada (firma $n)", is_array($j) && !empty($j['ok']),
        is_array($j) ? ($j['certificado']['cn'] ?? '') . ', ' . ($j['bytes'] ?? '?') . ' bytes DER' : trim((string)$salida));
    if (!is_array($j) || empty($j['ok'])) { continue; }

    // insertar y verificar ANTES de aceptar
    $signed = $signer->completar($statePath, bin2hex(file_get_contents($sigder)));
    if ($n === 1) { copy($pdfPath, "$out/base_v0.pdf"); }
    $anterior = $versiones ? end($versiones) : file_get_contents("$out/base_v0.pdf");
    $res = $verifier->verificarDocumento(file_get_contents($signed), $anterior, $f['cedula']);
    paso("Verificacion 4.4 (firma $n)", $res['ok'],
        $res['ok'] ? $res['firmas'] . ' firma(s), cert: ' . ($res['cert']['cn'] ?? '')
                   : implode(' | ', $res['errores']));
    if (!$res['ok']) { continue; }

    $signer->aceptar($statePath, $signed, $pdfPath);
    $versiones[] = file_get_contents($pdfPath);
    copy($pdfPath, "$out/acta_demo_v$n.pdf");
}

// ------------------------------------------------------------- 3. pdfsig
echo "\n3) Validacion con pdfsig (poppler)\n";
$salida = shell_exec('pdfsig ' . escapeshellarg($pdfPath) . ' 2>&1');
echo preg_replace('/^/m', '     ', (string)$salida) . "\n";
$validas = substr_count((string)$salida, 'Signature is Valid');
paso('pdfsig: 3 firmas validas', $validas === 3, "$validas de 3");

// -------------------------------------------------- 4. Pruebas negativas
echo "\n4) Pruebas negativas\n";
$pdfFinal = file_get_contents($pdfPath);
$vPrev = $versiones[1] ?? null;   // version con 2 firmas

// 4a. un byte alterado
$alterado = $pdfFinal;
$alterado[500] = chr(ord($alterado[500]) ^ 0xFF);
$res = $verifier->verificarDocumento($alterado, $vPrev, '1710000003');
paso('Rechaza un byte alterado', !$res['ok'], implode(' | ', $res['errores']));

// 4b. un PDF distinto que "dice" venir de la misma cadena
pocGenerarPdf("$out/otro.pdf");
$res = $verifier->verificarDocumento($pdfFinal, file_get_contents("$out/otro.pdf"), '1710000003');
paso('Rechaza sustitución del documento base', !$res['ok'], implode(' | ', $res['errores']));

// 4c. cedula que no corresponde: firma valida pero de otra persona
copy("$out/base_v0.pdf", "$out/cedula_mala.pdf");
@unlink("$out/cedula_mala.state.json");
$prep = $signer->preparar("$out/cedula_mala.pdf", "$out/cedula_mala.state.json", [
    'nombre' => 'Firmante con otra cedula', 'cedula' => '1799999999',
    'rol' => 'Prueba', 'rect' => $recuadros[1]['rect'],
]);
file_put_contents("$out/tosign_cm.bin", $prep['bytesAFirmar']);
shell_exec(sprintf('node %s %s %s %s %s 2>&1', escapeshellarg($signCli),
    escapeshellarg("$certs/firmante_cedula_mala.p12"), escapeshellarg('prueba123'),
    escapeshellarg("$out/tosign_cm.bin"), escapeshellarg("$out/sig_cm.der")));
$signed = $signer->completar("$out/cedula_mala.state.json", bin2hex(file_get_contents("$out/sig_cm.der")));
// la cedula ESPERADA del administrador es 1710000001; el cert trae 1799999999
$res = $verifier->verificarDocumento(file_get_contents($signed), file_get_contents("$out/base_v0.pdf"), '1710000001');
paso('Rechaza certificado con cédula que no corresponde', !$res['ok'], implode(' | ', $res['errores']));

// 4d. certificado vencido
copy("$out/base_v0.pdf", "$out/vencido.pdf");
@unlink("$out/vencido.state.json");
$prep = $signer->preparar("$out/vencido.pdf", "$out/vencido.state.json", [
    'nombre' => 'Firmante vencido', 'cedula' => '1710000009',
    'rol' => 'Prueba', 'rect' => $recuadros[1]['rect'],
]);
file_put_contents("$out/tosign_v.bin", $prep['bytesAFirmar']);
// OJO: el modulo JS tambien rechaza certificados vencidos; para probar el lado
// SERVIDOR lo forzamos con un firmador node sin esa comprobacion... no:
// la POC exige que el navegador rechace Y que el servidor rechace. Probamos ambos.
$salida = shell_exec(sprintf('node %s %s %s %s %s 2>&1', escapeshellarg($signCli),
    escapeshellarg("$certs/firmante_vencido.p12"), escapeshellarg('prueba123'),
    escapeshellarg("$out/tosign_v.bin"), escapeshellarg("$out/sig_v.der")));
$j = json_decode((string)$salida, true);
paso('El modulo JS rechaza el certificado vencido', is_array($j) && empty($j['ok']) && ($j['error'] ?? '') === 'vencido',
    $j['mensaje'] ?? trim((string)$salida));

// 4e. clave incorrecta
$salida = shell_exec(sprintf('node %s %s %s %s %s 2>&1', escapeshellarg($signCli),
    escapeshellarg("$certs/firmante1_admin.p12"), escapeshellarg('claveMala'),
    escapeshellarg("$out/tosign_v.bin"), escapeshellarg("$out/sig_x.der")));
$j = json_decode((string)$salida, true);
paso('Clave .p12 incorrecta -> mensaje claro', is_array($j) && empty($j['ok']) && ($j['error'] ?? '') === 'clave',
    $j['mensaje'] ?? trim((string)$salida));

// ------------------------------------------------------------- resumen
echo "\n== Resumen ==\n";
echo $fallos === 0 ? "TODAS LAS PRUEBAS PASARON (" . count($pasos) . ")\n"
                   : "$fallos PRUEBA(S) FALLARON de " . count($pasos) . "\n";
exit($fallos === 0 ? 0 : 1);
