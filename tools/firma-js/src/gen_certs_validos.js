// Certificados de prueba con cedulas VALIDAS (modulo 10) para la fase de formularios
'use strict';
const fs = require('fs');
const path = require('path');
const forge = require('node-forge');
const outDir = process.argv[2];
const CLAVE = 'prueba123';
const pki = forge.pki;
function atributos(cn, cedula) {
    const a = [{ shortName: 'C', value: 'EC' }, { shortName: 'O', value: 'PRUEBAS POC' }, { shortName: 'CN', value: cn }];
    if (cedula) a.push({ type: '2.5.4.5', value: cedula });
    return a;
}
function crearCert(subjectAttrs, issuerAttrs, issuerKey, publicKey, desde, hasta, esCA) {
    const cert = pki.createCertificate();
    cert.publicKey = publicKey;
    cert.serialNumber = String(Math.floor(Math.random() * 1e15) + 1);
    cert.validity.notBefore = desde; cert.validity.notAfter = hasta;
    cert.setSubject(subjectAttrs); cert.setIssuer(issuerAttrs);
    cert.setExtensions([{ name: 'basicConstraints', cA: !!esCA },
        { name: 'keyUsage', digitalSignature: true, nonRepudiation: true, keyCertSign: !!esCA }]);
    cert.sign(issuerKey, forge.md.sha256.create());
    return cert;
}
const desde = new Date(Date.now() - 86400000);
const hasta = new Date(Date.now() + 3 * 365 * 86400000);
const caKeys = pki.rsa.generateKeyPair(2048);
const caAttrs = atributos('CA RAIZ DE PRUEBA (NO USAR EN PRODUCCION)', null);
const caCert = crearCert(caAttrs, caAttrs, caKeys.privateKey, caKeys.publicKey, desde, hasta, true);
for (const [nombre, cn, ced] of [
    ['valido1', 'FIRMANTE VALIDO UNO', '1710000009'],
    ['valido2', 'FIRMANTE VALIDO DOS', '1710000017'],
    ['valido3', 'FIRMANTE VALIDO TRES', '1710000025'],
]) {
    const keys = pki.rsa.generateKeyPair(2048);
    const cert = crearCert(atributos(cn, ced), caAttrs, caKeys.privateKey, keys.publicKey, desde, hasta, false);
    const p12 = forge.pkcs12.toPkcs12Asn1(keys.privateKey, [cert, caCert], CLAVE, { algorithm: '3des', friendlyName: nombre });
    fs.writeFileSync(path.join(outDir, nombre + '.p12'), Buffer.from(forge.asn1.toDer(p12).getBytes(), 'binary'));
    console.log(nombre, ced);
}

// Certificado como los de las CA que ponen un NUMERO INTERNO en el
// serialNumber del sujeto y guardan la cedula en una extension propia
// (p. ej. estilo Banco Central, OID 1.3.6.1.4.1.37947.3.1) y el RUC en
// otro campo del sujeto. Reproduce el caso real que fallaba.
{
    const keys = pki.rsa.generateKeyPair(2048);
    const attrs = [
        { shortName: 'C', value: 'EC' }, { shortName: 'O', value: 'PRUEBAS POC' },
        { shortName: 'OU', value: 'RUC 1710000041001' },
        { shortName: 'CN', value: 'FIRMANTE CON CEDULA EN EXTENSION' },
        { type: '2.5.4.5', value: '131223165199' },   // numero interno de la CA, NO es cedula
    ];
    const cert = pki.createCertificate();
    cert.publicKey = keys.publicKey;
    cert.serialNumber = String(Math.floor(Math.random() * 1e15) + 1);
    cert.validity.notBefore = desde; cert.validity.notAfter = hasta;
    cert.setSubject(attrs); cert.setIssuer(caAttrs);
    cert.setExtensions([
        { name: 'basicConstraints', cA: false },
        { name: 'keyUsage', digitalSignature: true, nonRepudiation: true },
        { id: '1.3.6.1.4.1.37947.3.1',    // "cedula" en extension propia
          value: forge.asn1.toDer(forge.asn1.create(forge.asn1.Class.UNIVERSAL,
              forge.asn1.Type.PRINTABLESTRING, false, '1710000033')).getBytes() },
    ]);
    cert.sign(caKeys.privateKey, forge.md.sha256.create());
    const p12 = forge.pkcs12.toPkcs12Asn1(keys.privateKey, [cert, caCert], CLAVE,
        { algorithm: '3des', friendlyName: 'valido_ext' });
    fs.writeFileSync(path.join(outDir, 'valido_ext.p12'), Buffer.from(forge.asn1.toDer(p12).getBytes(), 'binary'));
    console.log('valido_ext', 'serialNumber interno 131223165199, cedula 1710000033 en extension, RUC en OU');
}
