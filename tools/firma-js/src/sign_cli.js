#!/usr/bin/env node
/**
 * Firma por linea de comandos para PRUEBAS de la Fase 0.
 * Usa exactamente el mismo modulo que el navegador (src/firma.js),
 * de modo que probar esto prueba la logica real del bundle.
 *
 * Uso: node sign_cli.js <archivo.p12> <clave> <datos.bin> <salida_firma.der>
 * Codigo de salida 0 = ok. Imprime JSON con el resultado en stdout.
 */
'use strict';
const fs = require('fs');
const { firmar } = require('./firma.js');

const [, , p12Path, clave, datosPath, salidaPath] = process.argv;
if (!p12Path || clave === undefined || !datosPath || !salidaPath) {
    console.error('Uso: node sign_cli.js <archivo.p12> <clave> <datos.bin> <salida_firma.der>');
    process.exit(2);
}

const p12 = fs.readFileSync(p12Path);
const datos = fs.readFileSync(datosPath);

const res = firmar({
    p12Bytes: p12.buffer.slice(p12.byteOffset, p12.byteOffset + p12.byteLength),
    clave,
    datos: datos.buffer.slice(datos.byteOffset, datos.byteOffset + datos.byteLength)
});

if (!res.ok) {
    console.log(JSON.stringify(res));
    process.exit(1);
}
fs.writeFileSync(salidaPath, Buffer.from(res.firmaHex, 'hex'));
console.log(JSON.stringify({ ok: true, certificado: res.certificado, bytes: res.firmaHex.length / 2 }));
