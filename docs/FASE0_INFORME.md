# Informe de la Fase 0 — Prueba de concepto de la firma electrónica en línea

**Fecha:** 2026-11-16 · **Estado: COMPLETADA Y VALIDADA EN FIRMAEC**

> **Resultado de la validación (2026-11-16):** FirmaEC 5.3.0 valida como **Válida (✓)** la firma
> generada por el sistema con un certificado real de **Lazzate Cía. Ltda. (Persona Natural)**:
> cédula extraída correctamente del certificado, entidad certificadora reconocida, no revocado.
> Con los certificados de juguete de la demo, FirmaEC responde "Entidad Certificadora no
> reconocida", que es lo esperado (la CA de prueba no está acreditada).

---

## 1. Qué se construyó

Un prototipo **aislado del sistema** (nada de los formularios 1–7 fue tocado) que demuestra
el circuito completo de firma electrónica de actas en el navegador:

| Pieza | Archivo | Qué hace |
|---|---|---|
| Preparador de firma | `poc/lib/PdfSigner.php` | Agrega a un PDF, por **actualización incremental**, el campo de firma visible, su apariencia, el AcroForm y el hueco `/Contents` de 16 KB. Devuelve los bytes a firmar. PHP puro, sin `exec`. |
| Verificador | `poc/lib/CmsVerifier.php` | Las 6 comprobaciones de la sección 4.4 del plan: integridad (`openssl_cms_verify`), cobertura, continuidad byte a byte con la versión anterior, identidad por cédula, vigencia y cadena opcional. |
| Módulo de firma JS | `tools/firma-js/src/firma.js` → `poc/assets/firma.bundle.js` | Abre el `.p12` con **node-forge**, valida vigencia, y genera la firma **CMS/PKCS#7 detached SHA-256** con atributos firmados (contentType, messageDigest, signingTime) y la cadena de certificados. El `.p12` y la clave **nunca salen del navegador**. |
| Página de prueba | `poc/index.php` + `poc/api.php` | Flujo guiado de 3 firmas en orden con visor del acta, **CSP estricta** (`script-src 'self'`, sin CDN, sin inline), X-Frame-Options DENY. |
| Prueba automática | `poc/cli_test.php` | Toda la batería de punta a punta por línea de comandos, usando el MISMO código JS que el navegador. |
| Certificados de prueba | `poc/certs/` | CA de juguete + 5 `.p12` (3 firmantes, 1 vencido, 1 con cédula que no corresponde). Clave: `prueba123`. **No son certificados reales.** |

## 2. Opción elegida: A (el servidor controla el PDF)

- **Por qué:** el JavaScript no manipula la estructura del PDF (solo firma bytes), lo que
  evita el defecto conocido de la opción B (`plainAddPlaceholder` daña el `/Catalog` al
  empaquetarse para navegador). El servidor controla siempre el documento y el bundle
  queda pequeño.
- **Librería del navegador:** node-forge 1.3.3 (licencia BSD-3-Clause / GPL dual — se usa BSD).
- **Empaquetador:** esbuild 0.25 (MIT), solo en desarrollo. **El servidor no necesita Node.**
- **Tamaño del bundle:** `firma.bundle.js` = **281 KB** minificado (node-forge completo).
- **Limitación conocida:** node-forge firma solo claves **RSA**. Los certificados de las CA
  ecuatorianas de personas naturales son RSA (2048/4096), así que cubre el caso real.
  Si apareciera un certificado de curva elíptica, se detectará en la prueba con
  certificados reales y se evaluaría PKI.js como complemento.

## 3. Resultados de las pruebas (automatizadas, `php poc/cli_test.php`)

Las 16 pruebas pasaron:

- PDF generado con DomPDF y firmado 3 veces en orden (administrador → custodio anterior
  → nuevo custodio), cada firma **visible** en su recuadro reservado.
- `pdfsig` (poppler): **las 3 firmas "Signature is Valid"**, la última cubre el documento
  completo ("Total document signed"). El aviso "Certificate issuer isn't Trusted" es el
  esperado: la CA de juguete no está instalada como confiable en el sistema; es la
  validación de cadena, no de integridad.
- Verificación del servidor **rechaza**: un byte alterado, un documento base sustituido,
  una firma con cédula que no corresponde y (en el navegador) un certificado vencido.
- Clave `.p12` incorrecta → mensaje claro: "La clave del archivo de firma no es correcta".
- El mismo circuito funciona a través de la **API web** (flujo idéntico al del navegador).

## 4. Cómo probarlo usted

1. En el servidor: `git pull`, y abrir `https://formularios.arconel.gob.ec/poc/`
   (el directorio `poc/out/` queda bloqueado por `.htaccess`; el acta solo se sirve vía PHP).
2. Pulsar "Generar acta de prueba", descargar los `.p12` de prueba desde la misma página
   (clave `prueba123`) y firmar las 3 veces en orden, **en Chrome y en Edge**.
3. Probar los casos negativos: clave equivocada, certificado vencido, cédula que no corresponde.
4. Descargar el acta final y validarla en **FirmaEC de escritorio** y **Adobe Reader**.
   También está `poc/muestra_acta_3firmas.pdf` ya firmada por la prueba automática.
5. Cuando tenga su `.p12` real a mano, repetir una firma con él **en su navegador**
   (el archivo no se sube a ningún lado) para confirmar que la cédula se extrae bien
   de los certificados de la CA que usa ARCONEL.

## 5. Qué queda para las siguientes fases

- ~~La extracción de cédula con certificados reales~~ **CONFIRMADA** con un certificado
  real de Lazzate Cía. Ltda. (la cédula se leyó correctamente y FirmaEC validó la firma).
- El sello de tiempo (TSA) no se incluye: FirmaEC no lo exige para validez, pero puede
  evaluarse más adelante.
- `poc/` debe **eliminarse o protegerse con clave** al terminar la validación de esta fase.

## 6. Decisión pendiente del usuario

- [x] FirmaEC de escritorio valida la firma con certificado real (Lazzate). Con los
      certificados de juguete responde "Entidad Certificadora no reconocida" (esperado).
- [ ] Las 3 firmas se ven y validan en Adobe Reader.
- [ ] La firma funciona en Chrome y Edge del equipo institucional.
- [ ] Visto bueno para arrancar la Fase 1.

## 7. Ajustes posteriores a la validación

- Se agregó `/Location (Quito - Ecuador)` al diccionario de firma para que FirmaEC
  no muestre "null" en la columna Razón/Localización.
- El Sellado de Tiempo aparece "No": no usamos TSA. FirmaEC valida igual; queda
  anotado como mejora opcional futura.
