# Prompt para Claude Code: incorporar la firma electrónica en línea a este sistema

> **Cómo usarlo:** pega todo este documento como mensaje a Claude Code dentro del
> repositorio del sistema donde quieres la firma. Claude ya conoce ese sistema; este
> documento le da la arquitectura de firma, ya probada en producción y validada con
> FirmaEC en el sistema `formularios` de ARCONEL (repositorio `ncuenca84/formularios`).

---

## 0. Qué te estoy pidiendo

Tú ya conoces este sistema por completo (su documentación, su código, su base de datos y
sus procesos). Lo que quiero es **incorporar a sus procesos la firma electrónica en línea
de documentos PDF** que ya funciona en mi sistema `formularios` (módulo de equipos
rentados), adaptándola a lo que este sistema ya tiene.

**Tu primera tarea NO es programar:** analiza este sistema, decide dónde encaja la firma
(qué documentos, quiénes firman, en qué orden, qué efecto tiene el documento completado)
y **preséntame un plan de implementación por fases**. Detente ahí y espera mi visto bueno.
Después, trabaja fase por fase, deteniéndote al final de cada una con un informe de qué
hiciste y cómo lo probaste. No rompas nada de lo que ya funciona.

Habla conmigo en español claro, sin jerga: no soy programador.

---

## 1. Cómo funciona la firma (arquitectura probada — no cambiarla sin consultarme)

**El servidor controla el PDF; el navegador solo firma bytes. El `.p12` y su clave JAMÁS
salen del navegador.**

1. El sistema genera el PDF del documento **una sola vez**, con recuadros de firma
   reservados en una última página con coordenadas fijas. Desde ahí **nunca se regenera**:
   solo crece con firmas incrementales.
2. **Servidor (PHP puro, sin exec, sin Node):** agrega por actualización incremental el
   campo de firma (`/Sig` `adbe.pkcs7.detached` SHA-256, widget visible, `/AcroForm`
   `SigFlags 3`, `/ByteRange` de ancho fijo, `/Contents` reservado de 16 KB) y entrega al
   navegador los bytes a firmar.
3. **Navegador (node-forge, compilado con esbuild fuera del servidor):** abre el `.p12`,
   genera la firma CMS/PKCS#7 detached con atributos firmados (contentType,
   messageDigest, signingTime) y la cadena del certificado, y devuelve solo la firma en
   hexadecimal.
4. **Servidor:** inserta la firma, la **verifica** (sección 3) y solo entonces la acepta.
5. Cada firmante recibe por correo un **enlace personal con token**; abre la página, ve el
   PDF, firma o **rechaza con motivo**. Las firmas van **en orden**: cada quien recibe su
   enlace cuando firmó el anterior. Al completarse la última, el sistema aplica el efecto
   del documento **en una transacción** y envía el PDF final firmado a todos.
6. El resultado valida en **FirmaEC de escritorio** (el único validador que usa la
   institución) y en `pdfsig`.

---

## 2. Componentes LISTOS para reutilizar (no reinventes nada)

Están en el repositorio **`ncuenca84/formularios`**, rama
`claude/php-pdf-form-generator-RmXT9`. Si no puedes acceder a ese repositorio desde aquí,
**pídemelos y yo copio los archivos**:

| Componente | Archivo | Qué hace |
|---|---|---|
| Firmador incremental | `includes/eqr/PdfSigner.php` | preparar / completar / aceptar, estado en JSON sidecar, firma en la última página, sello visible estilo FirmaEC con QR. |
| Verificador | `includes/eqr/CmsVerifier.php` | Toda la verificación de la sección 3 con `openssl_cms_verify`, incluida la extracción de la cédula del certificado (resuelta para las CA ecuatorianas). |
| Módulo JS de firma | `tools/firma-js/` (fuente) y `assets/js/firma.bundle.js` (compilado, 281 KB) | `ArconelFirma.firmar({p12Bytes, clave, datos})`. Incluye `sign_cli.js` (pruebas por consola) y `gen_certs.js` (certificados de prueba, clave `prueba123`). El servidor no necesita Node. |
| Página de firma | `firmar.php`, `firmar_api.php`, `assets/js/firma_ui.js`, `assets/css/firma.css` | Página pública con token, visor PDF, firmar y rechazar. |
| Circuito | `includes/eqr/flujo.php` | Tokens, orden, completar/rechazar/anular/reenviar, transacción de cierre. Adapta las reglas al proceso de este sistema. |
| Correos | `includes/eqr/Notificador.php`, `config/mail.example.php` | Clase única con PHPMailer, registro de envíos, clave fuera del repo. |

Dependencias Composer: `phpmailer/phpmailer`, `chillerlan/php-qrcode` y, si los PDF se
generan desde HTML, `dompdf/dompdf` ^3.1. Si este sistema genera sus PDF con otra
librería (xref streams en lugar de xref clásica), **avísame antes**: el parser del
firmador habría que extenderlo.

---

## 3. Verificación en el servidor (OBLIGATORIA antes de aceptar cada firma)

1. **Integridad:** cada firma verifica con `openssl_cms_verify`
   (`OPENSSL_CMS_BINARY | OPENSSL_CMS_NOVERIFY | OPENSSL_CMS_DETACHED`, DER).
2. **Cobertura:** la firma nueva cubre todo el archivo.
3. **Continuidad:** el archivo nuevo empieza byte a byte con la versión anterior aceptada.
4. **Identidad:** la cédula del certificado coincide con la del firmante esperado
   (en el `serialNumber` del sujeto o en las extensiones).
5. **Vigencia:** el certificado estaba vigente al firmar.
6. La validación de cadena de confianza (CAs acreditadas) **no se implementa**:
   decisión institucional, la validación oficial se hace en FirmaEC.
7. Registrar por firma: CN, serial, emisor, vigencia, hash SHA-256 resultante, IP,
   user-agent y fecha. Bitácora solo-inserción de todo el circuito.

---

## 4. Lecciones aprendidas (errores ya cometidos: NO repetirlos)

- **FirmaEC:** sin `/Reason` ni `/Location` en el diccionario `/Sig` (muestra "null");
  solo `/Name` y `/M` con fecha UTC real (`gmdate`, `+00'00`).
- `/ByteRange` de ancho fijo (10 dígitos) para sustituirlo sin mover offsets; hueco de
  `/Contents` de 16 KB hex rellenado con ceros.
- La apariencia de cada firma crea **sus propias fuentes y su QR en cada actualización**;
  nunca persistas números de objeto de una preparación que puede descartarse (hubo un bug
  real con reintentos tras una firma rechazada).
- Recuadros en última página dedicada; conversión CSS→PDF:
  `x_pdf = margen_izq + left_css`, `y_pdf = alto_página − margen_sup − top_css − alto`.
  Verifica visualmente con `pdftoppm` que el sello caiga dentro del recuadro.
- Sello visible **estilo FirmaEC**: QR a la izquierda (texto
  `FIRMADO POR:/RAZON:/LOCALIZACION:/FECHA:/VALIDAR CON: www.firmadigital.gob.ec`) y a la
  derecha "Validar únicamente en FirmaEC." / "Firmado electrónicamente por:" / NOMBRE en
  Courier-Bold grande con salto de línea automático.
- Mensajes claros: clave incorrecta, certificado vencido, cédula que no corresponde.
- Los correos arman el enlace con **URL base configurable** (con respaldo al host actual);
  sin ella salen enlaces relativos rotos.
- `ini_set('session.cookie_httponly')` siempre ANTES de `session_start()`.

---

## 5. Seguridad (no negociable)

- PDFs firmados **fuera de la carpeta pública** (`storage/` con `Require all denied`);
  se sirven solo por PHP con sesión válida o token vigente.
- Tokens de 32 bytes (`random_bytes`); en BD **solo su hash** SHA-256; expiran
  (configurable) y se invalidan al firmar, rechazar, anular o reenviar.
- Página de firma con **CSP estricta y nada de CDN**:
  `default-src 'none'; script-src 'self'; style-src 'self'; connect-src 'self';
  frame-src 'self'; img-src 'self'; base-uri 'none'; form-action 'self'` +
  `X-Frame-Options: DENY` + `Referrer-Policy: no-referrer`.
- CSRF en todos los formularios, consultas preparadas, escapado de toda salida,
  límite de intentos por IP y por firma.
- El `.p12` y su clave: solo en el navegador; nunca en localStorage, cookies ni logs.
- Credenciales (SMTP, BD) fuera del repositorio, con archivo `.example` de muestra.

---

## 6. Qué espero de tu plan de fases

Propón las fases que tengan sentido para ESTE sistema, pero deben incluir como mínimo:

- Una **prueba de concepto** temprana: un PDF de este sistema firmado por 3 certificados
  de prueba en orden, `pdfsig` con todas las firmas válidas, y la prueba con mi
  certificado real para que yo lo valide en FirmaEC. Pruebas negativas: byte alterado,
  cédula ajena, certificado vencido y clave incorrecta, todas rechazadas con mensaje claro.
- El circuito completo adaptado al proceso de este sistema, con migración de base de
  datos **idempotente** que no borre nada.
- Una **prueba de punta a punta automatizada** del circuito (hay un ejemplo en el repo
  original) y la prueba negativa del firmante con certificado ajeno.
- Documentación final: cómo funciona, cómo se instala, cómo recompilar el JS y un manual
  corto "cómo firmo un documento".

## 7. Lo que NO debes hacer

- No usar FirmaEC web ni servicios del MINTEL ni ningún servicio externo de firma.
- No enviar el `.p12` ni la clave al servidor bajo ninguna circunstancia.
- No usar `exec` ni requerir Node en el servidor; no cargar CDN en la página de firma.
- No regenerar un PDF que ya tiene firmas.
- No guardar tokens en claro ni subir certificados reales o credenciales al repositorio.
