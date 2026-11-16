# Prompt para Claude Code: módulo de firma electrónica en línea de documentos PDF

> **Cómo usar este archivo:** complete la sección 1 con los datos de su sistema y copie
> TODO el documento como primer mensaje a Claude Code, dentro del repositorio del sistema
> donde quiere implementar la firma. Esta arquitectura ya está construida, probada en
> producción y validada con FirmaEC en el sistema `formularios` de ARCONEL
> (repositorio `ncuenca84/formularios`, rama `claude/php-pdf-form-generator-RmXT9`):
> **no reinventes nada, reutiliza esos componentes.**

---

## 0. Cómo quiero que trabajes

- No soy programador. Explícame cada decisión técnica en español claro, sin jerga innecesaria.
- Quiero calidad profesional: código ordenado, validado, seguro y probado.
- Trabaja **por fases**. Al terminar cada fase **detente**, muéstrame qué hiciste, cómo lo
  probaste y qué falta. No sigas sin mi visto bueno.
- **No rompas lo que ya funciona** en este sistema. Antes de tocar un archivo existente,
  dime qué vas a cambiar.
- Primero **analiza este repositorio** (tecnología, estructura, base de datos, cómo genera
  o almacena sus documentos) y proponme el plan de fases adaptado, antes de escribir código.
- Todo el texto visible para el usuario va en español, con tildes correctas.

---

## 1. Datos de MI sistema (completar antes de usar este prompt)

- **Nombre del sistema y qué hace:** ______________________________________
- **Tecnología:** (ej.: PHP 8 + MySQL, Laravel, etc.) ______________________
- **Servidor:** (ej.: CWP/CentOS, cPanel; ¿hay Composer?, ¿exec deshabilitado?) ______
- **URL pública:** ___________________________________________________
- **Qué documentos se firmarán:** (ej.: memorandos, solicitudes, actas) ______
- **Quiénes firman y en qué orden:** (ej.: solicitante → jefe → director) ______
- **¿Cómo se notifica a los firmantes?** (SMTP disponible: servidor, puerto, usuario) ______

---

## 2. Qué hay que construir (resumen)

Firma electrónica **en línea** de documentos PDF con el archivo `.p12` de cada funcionario:

1. El sistema genera el PDF del documento **una sola vez** (con los recuadros de firma
   reservados). Desde ahí **nunca se regenera**: solo crece con firmas incrementales.
2. Cada firmante recibe por correo un **enlace personal** con token; abre la página, ve el
   PDF en pantalla, elige su `.p12`, escribe su clave y firma. También puede **rechazar**
   con motivo.
3. Las firmas van **en orden**: cada firmante recibe su enlace solo cuando firmó el anterior.
4. Al completarse la última firma, el sistema aplica el efecto del documento (lo que
   corresponda en mi sistema) **dentro de una transacción**, y envía el documento final
   firmado a todos.
5. Las firmas deben validar en **FirmaEC de escritorio** (es el único validador que usa la
   institución) y en `pdfsig`.

---

## 3. Arquitectura (YA PROBADA — no cambiarla sin consultarme)

**Opción A: el servidor controla el PDF; el navegador solo firma bytes.**

1. **Servidor (PHP puro, sin exec, sin Node):** toma la versión actual del PDF y le agrega
   por **actualización incremental** el campo de firma: diccionario `/Sig`
   (`adbe.pkcs7.detached`, SHA-256), widget visible con su apariencia en el recuadro
   reservado, `/AcroForm` con `SigFlags 3`, `/ByteRange` de ancho fijo y un `/Contents`
   reservado de 16 KB en hexadecimal. Entrega al navegador los **bytes a firmar** en Base64.
2. **Navegador (JavaScript con node-forge, licencia BSD):** abre el `.p12`, genera una firma
   **CMS/PKCS#7 detached** con atributos firmados (`contentType`, `messageDigest`,
   `signingTime`) incluyendo el certificado y su cadena, y devuelve solo la firma en
   hexadecimal. **El `.p12` y la clave JAMÁS salen del navegador.**
3. **Servidor:** inserta la firma en el hueco, **verifica** (sección 5) y solo entonces la
   acepta como nueva versión oficial del documento.

---

## 4. Componentes LISTOS para reutilizar (del repositorio `ncuenca84/formularios`)

Cópialos y adáptalos; cada uno está probado en producción:

| Componente | Archivo en `formularios` | Qué hace |
|---|---|---|
| Firmador incremental | `includes/eqr/PdfSigner.php` | `preparar()` / `completar()` / `aceptar()` con estado en un JSON "sidecar". Firma en la última página, sello visible estilo FirmaEC con QR incrustado como imagen. |
| Verificador | `includes/eqr/CmsVerifier.php` | Toda la verificación de la sección 5 con `openssl_cms_verify`. |
| Módulo JS de firma | `tools/firma-js/src/firma.js` + bundle compilado `assets/js/firma.bundle.js` (281 KB) | `ArconelFirma.firmar({p12Bytes, clave, datos})`. Se compila con esbuild FUERA del servidor; el servidor no necesita Node. Incluye `sign_cli.js` (pruebas por consola) y `gen_certs.js` (certificados de prueba). |
| Página de firma | `firmar.php` + `firmar_api.php` + `assets/js/firma_ui.js` + `assets/css/firma.css` | Página pública con token, visor del PDF en iframe, firma y rechazo. |
| Circuito de firmas | `includes/eqr/flujo.php` | Tokens, orden de firmantes, completar/rechazar/anular/reenviar, transacción de cierre. Adaptar las reglas de negocio a mi sistema. |
| Correos | `includes/eqr/Notificador.php` + `config/mail.example.php` | Clase única de salida con PHPMailer; registro de cada envío; la clave SMTP fuera del repo. |

Dependencias Composer: `dompdf/dompdf` (^3.1, si el sistema genera los PDF con HTML),
`phpmailer/phpmailer`, `chillerlan/php-qrcode` (QR del sello). Licencias: todas libres
(LGPL/MIT/BSD).

---

## 5. Verificación en el servidor (OBLIGATORIA antes de aceptar cada firma)

Con PHP y la extensión `openssl`, sin exec:

1. **Integridad:** cada firma del documento verifica con `openssl_cms_verify`
   (`OPENSSL_CMS_BINARY | OPENSSL_CMS_NOVERIFY | OPENSSL_CMS_DETACHED`, DER).
2. **Cobertura:** la firma nueva cubre todo el archivo (`c + d == tamaño`).
3. **Continuidad:** el archivo nuevo **empieza byte a byte** con la versión anterior
   aceptada (así nadie sustituye el documento).
4. **Identidad:** la **cédula del certificado** coincide con la del firmante esperado.
   Búscala en el `serialNumber` del sujeto Y en las extensiones (las CA ecuatorianas no la
   guardan en el mismo lugar). Está resuelto en `CmsVerifier::extraerCedula()`.
5. **Vigencia:** el certificado estaba vigente al firmar.
6. La validación de cadena de confianza (CAs acreditadas) **no se implementa**: son
   trámites internos y la validación oficial se hace en FirmaEC. (Decisión ya tomada.)
7. Guardar por firma: CN, serial, emisor, vigencia, hash SHA-256 resultante, IP,
   user-agent y fecha.

---

## 6. Lecciones aprendidas (errores ya cometidos: NO repetirlos)

- **FirmaEC:** no incluyas `/Reason` ni `/Location` en el diccionario `/Sig` (FirmaEC
  muestra "null"); solo `/Name` y `/M`. La fecha `/M` en UTC real (`gmdate`) con
  `+00'00`.
- **`/ByteRange` de ancho fijo** (10 dígitos con ceros a la izquierda) para sustituirlo
  sin mover offsets. Hueco de `/Contents`: 16 KB hex; la firma se rellena con ceros.
- **La apariencia de cada firma crea sus propias fuentes y su QR en cada actualización**
  (objetos pequeños). NUNCA persistas números de objeto de una preparación que puede
  descartarse: hubo un bug real donde un reintento tras una firma rechazada dejaba la
  referencia de fuente rota.
- Los **recuadros de firma** van en una **última página dedicada** con coordenadas
  `position:absolute` conocidas; la conversión CSS→PDF es:
  `x_pdf = margen_izq + left_css`, `y_pdf = alto_pagina − margen_sup − top_css − alto`.
  Verifícalo visualmente (renderiza con `pdftoppm` y mira que el sello caiga en el recuadro).
- **El PDF original debe tener xref clásica** (DomPDF la produce). Si mi sistema genera
  PDF con otra librería (xref streams), avísame antes: el parser de `PdfSigner` habría
  que extenderlo.
- El sello visible es **estilo FirmaEC**: QR a la izquierda (contenido:
  `FIRMADO POR: / RAZON: / LOCALIZACION: / FECHA: / VALIDAR CON: www.firmadigital.gob.ec`)
  y a la derecha "Validar únicamente en FirmaEC." / "Firmado electrónicamente por:" /
  NOMBRE en Courier-Bold grande con salto de línea automático.
- Mensajes claros en el navegador: clave incorrecta ("La clave del archivo de firma no es
  correcta"), certificado vencido, cédula que no corresponde.
- `ini_set('session.cookie_httponly')` SIEMPRE antes de `session_start()`.
- Los correos arman el enlace con una **URL base configurable** (si falta, usar el host
  actual como respaldo); sin ella salen enlaces relativos rotos.

---

## 7. Seguridad (no negociable)

- PDFs firmados **fuera de la carpeta pública** (`storage/...` con `Require all denied`);
  se sirven solo por PHP con sesión válida o token de firma vigente.
- **Tokens de firma:** 32 bytes de `random_bytes`; en BD solo su hash SHA-256; expiran
  (configurable, por defecto 15 días) y se invalidan al firmar, rechazar, anular o reenviar.
- Página de firma con **CSP estricta** y **nada de CDN** (todo servido desde el propio
  servidor): `default-src 'none'; script-src 'self'; style-src 'self'; connect-src 'self';
  frame-src 'self'; img-src 'self'; base-uri 'none'; form-action 'self'` +
  `X-Frame-Options: DENY` + `Referrer-Policy: no-referrer`.
- **CSRF** en todos los formularios; consultas preparadas; escapado de toda salida.
- **Límite de intentos** por IP en la página de firma y por firma (contador de fallos).
- El `.p12` y su clave: solo en el navegador, nunca en `localStorage`, cookies ni logs;
  limpiar las variables al terminar.
- Clave SMTP y credenciales **fuera del repositorio** (archivo en `.gitignore` con un
  `.example` de muestra).
- Bitácora **solo-inserción** de todo el circuito.

---

## 8. Fases

### Fase 0 — Adaptación y prueba de concepto (detente al terminar)
- Analiza mi sistema y dime cómo encaja el módulo (dónde se generan los PDF, dónde van
  los recuadros, qué tabla guarda los documentos).
- Copia los componentes de la sección 4, intégralos y demuestra: un PDF de mi sistema
  firmado por 3 certificados de prueba en orden (usa `gen_certs.js`, clave `prueba123`),
  `pdfsig` con todas las firmas "Valid", y la prueba con MI certificado real para
  validarlo yo en FirmaEC.
- Pruebas negativas: un byte alterado, cédula que no corresponde, certificado vencido y
  clave incorrecta — todas rechazadas con mensaje claro.

### Fase 1 — Circuito completo en mi sistema
- Modelo de datos (documentos, firmas, tokens, bitácora, notificaciones) con migración
  idempotente que no borre nada.
- Circuito de firmas en orden con correos, rechazo con motivo, anulación, reenvío de
  enlace y el efecto final del documento en transacción.
- Prueba de punta a punta automatizada (como `fase2_test.php` del repo original) +
  prueba negativa del firmante con certificado ajeno.

### Fase 2 — Pantallas, reportes y cierre
- Bandeja de documentos por estado, detalle con firmas y correos, descarga del PDF.
- Documentación: cómo funciona, cómo se instala, cómo recompilar el JS, manual corto
  "cómo firmo un documento".

---

## 9. Lo que NO debes hacer

- No usar FirmaEC web ni servicios del MINTEL ni ningún servicio externo de firma.
- No enviar el `.p12` ni la clave al servidor bajo ninguna circunstancia.
- No usar `exec` ni comandos del sistema en el servidor; no requerir Node en el servidor.
- No cargar scripts de CDN en la página de firma.
- No regenerar un PDF que ya tiene firmas.
- No guardar tokens en claro, ni poner credenciales o datos personales en el código o el repositorio.
- No subir certificados reales al repositorio.
