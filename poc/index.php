<?php
/**
 * Pagina de prueba de la firma en el navegador — Fase 0.
 * CSP estricta: nada de CDN, nada inline. El .p12 nunca sale del navegador.
 */
header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self'; " .
       "connect-src 'self'; frame-src 'self'; img-src 'self'; base-uri 'none'; form-action 'self'");
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Prueba de firma electrónica — Fase 0</title>
<link rel="stylesheet" href="assets/poc.css">
</head>
<body>
<div class="caja">
    <h1>Prototipo de firma electrónica en línea — Fase 0</h1>
    <p class="sub">Prueba técnica aislada. El acta se firma 3 veces en orden (administrador,
    custodio anterior, nuevo custodio) por <strong>actualización incremental</strong>.
    El archivo .p12 y su clave se procesan <strong>únicamente en su navegador</strong>.</p>

    <div class="acciones">
        <button id="btnReiniciar" type="button">1. Generar acta de prueba (reinicia todo)</button>
        <button id="btnVerificar" type="button" style="display:none">Verificar firmas en el servidor</button>
        <a id="btnDescargar" href="ver.php?dl=1" style="display:none" class="boton">Descargar el acta</a>
    </div>

    <div id="mensajes" class="alerta info" style="display:none"></div>
    <div id="detalle" class="detalle" style="display:none"></div>

    <h2>Circuito de firmas</h2>
    <div id="firmantes"></div>
    <p class="nota">Los certificados de prueba (.p12, clave <code>prueba123</code>) se descargan aquí:
        <a href="certs/firmante1_admin.p12" download>firmante 1</a> ·
        <a href="certs/firmante2_anterior.p12" download>firmante 2</a> ·
        <a href="certs/firmante3_nuevo.p12" download>firmante 3</a> ·
        <a href="certs/firmante_vencido.p12" download>vencido</a> ·
        <a href="certs/firmante_cedula_mala.p12" download>cédula que no corresponde</a>
    </p>

    <div id="panelFirma" style="display:none">
        <h2>Firmar como el firmante de turno</h2>
        <div class="campo">
            <label for="p12">Archivo de firma electrónica (.p12 / .pfx)</label>
            <input type="file" id="p12" accept=".p12,.pfx">
        </div>
        <div class="campo">
            <label for="clave">Clave del archivo de firma</label>
            <input type="password" id="clave" autocomplete="off">
        </div>
        <button id="btnFirmar" type="button" class="primario">Firmar</button>
        <p class="nota">Pruebe también lo que NO debe pasar: la clave equivocada, el certificado
        vencido o el de cédula que no corresponde. El sistema debe rechazarlos con un mensaje claro.</p>
    </div>

    <div id="visor" style="display:none">
        <h2>Acta actual</h2>
        <iframe id="marco" title="Acta en PDF"></iframe>
    </div>
</div>
<script src="assets/firma.bundle.js"></script>
<script src="assets/poc.js"></script>
</body>
</html>
