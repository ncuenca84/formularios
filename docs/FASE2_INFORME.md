# Informe de la Fase 2 — Trámites y firma electrónica en línea

**Fecha:** 2026-11-16 · **Estado: COMPLETADA (pendiente del visto bueno del usuario)**

## 1. Qué se construyó

| Pieza | Archivo | Qué hace |
|---|---|---|
| Firmador de PDF | `includes/eqr/PdfSigner.php` | El firmador probado en la Fase 0, promovido a producción. Firma por **actualización incremental** en la **última página** del acta (recuadros reservados). Corregido un defecto detectado en pruebas: tras un intento de firma rechazado, el reintento generaba una referencia de fuente inválida. |
| Verificador | `includes/eqr/CmsVerifier.php` | La verificación de la sección 4.4: integridad, cobertura, continuidad incremental, cédula del certificado, vigencia y cadena opcional. **Ninguna firma se acepta sin pasar por aquí.** |
| Generador de actas | `includes/eqr/acta_pdf.php` | Acta según `ACTA01.DOC` (6 secciones + firmas), cláusulas tomadas del contrato, **QR de verificación** y código de trámite. Se genera **una sola vez**: nunca se regenera. Guardada en `storage/actas/` (no pública). |
| Flujo del circuito | `includes/eqr/flujo.php` | Crear trámite (borrador), firmas en orden, tokens de 32 bytes (solo hash en BD, vencen a los N días), cierre **en transacción** (el custodio cambia solo con la última firma), rechazo, anulación, reenvío, descarte de borrador, bitácora. |
| Correos | `includes/eqr/Notificador.php` + `config/mail.example.php` | Clase **única** de salida con PHPMailer (SMTP STARTTLS 587). La clave va en `config/mail.php`, **ignorado por Git**. Cada envío queda en `eqr_notificaciones`; si falla, el trámite sigue y se puede reenviar. |
| Asistente nuevo trámite | `admin/eqr_tramite_nuevo.php` | Asignación (equipos en stock), traspaso y devolución (asignados sin trámite abierto). Autocompletado de personas por cédula. Valida cédulas (módulo 10) y correos institucionales. |
| Firma del administrador | `admin/eqr_tramite_firmar.php` + `eqr_firma_api.php` | El administrador firma al crear, con su `.p12`, en una página con **CSP estricta y sin CDN**. La cédula del certificado **debe** ser la del administrador del contrato. |
| Bandeja de trámites | `admin/eqr_tramites.php` + `eqr_tramite_ver.php` | Pestañas Borradores / En firma / Completados / Rechazados / Anulados. Detalle con firmas, correos, bitácora y acciones: retomar, reenviar enlace, anular, descartar. |
| Página pública de firma | `firmar.php` + `firmar_api.php` + `firmar_acta.php` | `firmar.php?t=TOKEN`: visor del acta, firma con `.p12` o rechazo con motivo. CSP estricta, sin CDN, límite de intentos por IP. El `.p12` y la clave **jamás salen del navegador**. |
| Configuración del módulo | `admin/eqr_config.php` | Dominio de correo, días del enlace, **URL base** (necesaria para los enlaces y el QR) y botón **"Enviar correo de prueba"**. |

También se actualizó DomPDF de 2.0.8 a **3.1** (la 2.x tenía 6 avisos de seguridad publicados). Se verificó por regresión que las plantillas existentes rinden idéntico.

## 2. Cómo queda el flujo (ejemplo: traspaso)

1. Admin → **Trámites → Nuevo trámite** → Traspaso → elige el equipo → datos del nuevo custodio (el anterior se toma del equipo) → *Generar el acta y pasar a mi firma*.
2. El acta aparece en pantalla; el admin firma con su `.p12`. Hasta aquí, **borrador**: no se envía nada.
3. Al firmar, el sistema manda el enlace al **custodio anterior**; cuando él firma, al **nuevo custodio**.
4. Con la última firma, **en una sola transacción**: trámite completado + el equipo cambia de custodio + bitácora. Se envía la alerta de cierre al administrador y copia del acta firmada a todos.
5. Si alguien rechaza (con motivo), el trámite queda **Rechazado**, el custodio no cambia y se avisa al administrador.

## 3. Pruebas realizadas (entorno local, certificados de prueba de la Fase 0)

Prueba de punta a punta automatizada (25 comprobaciones, 2 corridas limpias):

- Traspaso completo con 3 firmas en orden (administrador → custodio anterior → nuevo custodio). ✅
- **Prueba negativa obligatoria:** un admin que firma con un certificado de cédula ajena es **rechazado** por la verificación del servidor, con el intento fallido contabilizado. ✅
- El custodio **no cambia** tras las firmas 1 y 2; cambia **solo** al completarse la 3. ✅
- Tokens: válidos solo en su turno, **invalidados al usarse**; vencimiento y reenvío probados. ✅
- Acta: generada una sola vez (regenerarla lanza error), crece solo con firmas incrementales. ✅
- `pdfsig`: las 3 firmas "Signature is Valid", la última cubre todo el documento. ✅
- Devolución rechazada por el custodio: trámite Rechazado, equipo intacto, aviso al administrador registrado. ✅
- Sin SMTP configurado: los correos quedan **fallidos registrados** y el trámite sigue vivo (se puede reenviar). ✅
- Regresión DomPDF 3: plantillas existentes idénticas; formularios públicos 1–6 **sin tocar**. ✅

## 4. Textos NUEVOS que debe revisar el área legal

Las actas de **traspaso** y **devolución** derivan del acta de asignación aprobada. Los textos nuevos
están en la "Constancia" (sección 3 del acta), en `includes/eqr/acta_pdf.php`:

- **Traspaso:** "…se realiza el traspaso del equipo… lo entrega [custodio saliente]… y lo recibe
  [nuevo custodio], quien asume desde la suscripción de esta acta la calidad de usuario final y custodio…"
- **Devolución:** "…[custodio] devuelve a la DTIC el equipo… Lo recibe [administrador]… Con la
  suscripción de esta acta cesa la custodia del usuario sobre el equipo, el cual queda a disposición de la DTIC."

Las secciones 4, 5 y 6 (compromisos, consideraciones, aceptación) salen tal cual del contrato registrado.

## 5. Para poner en producción

```bash
cd /home/arconel/formularios.arconel.gob.ec
git pull
composer install          # IMPRESCINDIBLE: dompdf 3, phpmailer y el generador de QR
chown -R arconel:arconel .
chmod -R 775 storage
```

Después, en el panel admin:

1. **Equipos Rentados → Configuración** → poner la **URL base** `https://formularios.arconel.gob.ec` y guardar (sin esto los enlaces de los correos no se arman).
2. En el servidor: `cp config/mail.example.php config/mail.php`, editar la clave del buzón
   `notificaciones@app.arconel.gob.ec` y `chmod 640 config/mail.php`. Probar con el botón
   **"Enviar correo de prueba"**.
3. Hacer un trámite real de prueba y validar el acta final en **FirmaEC de escritorio**.

## 6. Qué sigue

- **Fase 3:** formulario 7 (salida del edificio) conectado al inventario, con firmas del custodio y su jefe.
- **Fase 4:** `verificar.php` (el QR ya lo imprime cada acta), reportes CSV, tablero y endurecimiento.
- **Fase 5:** pruebas automatizadas y manual de uso.
- Recordatorio pendiente: revisar que los correos de `app.arconel.gob.ec` lleguen a la bandeja de entrada (SPF/DKIM) cuando el SMTP quede configurado.
