/**
 * Logica compartida de las paginas de firma (admin y publica).
 * Config por atributos data- del <body>:
 *   data-api   : endpoint JSON (ej. "eqr_firma_api.php?id=5" o "firmar_api.php?t=TOKEN")
 *   data-csrf  : token CSRF (solo admin; se agrega al cuerpo JSON)
 *   data-volver: URL opcional del boton "volver" al completar
 * El archivo .p12 y su clave se procesan SOLO en el navegador.
 */
'use strict';

(function () {
    var $ = function (s) { return document.querySelector(s); };
    var cfg = document.body.dataset;

    function api(accion, metodo, cuerpo) {
        var url = cfg.api + (cfg.api.indexOf('?') >= 0 ? '&' : '?') + 'a=' + encodeURIComponent(accion);
        var opts = { method: metodo || 'GET', headers: {} };
        if (metodo === 'POST') {
            cuerpo = cuerpo || {};
            if (cfg.csrf) { cuerpo.csrf = cfg.csrf; }
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(cuerpo);
        }
        return fetch(url, opts).then(function (r) { return r.json(); });
    }

    function pinta(msg, tipo) {
        var el = $('#mensajes');
        el.className = 'alerta ' + (tipo || 'info');
        el.textContent = msg;
        el.style.display = 'block';
    }
    function pintaDetalle(lineas) {
        var el = $('#detalle');
        el.innerHTML = '';
        (lineas || []).forEach(function (l) {
            var d = document.createElement('div');
            d.textContent = l;
            el.appendChild(d);
        });
        el.style.display = (lineas && lineas.length) ? 'block' : 'none';
    }

    function refrescar() {
        return api('estado').then(function (st) {
            if (!st.ok) { pinta(st.mensaje || 'No se pudo cargar el estado.', 'error'); return; }
            var cont = $('#firmantes');
            cont.innerHTML = '';
            st.firmantes.forEach(function (f) {
                var div = document.createElement('div');
                var clase = f.estado === 'firmado' ? 'firmado' : (f.estado === 'pendiente' ? 'turno' : '');
                div.className = 'firmante ' + clase;
                var icono = f.estado === 'firmado' ? '✔' : (f.estado === 'pendiente' ? '✍' : '•');
                var texto = f.estado === 'firmado' ? ('FIRMADO el ' + (f.fecha_firma || ''))
                    : (f.estado === 'pendiente' ? 'ES EL TURNO DE ESTA FIRMA'
                    : (f.estado === 'rechazado' ? 'RECHAZADO' : 'en espera'));
                div.textContent = icono + ' Firma ' + f.orden + ' — ' + f.rol_legible + ': '
                    + f.nombre + ' (C.C. ' + f.cedula + ') — ' + texto;
                cont.appendChild(div);
            });
            var panel = $('#panelFirma');
            if (panel) { panel.style.display = st.puede_firmar ? 'block' : 'none'; }
            var panelR = $('#panelRechazo');
            if (panelR) { panelR.style.display = st.puede_firmar ? 'block' : 'none'; }
            if (st.completo) {
                pinta('El acta está completa, con todas sus firmas electrónicas.', 'exito');
                var volver = $('#btnVolver');
                if (volver) { volver.style.display = 'inline-block'; }
            }
            if (st.aviso) { pinta(st.aviso, 'info'); }
            return st;
        });
    }

    function firmar() {
        var archivo = $('#p12').files[0];
        var clave = $('#clave').value;
        if (!archivo) { pinta('Seleccione su archivo .p12 de firma electrónica.', 'error'); return; }
        if (!clave) { pinta('Escriba la clave de su archivo de firma.', 'error'); return; }
        $('#btnFirmar').disabled = true;
        pintaDetalle([]);
        pinta('Preparando el documento…', 'info');
        api('preparar', 'POST').then(function (prep) {
            if (!prep.ok) { throw { msg: prep.mensaje || 'No se pudo preparar la firma.' }; }
            pinta('Firmando en su navegador (su archivo .p12 y su clave NO se envían a ningún lado)…', 'info');
            return archivo.arrayBuffer().then(function (p12Bytes) {
                var datos = Uint8Array.from(atob(prep.bytesB64), function (c) { return c.charCodeAt(0); }).buffer;
                var res = ArconelFirma.firmar({ p12Bytes: p12Bytes, clave: clave, datos: datos });
                $('#clave').value = '';
                clave = null;
                if (!res.ok) {
                    var det = [];
                    if (res.certificado) {
                        det.push('Certificado: ' + res.certificado.cn);
                        if (res.certificado.hasta) { det.push('Vigente hasta: ' + String(res.certificado.hasta).slice(0, 10)); }
                    }
                    throw { msg: res.mensaje, detalle: det };
                }
                pinta('Enviando la firma para la verificación del servidor…', 'info');
                return api('completar', 'POST', { firmaHex: res.firmaHex });
            });
        }).then(function (fin) {
            if (!fin) { return; }
            if (!fin.ok) {
                pinta(fin.mensaje || 'La firma fue rechazada.', 'error');
                pintaDetalle(fin.errores || []);
            } else {
                pinta(fin.mensaje, 'exito');
                pintaDetalle(fin.advertencias || []);
                var marco = $('#marco');
                if (marco) { marco.src = marco.dataset.src + '&v=' + Date.now(); }
            }
        }).catch(function (e) {
            pinta(e && e.msg ? e.msg : ('Error inesperado: ' + (e && e.message || e)), 'error');
            pintaDetalle(e && e.detalle || []);
        }).then(function () {
            $('#btnFirmar').disabled = false;
            $('#p12').value = '';
            return refrescar();
        });
    }

    function rechazar() {
        var motivo = ($('#motivoRechazo').value || '').trim();
        if (motivo.length < 5) { pinta('Escriba el motivo del rechazo (es obligatorio).', 'error'); return; }
        if (!window.confirm('¿Confirma que RECHAZA firmar esta acta? El trámite se detendrá y se avisará al administrador.')) { return; }
        $('#btnRechazar').disabled = true;
        api('rechazar', 'POST', { motivo: motivo }).then(function (r) {
            pinta(r.mensaje || (r.ok ? 'Trámite rechazado.' : 'No se pudo rechazar.'), r.ok ? 'info' : 'error');
            return refrescar();
        }).then(function () { $('#btnRechazar').disabled = false; });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var b = $('#btnFirmar');
        if (b) { b.addEventListener('click', firmar); }
        var r = $('#btnRechazar');
        if (r) { r.addEventListener('click', rechazar); }
        var marco = $('#marco');
        if (marco) { marco.src = marco.dataset.src + '&v=' + Date.now(); }
        refrescar();
    });
})();
