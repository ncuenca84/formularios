<?php
/** Sirve el acta de la POC para el visor (el directorio out/ esta bloqueado). */
$pdf = __DIR__ . '/out/acta_demo.pdf';
if (!is_file($pdf)) { http_response_code(404); exit('No hay acta generada.'); }
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($pdf));
header('Content-Disposition: ' . ((isset($_GET['dl'])) ? 'attachment' : 'inline') . '; filename="acta_demo_firmada.pdf"');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
readfile($pdf);
