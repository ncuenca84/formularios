# Sistema de Formularios ARCONEL — Manual del módulo de firma en línea y equipos rentados

**Última actualización:** 2026-11-18 · formularios.arconel.gob.ec

Este documento explica cómo funciona el sistema completo, cómo se instala y actualiza en el
servidor (CWP), cómo se recompila el componente de firma, y cómo firma un funcionario.

---

## 1. Qué hace el sistema

### 1.1 Formularios institucionales con firma en línea (públicos, 1 a 8)

| # | Formulario | Quién firma (en orden) |
|---|---|---|
| 1 | Acuerdo de confidencialidad con terceros | Receptor (externo) → OSI |
| 2 | Acceso a sistemas para terceros | Solicitante → Director entidad externa → Área ARCONEL → Director/a TIC |
| 3 | Acceso a sistemas informáticos | Solicitante → Director/Jefe de área |
| 4 | Acceso VPN | Solicitante → Su jefe → [Área ARCONEL, si se indica] → Director/a TIC |
| 5 | Privilegios de directorio activo | Solicitante → Director/Jefe de área |
| 6 | Accesos especiales de internet | Solicitante → Director/Jefe de área |
| 7 | Salida de equipo de cómputo (rentados e institucionales) | Custodio → Jefe inmediato |
| 8 | Cuenta adicional de correo electrónico | Solicitante → Director/Jefe de área |

El proceso es siempre el mismo: la persona llena el formulario, **firma ahí mismo** con su
archivo .p12, y cada firmante siguiente recibe un **enlace personal por correo**, uno por
uno y en orden. Cualquier firmante (excepto quien inició) puede **rechazar** con motivo.
Al completarse, el **PDF firmado llega por correo a todos los firmantes y al correo de
soporte** (email_admin). Las solicitudes pasan automáticamente a *Aprobada* o *Rechazada*
en el panel.

**Validación**: únicamente en **FirmaEC de escritorio** (decisión institucional: sin
verificación en línea ni validación de cadena de certificadoras). Cada sello visible
replica el formato de FirmaEC, con su QR estándar.

### 1.2 Módulo de Equipos Rentados (panel admin → Equipos Rentados)

- **Contratos**: datos del contrato de arrendamiento (precargado el ARCONEL-005-2026),
  administrador del contrato, **delegado de gestión** (firma las actas si está configurado)
  y textos de las cláusulas del acta.
- **Equipos**: inventario (códigos REN-####), custodios, estados (en stock / asignado /
  en reparación / retirado) y marca "Fuera de las instalaciones". Importador CSV con
  vista previa.
- **Trámites** con acta PDF y firmas en línea. Al crear el trámite se puede elegir la
  **fecha del acta** (la del texto "a los … días de …", por temas contractuales; vacía = hoy);
  los sellos de firma siempre llevan la fecha real de cada firma, y un acta ya firmada
  no se puede re-fechar (habría que anular el trámite y crear otro):
  - *Asignación* (stock → funcionario): firman administrador/delegado y nuevo custodio.
  - *Traspaso* (funcionario → funcionario): administrador/delegado, custodio anterior y nuevo.
  - *Devolución* (→ stock): administrador/delegado y custodio.
  - *Reposición* (el proveedor repuso el equipo por robo/daño): se actualiza la ficha
    (modelo/serie) y se re-emite el acta **al mismo custodio**; el motivo con la serie
    anterior consta en el acta.
  - *Salida del edificio* (formulario 7 público): para **equipos rentados**, verificación
    contra el inventario con cédula + **serie del equipo** (la que consta en el acta del
    funcionario) o código REN-…; para **equipos de propiedad institucional** (sin
    inventario en el sistema), el funcionario indica solo el **código del bien**, los
    accesorios y sus datos — el sistema crea un registro interno bajo el contrato
    `BIENES-ARCONEL` (inactivo) y reutiliza todo el circuito: firmas, QR de portería,
    marca "fuera" y retorno. Marca "fuera" al completarse y botón **Registrar retorno**
    en el detalle del trámite y en la ficha del equipo. **Al vencer la fecha de retorno estimada, el retorno se
    registra solo**: el equipo vuelve automáticamente al estado normal, sin trámite de
    ingreso (las salidas con retorno "indefinido" sí requieren el botón).
    El acta incluye un **QR de validación en portería**: el guardia lo escanea con su
    celular (sin instalar nada) y `salida_qr.php` le muestra SALIDA AUTORIZADA (verde)
    solo si tiene todas las firmas, está dentro del período y no se registró el retorno;
    en cualquier otro caso, NO AUTORIZADA (rojo) con el motivo. Cada escaneo queda en la
    bitácora. El guardia solo debe comprobar que la página sea del dominio institucional.
  - El custodio **solo cambia cuando el acta tiene todas sus firmas** (transacción).
  - El acta se genera **una sola vez** y nunca se regenera: solo crece con firmas.
- **Actas y pagos**: documentos del contrato (acta inicial / parcial mensual / definitiva
  e informe de conformidad + solicitud de pago), siempre a nombre del **administrador del
  contrato** (no del delegado).
- **Formularios en firma**: bandeja de los formularios 1-8 (reenviar enlace, anular, descargar).

### 1.3 Seguridad (resumen)

- El **.p12 y su clave jamás salen del navegador** (firma CMS con node-forge en el cliente).
- El servidor **verifica cada firma** antes de aceptarla: integridad criptográfica,
  cobertura total del archivo, continuidad incremental con la versión anterior,
  **cédula del certificado = cédula del firmante esperado** y vigencia.
- Tokens de firma de 32 bytes; en BD **solo su hash**; vencen (15 días configurables) y se
  invalidan al anular o reenviar. El enlace sigue sirviendo para VER el documento tras firmar.
- Páginas de firma con **CSP estricta y sin CDN**; PDFs en `storage/` (no público),
  servidos solo con sesión de admin o token vigente; CSRF en todos los formularios;
  bitácora solo-inserción; límite de intentos por IP.

---

## 2. Instalación y actualización en el servidor (CWP)

Ruta: `/home/arconel/formularios.arconel.gob.ec` · dominio `formularios.arconel.gob.ec`.

### 2.1 Actualizar (lo habitual)

```bash
cd /home/arconel/formularios.arconel.gob.ec
git pull
composer install          # solo si el pull trajo cambios en composer.lock
chown -R arconel:arconel .
chmod -R 775 storage
chmod 640 config/database.php config/mail.php
```

Después del pull, si el cambio lo indica: panel admin → `admin/eqr_migrar.php` →
**Ejecutar migración** (siempre es seguro ejecutarla: no borra nada).

### 2.2 Configuración que vive en el servidor (no en el repositorio)

| Qué | Dónde |
|---|---|
| Base de datos | `config/database.php` (BD `arconel_formdb`) |
| Clave SMTP | `config/mail.php` (copiar de `config/mail.example.php`, `chmod 640`) — buzón `notificaciones@app.arconel.gob.ec`, puerto 587 STARTTLS |
| URL base, dominio de correo, días del token | admin → Equipos Rentados → **Configuración** |
| Correos del OSI y la Dirección TIC | admin → pestaña **Autoridades** |
| Delegado de gestión del contrato | admin → Equipos Rentados → **Contratos → Editar** |

### 2.3 Instalación desde cero

1. Clonar el repositorio, `composer install`, crear la BD y `config/database.php`.
2. Abrir `install.php` (crea tablas y el usuario admin) y **eliminarlo** después.
3. Ejecutar la migración del módulo y completar la configuración (2.2).
4. Subir logos en el admin y fijar permisos como en 2.1.

---

## 3. Manual corto: "¿cómo firmo un documento?"

1. Le llega un correo *"Firma pendiente"* con el botón **Revisar y firmar** (el enlace es
   personal y vence en 15 días). Si llenó usted el formulario, pasa directo a esta pantalla.
2. Revise el documento en el visor.
3. En **Firmar**: elija su archivo de firma electrónica (**.p12**), escriba su clave y
   pulse *Firmar*. Su archivo y su clave **no se envían a ningún lado**: la firma ocurre
   en su propio navegador.
4. Mensajes posibles: *"La clave del archivo de firma no es correcta"* (clave mal escrita),
   certificado vencido, o *"la cédula no corresponde"* (está usando el .p12 de otra persona).
5. Si usted no es quien inició y no está de acuerdo, use **Rechazar** indicando el motivo.
6. Cuando todos firman, el PDF final llega a su correo. **Valídelo únicamente en FirmaEC.**

Para la DTIC: si a alguien le venció el enlace, en la bandeja correspondiente
(Trámites o Formularios en firma) está el botón **reenviar enlace** (invalida el anterior).

---

## 4. El componente de firma (para desarrolladores)

- **Servidor (PHP puro, sin exec, sin Node)**: `includes/eqr/PdfSigner.php` agrega el campo
  de firma por **actualización incremental** (sello visible estilo FirmaEC con QR, en los
  recuadros reservados de la última página) y `includes/eqr/CmsVerifier.php` verifica cada
  firma con `openssl_cms_verify`. El circuito vive en `includes/eqr/flujo.php` (equipos) y
  `includes/eqr/fir_flujo.php` (formularios); los correos salen solo por
  `includes/eqr/Notificador.php` (PHPMailer).
- **Navegador**: `assets/js/firma.bundle.js` (node-forge, licencia BSD) expone
  `ArconelFirma.firmar({p12Bytes, clave, datos})` → firma CMS/PKCS#7 detached SHA-256.
- **Recompilar el bundle** (en una PC con Node, el servidor no lo necesita):

  ```bash
  cd tools/firma-js
  npm install
  npx esbuild src/firma.js --bundle --minify --format=iife \
      --global-name=ArconelFirma --outfile=../../assets/js/firma.bundle.js
  ```

- **Pruebas automatizadas** (solo en desarrollo; requieren MariaDB local y Node):

  ```bash
  PRUEBAS_OK=1 php tests/ejecutar_pruebas.php
  ```

  Cubren: circuito completo de traspaso con 3 firmas, prueba negativa de cédula ajena,
  rechazo, bloqueo de trámites dobles, salidas con retorno, firma en línea de formularios
  (incluida la cuadrícula de 4 firmas) y la validación con `pdfsig`. **Nunca ejecutarlas
  contra la base de producción** (borran los datos de prueba `TEST-%`).

---

## 5. Pendientes y decisiones registradas

- **Sello de tiempo (TSA)**: pendiente por decisión del usuario; las firmas llevan la fecha
  del servidor, no un sello de una autoridad de tiempo.
- **Verificación en línea y cadena de certificadoras**: descartadas por decisión
  institucional; la validación oficial es FirmaEC.
- **SPF/DKIM**: verificar con el administrador de correo que los envíos de
  `app.arconel.gob.ec` lleguen a la bandeja de entrada de `@arconel.gob.ec`.
- **Custodios sin cédula** (8 al momento de la carga inicial): completarla en la ficha del
  equipo; sin ella no pueden firmar trámites ni usar el formulario 7.
- **Flujo antiguo de PDF descargable**: queda solo como respaldo interno en `process.php`;
  ningún formulario lo usa.
- **Integración con Directorio Activo** para autocompletar personas: fuera de alcance,
  prevista como mejora futura (el diseño no la impide).
- La carpeta `poc/` (prueba de concepto de la Fase 0) fue retirada del repositorio; si
  quedó en el servidor, eliminarla: `rm -rf poc`.
