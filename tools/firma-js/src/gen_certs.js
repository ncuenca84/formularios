#!/usr/bin/env node
/**
 * Genera los certificados .p12 de PRUEBA de la Fase 0 con node-forge.
 * SON CERTIFICADOS DE JUGUETE: CA autofirmada local y cedulas ficticias.
 * Clave de todos los .p12: prueba123
 *
 * Uso: node gen_certs.js <directorio_salida>
 */
'use strict';
const fs = require('fs');
const path = require('path');
const forge = require('node-forge');

const outDir = process.argv[2] || '.';
const CLAVE = 'prueba123';
const pki = forge.pki;

function atributos(cn, cedula) {
    const a = [
        { shortName: 'C', value: 'EC' },
        { shortName: 'O', value: 'PRUEBAS POC' },
        { shortName: 'CN', value: cn }
    ];
    if (cedula) a.push({ type: '2.5.4.5', value: cedula }); // serialNumber = cedula
    return a;
}

function crearCert(subjectAttrs, issuerAttrs, issuerKey, publicKey, desde, hasta, esCA) {
    const cert = pki.createCertificate();
    cert.publicKey = publicKey;
    cert.serialNumber = String(Math.floor(Math.random() * 1e15) + 1);
    cert.validity.notBefore = desde;
    cert.validity.notAfter = hasta;
    cert.setSubject(subjectAttrs);
    cert.setIssuer(issuerAttrs);
    cert.setExtensions([
        { name: 'basicConstraints', cA: !!esCA },
        { name: 'keyUsage', digitalSignature: true, nonRepudiation: true, keyCertSign: !!esCA }
    ]);
    cert.sign(issuerKey, forge.md.sha256.create());
    return cert;
}

function guardarP12(nombre, key, cert, caCert) {
    const p12Asn1 = forge.pkcs12.toPkcs12Asn1(key, [cert, caCert], CLAVE,
        { algorithm: '3des', friendlyName: nombre });
    const der = forge.asn1.toDer(p12Asn1).getBytes();
    fs.writeFileSync(path.join(outDir, nombre + '.p12'), Buffer.from(der, 'binary'));
}

const hoy = new Date();
const en2anios = new Date(hoy.getTime() + 730 * 86400000);
const hace2anios = new Date(hoy.getTime() - 730 * 86400000);
const hace1anio = new Date(hoy.getTime() - 365 * 86400000);

console.log('Generando claves (RSA 2048)...');

// CA raiz de prueba
const caKeys = pki.rsa.generateKeyPair(2048);
const caAttrs = [
    { shortName: 'C', value: 'EC' },
    { shortName: 'O', value: 'CA DE PRUEBA ARCONEL POC' },
    { shortName: 'CN', value: 'CA RAIZ DE PRUEBA (NO USAR EN PRODUCCION)' }
];
const caCert = crearCert(caAttrs, caAttrs, caKeys.privateKey, caKeys.publicKey, hace2anios, en2anios, true);
fs.writeFileSync(path.join(outDir, 'ca.crt'), pki.certificateToPem(caCert));

const firmantes = [
    ['firmante1_admin', 'ADMINISTRADOR CONTRATO DE PRUEBA', '1710000001', hace1anio, en2anios],
    ['firmante2_anterior', 'CUSTODIO ANTERIOR DE PRUEBA', '1710000002', hace1anio, en2anios],
    ['firmante3_nuevo', 'CUSTODIO NUEVO DE PRUEBA', '1710000003', hace1anio, en2anios],
    ['firmante_vencido', 'FIRMANTE VENCIDO DE PRUEBA', '1710000009', hace2anios, hace1anio],
    ['firmante_cedula_mala', 'FIRMANTE CON OTRA CEDULA DE PRUEBA', '1799999999', hace1anio, en2anios],
];

for (const [archivo, cn, cedula, desde, hasta] of firmantes) {
    const keys = pki.rsa.generateKeyPair(2048);
    const cert = crearCert(atributos(cn, cedula), caAttrs, caKeys.privateKey, keys.publicKey, desde, hasta, false);
    guardarP12(archivo, keys.privateKey, cert, caCert);
    console.log('  ' + archivo + '.p12  (CN=' + cn + ', cedula=' + cedula + ')');
}
console.log('Listo. Clave de todos los .p12: ' + CLAVE);
