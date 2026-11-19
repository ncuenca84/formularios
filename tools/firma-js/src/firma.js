/**
 * Firma CMS/PKCS#7 detached en el navegador con node-forge.
 *
 * El archivo .p12 y su clave se procesan UNICAMENTE en memoria del navegador.
 * Nunca se envian al servidor, no se guardan en localStorage ni cookies.
 *
 * API publica (global ArconelFirma):
 *   firmar({ p12Bytes: ArrayBuffer, clave: string, datos: ArrayBuffer })
 *     -> { ok: true, firmaHex, certificado: {cn, cedula, emisor, desde, hasta} }
 *     -> { ok: false, error: 'clave' | 'vencido' | 'sin_clave_privada' | 'formato' | 'interno',
 *          mensaje: string }
 *   inspeccionar({ p12Bytes, clave })
 *     -> igual pero sin firmar: solo devuelve los datos del certificado.
 */
'use strict';

const forge = require('node-forge');

/* Extrae la cedula del titular desde el certificado.
 * Las CA ecuatorianas no la guardan todas en el mismo lugar:
 * 1) subject serialNumber (OID 2.5.4.5)  2) texto de las extensiones. */
function extraerCedula(cert) {
    try {
        const attr = cert.subject.getField({ type: '2.5.4.5' });
        if (attr && attr.value) {
            const m = String(attr.value).match(/\d{10,13}/);
            if (m) return m[0];
        }
    } catch (e) { /* sigue */ }
    try {
        for (const ext of cert.extensions || []) {
            const raw = String(ext.value || '');
            const m = raw.match(/\d{10}/);
            if (m) return m[0];
        }
    } catch (e) { /* sigue */ }
    return null;
}

function abrirP12(p12Bytes, clave) {
    let p12;
    try {
        const der = forge.util.createBuffer(new Uint8Array(p12Bytes));
        const asn1 = forge.asn1.fromDer(der);
        p12 = forge.pkcs12.pkcs12FromAsn1(asn1, false, clave);
    } catch (e) {
        const msg = String(e && e.message || e);
        if (/invalid password|mac verify|mac could not be verified|pkcs#12 mac/i.test(msg)) {
            return { error: 'clave', mensaje: 'La clave del archivo de firma no es correcta.' };
        }
        return { error: 'formato', mensaje: 'El archivo no es un contenedor .p12/.pfx valido.' };
    }

    let key = null;
    const bagsK = p12.getBags({ bagType: forge.pki.oids.pkcs8ShroudedKeyBag });
    const bagsK2 = p12.getBags({ bagType: forge.pki.oids.keyBag });
    const kb = (bagsK[forge.pki.oids.pkcs8ShroudedKeyBag] || []).concat(bagsK2[forge.pki.oids.keyBag] || []);
    if (kb.length) key = kb[0].key;

    const bagsC = p12.getBags({ bagType: forge.pki.oids.certBag });
    const certs = (bagsC[forge.pki.oids.certBag] || []).map(b => b.cert).filter(Boolean);
    if (!key) {
        return { error: 'sin_clave_privada', mensaje: 'El archivo .p12 no contiene una clave privada.' };
    }
    if (!certs.length) {
        return { error: 'formato', mensaje: 'El archivo .p12 no contiene certificados.' };
    }

    /* El certificado del firmante es el que corresponde a la clave privada. */
    let firmante = null;
    for (const c of certs) {
        try {
            if (c.publicKey && key.n && c.publicKey.n && c.publicKey.n.compareTo(key.n) === 0) {
                firmante = c; break;
            }
        } catch (e) { /* sigue */ }
    }
    if (!firmante) firmante = certs[0];
    const cadena = certs.filter(c => c !== firmante);

    return { key, firmante, cadena };
}

function datosCert(cert) {
    const cn = (cert.subject.getField('CN') || {}).value || '';
    const emisor = (cert.issuer.getField('CN') || {}).value || '';
    return {
        cn,
        cedula: extraerCedula(cert),
        emisor,
        desde: cert.validity.notBefore.toISOString(),
        hasta: cert.validity.notAfter.toISOString()
    };
}

function inspeccionar(opts) {
    const r = abrirP12(opts.p12Bytes, opts.clave);
    if (r.error) return { ok: false, error: r.error, mensaje: r.mensaje };
    return { ok: true, certificado: datosCert(r.firmante) };
}

function firmar(opts) {
    const r = abrirP12(opts.p12Bytes, opts.clave);
    if (r.error) return { ok: false, error: r.error, mensaje: r.mensaje };

    const ahora = new Date();
    if (ahora < r.firmante.validity.notBefore || ahora > r.firmante.validity.notAfter) {
        return {
            ok: false, error: 'vencido',
            mensaje: 'El certificado de firma no esta vigente (vencio el ' +
                r.firmante.validity.notAfter.toISOString().slice(0, 10) + ').',
            certificado: datosCert(r.firmante)
        };
    }

    try {
        const p7 = forge.pkcs7.createSignedData();
        /* contenido = bytes del PDF a firmar (ByteRange), como buffer binario */
        const bin = new Uint8Array(opts.datos);
        let s = '';
        const CHUNK = 0x8000;
        for (let i = 0; i < bin.length; i += CHUNK) {
            s += String.fromCharCode.apply(null, bin.subarray(i, i + CHUNK));
        }
        p7.content = forge.util.createBuffer(s, 'binary');

        p7.addCertificate(r.firmante);
        for (const c of r.cadena) { try { p7.addCertificate(c); } catch (e) { /* opcional */ } }

        p7.addSigner({
            key: r.key,
            certificate: r.firmante,
            digestAlgorithm: forge.pki.oids.sha256,
            authenticatedAttributes: [
                { type: forge.pki.oids.contentType, value: forge.pki.oids.data },
                { type: forge.pki.oids.messageDigest },
                { type: forge.pki.oids.signingTime, value: ahora }
            ]
        });
        p7.sign({ detached: true });

        const der = forge.asn1.toDer(p7.toAsn1()).getBytes();
        let hex = '';
        for (let i = 0; i < der.length; i++) {
            hex += ('0' + der.charCodeAt(i).toString(16)).slice(-2);
        }
        return { ok: true, firmaHex: hex, certificado: datosCert(r.firmante) };
    } catch (e) {
        return { ok: false, error: 'interno', mensaje: 'No se pudo generar la firma: ' + String(e && e.message || e) };
    } finally {
        /* Limpieza: soltar referencias a material sensible */
        try { r.key = null; } catch (e) { }
        opts.clave = null;
        opts.p12Bytes = null;
    }
}

module.exports = { firmar, inspeccionar };
