/**
 * Logica de la pagina de prueba de firma (Fase 0).
 * Sin librerias externas salvo el bundle local de firma (ArconelFirma).
 * El .p12 y la clave JAMAS salen del navegador.
 */
'use strict';

const $ = (sel) => document.querySelector(sel);

let estadoActual = null;

async function api(accion, metodo = 'GET', cuerpo = null) {
    const opts = { method: metodo, headers: {} };
    if (cuerpo) {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(cuerpo);
    }
    const r = await fetch('api.php?a=' + encodeURIComponent(accion), opts);
    return r.json();
}

function pinta(msg, tipo) {
    const el = $('#mensajes');
    el.className = 'alerta ' + (tipo || 'info');
    el.textContent = msg;
    el.style.display = 'block';
}

function pintaDetalle(lineas) {
    const el = $('#detalle');
    el.innerHTML = '';
    for (const l of lineas) {
        const d = document.createElement('div');
        d.textContent = l;
        el.appendChild(d);
    }
    el.style.display = lineas.length ? 'block' : 'none';
}

async function refrescar() {
    estadoActual = await api('estado');
    const cont = $('#firmantes');
    cont.innerHTML = '';
    for (const f of estadoActual.firmantes) {
        const div = document.createElement('div');
        div.className = 'firmante ' + f.estado;
        const icono = f.estado === 'firmado' ? '✔' : (f.estado === 'turno' ? '✍' : '•');
        div.textContent = icono + ' Firma ' + f.n + ' — ' + f.rol + ': ' + f.nombre +
            ' (C.C. ' + f.cedula + ') — ' +
            (f.estado === 'firmado' ? 'FIRMADO' : (f.estado === 'turno' ? 'ES SU TURNO' : 'en espera'));
        if (f.estado === 'turno') {
            const nota = document.createElement('div');
            nota.className = 'nota';
            nota.textContent = 'Para la prueba use el archivo ' + f.p12 + ' (clave: prueba123)';
            div.appendChild(nota);
        }
        cont.appendChild(div);
    }

    $('#panelFirma').style.display = (estadoActual.pdf && !estadoActual.completo) ? 'block' : 'none';
    $('#visor').style.display = estadoActual.pdf ? 'block' : 'none';
    $('#btnVerificar').style.display = estadoActual.pdf ? 'inline-block' : 'none';
    $('#btnDescargar').style.display = estadoActual.pdf ? 'inline-block' : 'none';
    if (estadoActual.pdf) {
        $('#marco').src = 'ver.php?v=' + Date.now();
    }
    if (estadoActual.completo) {
        pinta('El acta tiene las 3 firmas. Descárguela y pruébela en FirmaEC de escritorio y Adobe Reader.', 'exito');
    }
}

async function reiniciar() {
    pinta('Generando acta de prueba…', 'info');
    pintaDetalle([]);
    const r = await api('reiniciar', 'POST');
    pinta(r.mensaje || 'Listo.', r.ok ? 'exito' : 'error');
    await refrescar();
}

async function firmar() {
    const archivo = $('#p12').files[0];
    const clave = $('#clave').value;
    if (!archivo) { pinta('Seleccione su archivo .p12 de firma.', 'error'); return; }
    if (!clave) { pinta('Escriba la clave del archivo de firma.', 'error'); return; }

    $('#btnFirmar').disabled = true;
    try {
        pinta('Preparando el documento…', 'info');
        const prep = await api('preparar', 'POST');
        if (!prep.ok) { pinta(prep.mensaje || 'Error al preparar.', 'error'); return; }

        pinta('Firmando en su navegador (el archivo .p12 no se envía a ningún lado)…', 'info');
        const p12Bytes = await archivo.arrayBuffer();
        const datos = Uint8Array.from(atob(prep.bytesB64), c => c.charCodeAt(0)).buffer;

        const res = ArconelFirma.firmar({ p12Bytes, clave, datos });
        $('#clave').value = '';
        if (!res.ok) {
            pinta(res.mensaje, 'error');
            if (res.certificado) {
                pintaDetalle(['Certificado: ' + res.certificado.cn,
                              'Vigente hasta: ' + res.certificado.hasta.slice(0, 10)]);
            }
            return;
        }

        pinta('Enviando la firma para verificación del servidor…', 'info');
        const fin = await api('completar', 'POST', { firmaHex: res.firmaHex });
        if (!fin.ok) {
            pinta(fin.mensaje || 'La firma fue rechazada.', 'error');
            pintaDetalle(fin.errores || []);
            return;
        }
        pinta(fin.mensaje, 'exito');
        pintaDetalle(fin.advertencias || []);
    } catch (e) {
        pinta('Error inesperado: ' + (e && e.message || e), 'error');
    } finally {
        $('#btnFirmar').disabled = false;
        $('#p12').value = '';
        await refrescar();
    }
}

async function verificar() {
    const r = await api('verificar');
    if (!r.ok) { pinta(r.mensaje || 'No se pudo verificar.', 'error'); return; }
    const lineas = [];
    for (const f of r.firmas) {
        if (f.integra) {
            lineas.push('Firma ' + f.n + ': ÍNTEGRA — ' + f.cert.cn + ' (C.C. ' + (f.cert.cedula || '¿?') +
                '), emitido por ' + f.cert.emisor);
        } else {
            lineas.push('Firma ' + f.n + ': INVÁLIDA — ' + (f.error || ''));
        }
    }
    lineas.push('SHA-256 del acta: ' + r.sha256);
    lineas.push('Tamaño: ' + r.bytes + ' bytes');
    pinta('Resultado de la verificación en el servidor:', 'info');
    pintaDetalle(lineas);
}

document.addEventListener('DOMContentLoaded', () => {
    $('#btnReiniciar').addEventListener('click', reiniciar);
    $('#btnFirmar').addEventListener('click', firmar);
    $('#btnVerificar').addEventListener('click', verificar);
    refrescar();
});
