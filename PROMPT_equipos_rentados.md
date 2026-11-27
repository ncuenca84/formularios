# Prompt para Claude Code: módulo de Equipos Rentados con firma electrónica en línea

> Copia todo este archivo como primer mensaje a Claude Code, dentro del repositorio `formularios` (rama `claude/php-pdf-form-generator-RmXT9`). Copia también el acta modelo `ACTA01.DOC` en `docs/actas/` antes de empezar.

---

## 0. Cómo quiero que trabajes

- No soy programador. Explícame cada decisión técnica en español claro, sin jerga innecesaria.
- Quiero calidad profesional, sin atajos: código ordenado, validado, seguro y probado.
- Trabaja **por fases**. Al terminar cada fase **detente**, muéstrame qué hiciste, cómo lo probaste y qué falta. No sigas sin mi visto bueno.
- **No rompas lo que ya funciona.** Los formularios 1 a 6 deben seguir igual. Antes de tocar un archivo existente, dime qué vas a cambiar.
- Si algo de este documento no cuadra con el código real, o ves un riesgo, **avísame antes** de decidir por tu cuenta.
- Todo el texto visible para el usuario va en español, con tildes correctas.

---

## 1. Contexto del sistema actual (ya analizado)

Sistema de formularios institucionales de ARCONEL en `formularios.arconel.gob.ec`.

- **Tecnología:** PHP puro (≥ 8.0), Bootstrap 5, DomPDF 2 (Composer), MySQL/MariaDB con PDO.
- **Servidor:** CWP (CentOS Web Panel). **Supón que `exec`, `shell_exec`, `system` y similares están deshabilitados**, y que **no hay Node.js** en el servidor. Todo lo del servidor debe funcionar con PHP y sus extensiones (`openssl`, `pdo_mysql`, `mbstring`, `fileinfo`).
- **Estructura:**
  - `index.php`: lista de formularios (arreglo `$formularios` del 1 al 7)
  - `forms/formN.php`: pantalla de cada formulario
  - `templates/pdf_formN.php`: plantilla PDF de cada formulario; devuelve el HTML con `ob_get_clean()`
  - `process.php`: valida (`validarPorTipo`), genera el PDF con DomPDF, guarda en `solicitudes` y muestra `resultado.php`
  - `includes/functions.php`: `getDB()` (en `config/database.php`), `getConfig()`, `getAllConfig()`, `guardarSolicitud()`, `enviarNotificacionAdmin()` (usa `mail()`), `renderPdfEncabezadoInstitucional()`, `getFormularioMeta()`
  - `includes/layout.php`: `renderHeadHtml()`, `renderHeader()`, `renderFooterHtml()`
  - `admin/`: `login.php`, `index.php` (pestañas por JavaScript), `save_settings.php`, `logout.php`. Hay un solo tipo de usuario: admin (tabla `admins`)
  - `install.php` y `config/setup.sql` crean las tablas: `solicitudes`, `configuracion`, `admins`, `formularios_meta`
- **El número 7 está fijo en varios lugares.** **No** se agrega un formulario 8 público, pero sí hace falta un encabezado institucional nuevo para las actas del módulo:
  - `formularios_meta` y `install.php`: agregar la fila 8 para las actas de equipos rentados
  - `admin/index.php`: el `for ($i = 1; $i <= 7 …)` y `$nombresFormularios`, para que esa fila se pueda editar en "Encabezados por formulario"
  - `index.php` y `process.php` **no cambian** para este módulo; solo cambia el formulario 7 (sección 3.4)
- **Debilidades actuales que este trabajo debe corregir o, como mínimo, no repetir:**
  - Los formularios del admin no tienen protección CSRF. Todo lo nuevo debe tenerla.
  - Los PDF se guardan en `assets/uploads/pdfs/`, que es una carpeta pública, con nombres poco aleatorios. **Las actas nuevas no deben ser públicas** (ver sección 7).
  - `process.php` muestra errores con `alert()` de JavaScript, armado con texto del usuario. En lo nuevo usa mensajes en la página y escapa todo.

---

## 2. Qué hay que construir (resumen)

1. **Inventario de equipos rentados** en el panel admin, organizado por **contrato de arrendamiento**.
2. **Circuito de actas con firma electrónica en línea** para asignación, traspaso y devolución. **Solo el administrador del contrato crea estos trámites**, desde el panel admin, y firma en el mismo momento de crearlos. Luego el sistema envía por correo el enlace de firma a cada persona, **una por una, en orden**. El responsable (custodio) de un equipo **solo cambia cuando el acta tiene todas sus firmas**.
3. **No hay formulario público para traspasos ni devoluciones.** El funcionario pide el cambio por los canales de siempre (correo o en persona) y el administrador del contrato lo registra.
4. **Formulario 7 adaptado (público):** la salida de equipo de cómputo pasa a ser **solo para equipos rentados**. La llena el funcionario, toma el equipo del inventario y firman en línea el custodio y su jefe inmediato.
5. **Firma en el navegador** con el archivo `.p12` de cada funcionario. **Sin FirmaEC web y sin servicios del MINTEL**, sin instalar nada y sin descargar ni subir archivos. El `.p12` y su clave **nunca salen de la PC del usuario**.
6. **Correo por SMTP institucional** (sección 8).

---

## 3. Reglas del negocio (definidas, no cambiar sin consultarme)

### 3.1 Contratos

- Puede haber más de un contrato de arrendamiento. Hoy existe: Contrato **ARCONEL-005-2026**, proceso **SIE-ARCONEL-2026-003**, objeto "ARRENDAMIENTO DE EQUIPOS DE CÓMPUTO", **60 equipos**.
- Cada contrato registra a su **Administrador del contrato / Custodio Administrativo** (nombre, cédula, cargo, correo, calidad). **Nada de esto va fijo en el código.** Se edita desde el admin, y si cambia la persona, los trámites nuevos usan a la nueva.
- Al crear cada trámite se **copian** los datos del administrador vigente dentro del trámite. Así, un cambio posterior no altera las actas ya emitidas ni los trámites en curso.
- Cada contrato guarda también los **textos de cláusulas** del acta (compromisos, consideraciones, aceptación). Esos textos mencionan el número de contrato, los 60 equipos y la cobertura de 3 robos. Deben poder editarse desde el admin, con los textos de `docs/actas/ACTA01.DOC` como valor inicial.

### 3.2 Tipos de trámite y firmantes (el orden es obligatorio)

| Trámite | Quién lo crea | Firma 1 (al crear) | Firma 2 | Firma 3 | ¿Cambia el custodio? |
|---|---|---|---|---|---|
| **Asignación** (TICs → funcionario) | Administrador del contrato, en el panel | Administrador del contrato | Nuevo custodio | — | Sí, al completarse |
| **Traspaso** (funcionario → funcionario) | Administrador del contrato, en el panel | Administrador del contrato | Custodio anterior | Nuevo custodio | Sí, al completarse |
| **Devolución** (funcionario → TICs) | Administrador del contrato, en el panel | Administrador del contrato | Custodio que devuelve | — | Sí, el equipo vuelve a "En stock" |
| **Salida del edificio** (formulario 7, público) | El custodio | Custodio | Jefe inmediato | — | No. Solo registra la salida |

- **Creación y primera firma en un solo paso.** El administrador del contrato entra al panel con su usuario admin, llena el trámite, revisa el acta en pantalla y la firma ahí mismo con su `.p12`. Solo después de esa firma se envía el enlace al siguiente firmante.
  - Si cierra la pantalla sin firmar, el trámite queda en estado `borrador`: no envía nada, no bloquea al equipo para otros trámites y se puede retomar o descartar.
  - La cédula del certificado debe coincidir con la del administrador registrado en el contrato. Si otro usuario admin intenta firmar con un certificado distinto, se rechaza. Así, en la práctica, solo el administrador del contrato puede emitir actas, aunque haya varios usuarios admin.
- Los **demás firmantes** firman **uno por uno, en el orden de la tabla**. Cada uno recibe su enlace solo cuando firmó el anterior.
- El **jefe inmediato** participa **únicamente** en la salida del edificio (formulario 7). En ningún otro trámite.
- **Al completarse** (última firma), el sistema aplica el cambio de custodio y envía la **alerta de cierre** al administrador del contrato, con copia del acta final a todos los firmantes.
- **Si alguien no firma, el trámite no avanza** y el equipo sigue a nombre del custodio anterior. No hay vencimiento automático.
- El **firmante de turno puede rechazar**, indicando el motivo. Entonces el trámite queda "Rechazado", sin cambios en el custodio, y se notifica al administrador del contrato.
- El **admin** puede **anular** un trámite pendiente (con motivo) y **reenviar** el enlace de firma.
- Un equipo **no puede tener dos trámites de custodia abiertos a la vez** (asignación, traspaso o devolución). Una salida del edificio solo se permite si el equipo está "Asignado" y no tiene un traspaso o devolución en curso.

### 3.3 Estados

- **Equipo:** `en_stock`, `asignado`, `en_reparacion`, `retirado` (devuelto al proveedor). Además, una marca aparte **"fuera de las instalaciones"** cuando hay una salida vigente.
- **Trámite:** `borrador` (creado, falta la firma del administrador) → `en_firma` → `completado` | `rechazado` | `anulado`.
- **Firma (por cada firmante):** `en_espera` (todavía no es su turno), `pendiente`, `firmado`, `rechazado`.

### 3.4 Salida del edificio (formulario 7)

- Sigue pidiendo lo que hoy pide (motivo, fechas, lugar, accesorios, observaciones), pero el equipo **se elige del inventario**: el custodio ingresa su cédula y el código del equipo, y el sistema confirma que el equipo está a su nombre.
- Firman en línea el custodio y el jefe inmediato (con nombre, cédula, cargo y **correo institucional**, que son obligatorios).
- Al completarse, el equipo queda marcado "fuera de las instalaciones" con su fecha estimada de retorno. El admin registra el **retorno** con un botón, que queda en la bitácora.
- La plantilla `templates/pdf_form7.php` se conserva como base del acta de salida.

### 3.5 Identificación de las personas

- La **cédula** (10 dígitos, validada con el algoritmo de módulo 10 de Ecuador) es obligatoria para todo firmante, porque la firma se valida contra ella.
- El **correo institucional** es obligatorio. Acepta solo el dominio configurado en el admin (por defecto `arconel.gob.ec`).
- Datos de la persona: apellidos y nombres, cédula, correo, cargo, coordinación o dirección.
- En esta primera versión, el administrador escribe a mano en el panel los datos del nuevo custodio. Agrega **autocompletado con las personas que ya existen en el sistema** (por cédula). Integrar el Active Directory queda para el futuro, así que no lo hagas ahora, pero no lo impidas en el diseño.

---

## 4. Firma electrónica en el navegador (la parte crítica)

### 4.1 Requisitos

- El funcionario abre el enlace de firma que le llegó por correo, ve el acta **en pantalla** (visor PDF dentro de la página), pulsa **"Firmar"**, elige su archivo `.p12`/`.pfx`, escribe su clave y listo.
- El administrador del contrato firma con **el mismo componente**, pero dentro del panel admin, en el paso final de crear el trámite.
- **El `.p12` y la clave se procesan solo en el navegador.** Jamás se envían al servidor, no se guardan en `localStorage` ni en cookies y no se registran en logs. Al terminar, limpia las variables.
- **Nadie usa token USB**, así que solo se soporta archivo `.p12`.
- El resultado debe ser una **firma PDF estándar** (`adbe.pkcs7.detached` o `ETSI.CAdES.detached`, con SHA-256), **incremental**: cada firma nueva se agrega al final sin modificar los bytes anteriores, de modo que **todas las firmas previas sigan válidas**.
- Cada firma debe ser **visible** en el acta, en el recuadro reservado para ese firmante: "Firmado electrónicamente por NOMBRE, C.C. 17XXXXXXXX, fecha y hora". Las coordenadas de cada recuadro las conoce el sistema, porque la plantilla PDF los reserva.
- El acta final debe validarse como correcta en **FirmaEC de escritorio**, en **Adobe Acrobat Reader** y con `pdfsig` (poppler).

### 4.2 Arquitectura recomendada (opción A, preferida)

1. **Servidor (PHP):** toma la versión actual del acta y le agrega por **actualización incremental** el diccionario de firma: campo `/Sig`, widget visible con su apariencia (texto del firmante) en el recuadro que le corresponde, `/AcroForm` con `SigFlags 3`, `/ByteRange` y un `/Contents` reservado (por ejemplo 16 KB en hexadecimal). Guarda esa "versión preparada" y entrega al navegador los bytes que hay que firmar (o su hash).
2. **Navegador (JavaScript):** abre el `.p12` con **node-forge** (licencia BSD) o **PKI.js** (licencia BSD), y genera una firma **CMS/PKCS#7 detached** con atributos firmados: `contentType`, `messageDigest` y `signingTime`, incluyendo el certificado del firmante y su cadena. Devuelve solo esa firma (en hexadecimal o Base64) al servidor.
3. **Servidor:** inserta la firma en `/Contents`, **verifica** el resultado (sección 4.4) y solo entonces lo acepta como la nueva versión del acta.

Ventajas: el JavaScript no manipula el PDF, el servidor controla siempre el documento y el archivo JS queda pequeño.

**Opción B (alternativa):** que el navegador haga todo con `@signpdf/signpdf` + `@signpdf/placeholder-plain` + `@signpdf/signer-p12` (licencia MIT). **Ojo, problema ya detectado en una prueba:** empaquetado para navegador con esbuild y el polyfill `buffer`, `plainAddPlaceholder` **daña el diccionario `/Catalog`**. Deja `/Pages 3 0 R >>` y después agrega `/AcroForm`, con lo que `pdfsig` deja de reconocer la firma. La misma librería en Node funciona bien, incluso con 3 firmas incrementales. Si eliges B, tienes que resolver ese defecto y demostrarlo con las pruebas de la Fase 0.

### 4.3 El JavaScript de firma

- Se compila **en mi PC o en tu entorno** con esbuild y se guarda ya compilado en el repositorio (por ejemplo `assets/js/firma.bundle.js`). El código fuente y el `package.json` van en `tools/firma-js/`, con instrucciones para recompilar. **El servidor no necesita Node.**
- Las páginas donde se firma (la pública `firmar.php` y la pantalla de firma del panel admin) **no cargan nada de CDN ni de terceros**: Bootstrap, iconos, visor PDF y el bundle se sirven desde el propio servidor. Agrega una **Content-Security-Policy** estricta solo para esa página (`script-src 'self'`, sin `unsafe-inline` ni `unsafe-eval` si la librería lo permite), además de `X-Frame-Options: DENY` y `Referrer-Policy: no-referrer`. Motivo: el usuario escribe ahí la clave de su firma electrónica.
- Mensajes claros cuando la clave es incorrecta ("La clave del archivo de firma no es correcta"), cuando el certificado está vencido o cuando la cédula del certificado no coincide.

### 4.4 Verificación en el servidor (obligatoria antes de aceptar cada firma)

Ya está probado que esto funciona con PHP 8.3 y la extensión `openssl`, **sin exec**:

```php
// Para cada firma: localizar /ByteRange [a b c d] y /Contents <hex>
$data = substr($pdf, $a, $b) . substr($pdf, $c, $d);
$hex  = substr($pdf, $b + 1, $c - $b - 2);           // sin < >
$der  = hex2bin(rtrim($hex, '0') . (strlen(rtrim($hex,'0')) % 2 ? '0' : ''));
file_put_contents($tmpData, $data);
file_put_contents($tmpSig,  $der);
$ok = openssl_cms_verify($tmpData,
        OPENSSL_CMS_BINARY | OPENSSL_CMS_NOVERIFY | OPENSSL_CMS_DETACHED,
        $tmpSignerPem, [], null, null, null, $tmpSig, OPENSSL_ENCODING_DER);
// $ok === true   -> la firma es íntegra; $tmpSignerPem tiene el certificado del firmante
// alterar 1 byte -> false
```

Comprobaciones al recibir cada firma:

1. **Integridad:** cada firma del documento verifica con `openssl_cms_verify`.
2. **Cobertura:** la firma nueva cubre todo el archivo (`c + d == tamaño del archivo`).
3. **Continuidad:** el archivo nuevo **empieza exactamente con los bytes** de la versión anterior aceptada (`strncmp` con su tamaño). Así se garantiza que nadie sustituyó el documento.
4. **Identidad:** la **cédula del certificado coincide** con la cédula del firmante esperado. Las CA ecuatorianas (Banco Central, Security Data, Registro Civil, Consejo de la Judicatura, ANF, Uanataca, etc.) no guardan la cédula en el mismo lugar. Búscala en el `serialNumber` del sujeto **y** en las extensiones del certificado (parsea con `openssl_x509_parse` y revisa el contenido de las extensiones). Si no la encuentras, compara el nombre normalizado y marca el caso para revisión del admin. **Pruébalo con certificados reales** de las CA que usan en ARCONEL; yo te facilito uno mío para pruebas locales y **nunca debe subirse al repositorio**.
5. **Vigencia:** el certificado estaba vigente al firmar.
6. **Cadena de confianza (opcional y configurable):** si el admin carga en la configuración los certificados raíz de las CA ecuatorianas, valida también la cadena. Si no los carga, la firma se acepta igual, pero la verificación queda registrada como "cadena no validada".
7. Guarda en la tabla de firmas: el CN, el número de serie del certificado, el emisor, las fechas de vigencia, el hash SHA-256 de la versión resultante, la IP, el user-agent y la fecha.

En pruebas puedes crear certificados de prueba con `openssl`, con `serialNumber=1712345678` en el sujeto, para simular la cédula.

---

## 5. Modelo de datos (propuesta: ajústala si ves algo mejor y explícamelo)

Prefijo `eqr_` para no chocar con las tablas existentes. Todas InnoDB y utf8mb4.

- **`eqr_contratos`:** id, numero, proceso, objeto, proveedor, fecha_inicio, fecha_fin, total_equipos, admin_nombre, admin_cedula, admin_cargo, admin_correo, admin_calidad (ej.: "Custodio Administrativo / Servidor autorizado DTIC"), texto_compromisos, texto_consideraciones, texto_aceptacion, activo, timestamps.
- **`eqr_equipos`:** id, contrato_id, codigo_interno (ej.: `REN-0001`, único), tipo, marca, modelo, procesador, ram, almacenamiento, pantalla, serie (única), cargador_entregado, serie_cargador, estado_fisico, estado_operativo, observaciones, estado, fuera_instalaciones (bool), fecha_retorno_estimada, custodio_* (cédula, nombre, correo, cargo, área), timestamps.
- **`eqr_personas`:** cédula (única), nombres, correo, cargo, área, última actualización. Sirve para el autocompletado.
- **`eqr_tramites`:** id, codigo (ej.: `TRM-AAAAMMDD-XXXX`), tipo (`asignacion`, `traspaso`, `devolucion`, `salida`, `carga_inicial`), equipo_id, contrato_id, estado, entrega_* y recibe_* (los datos de las personas, copiados), admin_contrato_* (copiados), jefe_* (solo salida), motivo, estado_fisico_reportado, accesorios, observaciones, datos de salida (motivo, fechas, lugar), creado_por (id del usuario admin, o `formulario7` para las salidas), solicitud_id (vínculo con `solicitudes`, solo salidas), pdf_original, pdf_actual, hash_actual, motivo_rechazo o anulación, fechas.
- **`eqr_firmas`:** id, tramite_id, orden, rol (`custodio_anterior`, `nuevo_custodio`, `administrador`, `jefe_inmediato`), cedula, nombre, correo, cargo, estado, token_hash (SHA-256 del token, nunca el token en claro), token_expira, intentos_fallidos, cert_cn, cert_serial, cert_emisor, cert_desde, cert_hasta, cadena_validada, hash_resultante, ip, user_agent, fecha_firma, motivo_rechazo.
- **`eqr_bitacora`:** id, equipo_id, tramite_id, accion, detalle (JSON), usuario (admin o "firmante: cédula"), ip, fecha. **Solo inserciones**: nunca se edita ni se borra.

Todo cambio de custodio ocurre **dentro de una transacción**: se marca el trámite como completado, se actualiza el equipo y se escribe la bitácora, todo junto. Si algo falla, no se aplica nada.

---

## 6. Pantallas

### 6.1 Públicas

- **`index.php`:** sin cambios. **No** se agrega tarjeta para traspasos ni devoluciones.
- **`forms/form7.php`:** adaptado según la sección 3.4. El custodio ingresa su cédula y el código del equipo; por AJAX se verifica que el par corresponda y se muestran marca, modelo y serie. Si no coincide, solo dice "Los datos no coinciden con un equipo a su cargo", sin revelar a quién pertenece. Limita los intentos por IP. Al enviar se crea el trámite de salida, se registra en `solicitudes` y se lleva al custodio directamente a firmar. Después, el jefe inmediato recibe su enlace por correo.
- **`firmar.php?t=TOKEN`:** la página de firma (sección 4). Muestra qué firma, el número de trámite y el estado de las firmas anteriores, y tiene los botones **Firmar** y **Rechazar** (con motivo). Si el token venció, ya se usó o no es su turno, muestra un mensaje claro.
- **`verificar.php?c=CODIGO`:** el acta lleva un **QR** que apunta aquí. Muestra el tipo de trámite, el equipo (sin datos sensibles), el estado, la lista de firmantes con fecha y el hash SHA-256 del acta final. Permite comparar un PDF que alguien tenga contra el registrado.

### 6.2 Admin (nuevo menú "Equipos Rentados" en la barra lateral de `admin/index.php`)

Usa páginas separadas (`admin/eqr_*.php`) con el mismo estilo y la misma sesión, y una plantilla común para no repetir código.

- **Tablero:** total de equipos por estado, trámites en firma, trámites con firma pendiente hace más de N días (resaltados) y salidas con retorno vencido.
- **Contratos:** crear y editar contratos, su administrador y los textos de cláusulas.
- **Equipos:** listado con filtros (estado, contrato, custodio, área, texto libre), crear y editar la ficha técnica, y una **ficha del equipo** con su historial completo de trámites y bitácora.
- **Carga inicial por CSV/Excel:** los 60 equipos ya están entregados con actas en papel. El importador:
  - carga los datos técnicos y el custodio actual
  - crea un trámite `carga_inicial` ya completado, que guarda el número o la referencia del acta física, **sin firmas**
  - muestra una **vista previa con errores por fila** antes de confirmar
  - ofrece una plantilla CSV para descargar
- **Nuevo trámite** (asistente en 3 pasos):
  1. Tipo: asignación (solo equipos en stock), traspaso o devolución (solo equipos asignados y sin otro trámite abierto)
  2. Datos: equipo, persona o personas (con autocompletado), estado físico y operativo, accesorios y observaciones
  3. Vista del acta generada en pantalla y **firma del administrador** con su `.p12`. Al firmar, el trámite pasa a `en_firma` y sale el correo al siguiente firmante
- **Trámites:** bandeja con pestañas Borradores, En firma, Completados, Rechazados y Anulados. Desde el detalle: ver y descargar el acta, ver el estado de cada firma, **reenviar el enlace**, **anular**, **retomar o descartar un borrador** y **registrar el retorno** (en salidas).
- **Reportes** exportables a CSV: equipos por custodio, por área, en stock, fuera de las instalaciones y contratos por vencer.
- **Configuración del módulo:** dominio de correo permitido, días de validez del enlace de firma (por defecto 15), URL base del sistema, certificados raíz de confianza (opcional) y textos de los correos.

---

## 7. Seguridad (no negociable)

- **Actas fuera de la carpeta pública.** Guárdalas en `storage/actas/` y bloquea el acceso directo con `.htaccess` (`Require all denied`). Se descargan solo desde PHP, con sesión de admin o con un token de firma válido.
- **Tokens de firma** de 32 bytes aleatorios (`random_bytes`). En la BD se guarda solo su hash. Expiran y se invalidan al firmar, al rechazar o al anular. Al reenviar el enlace, el token anterior deja de servir.
- **CSRF** en todos los formularios nuevos, públicos y del admin.
- **Consultas preparadas** siempre, y escapado de toda salida HTML.
- **Validación en el servidor** de todo, aunque también se valide en el navegador.
- **Límite de intentos** en la verificación cédula + código del formulario 7 y en la página de firma.
- **Credenciales fuera del repositorio.** La clave SMTP y la de la base de datos van en archivos de configuración que Git ignora (sección 8).
- Las sesiones del admin con cookies `HttpOnly`, `Secure` y `SameSite=Lax`.
- Los PDF que llegan del navegador **nunca se aceptan sin pasar la verificación de la sección 4.4**.

---

## 8. Notificaciones por correo

Se envía por **SMTP institucional** usando **PHPMailer** (agrégalo con Composer: `phpmailer/phpmailer`).

- **Datos del servidor:**
  - Servidor: `app.arconel.gob.ec`
  - Puerto: `587`, con STARTTLS
  - Usuario y remitente: `notificaciones@app.arconel.gob.ec`, con nombre visible "Sistema de Formularios ARCONEL"
  - Contraseña: **no está en este documento**. La coloco yo directamente en el servidor
- **Dónde va la configuración:** crea `config/mail.php`, que lee esos valores y está en `.gitignore`. Incluye en el repositorio un `config/mail.example.php` con valores de ejemplo y sin clave. Nunca imprimas la clave en pantalla, en logs ni en mensajes de error.
- **Una sola puerta de salida:** crea **una sola clase** `Notificador` por la que pasen **todos** los correos del módulo. Si mañana cambia el servidor de correo, solo se toca esa pieza.
- **Prueba desde el admin:** un botón "Enviar correo de prueba" en la configuración del módulo, que muestre si la conexión SMTP funcionó o el error exacto (sin revelar la clave).
- **Registro:** guarda cada envío en una tabla `eqr_notificaciones` (destinatario, asunto, trámite, fecha, resultado y error si lo hubo). Si un correo falla, el trámite **no se pierde**: el admin ve el aviso y puede reenviar.
- Correos HTML sencillos, con versión en texto plano, en español y con el logo institucional.
- Correos a enviar:
  - **Al firmante de turno:** su enlace de firma, el equipo, el tipo de trámite y quién lo creó
  - **Al administrador del contrato:** si alguien rechaza (con el motivo)
  - **Al administrador del contrato (alerta de cierre) y a todos los firmantes:** cuando el trámite se completa, con un enlace para descargar el acta final
  - **Recordatorio:** reenvío manual desde el admin. Deja preparado, pero desactivado, un script para recordatorios automáticos por cron
- Al terminar, recuérdame revisar que los correos de `app.arconel.gob.ec` lleguen a la bandeja de entrada de `@arconel.gob.ec` y no a spam (SPF y DKIM del dominio).

---

## 9. Actas PDF

- Usa DomPDF y el encabezado institucional existente (`renderPdfEncabezadoInstitucional`). Agrega la fila 8 en `formularios_meta`, con el código `GTIC.PA.FO.08`, que sea editable en el admin.
- **Acta de asignación:** reproduce `docs/actas/ACTA01.DOC`:
  - las 6 secciones: datos del usuario final, datos del equipo, constancia de entrega y recepción, compromisos, consideraciones y aceptación
  - el bloque de firmas "ENTREGA MATERIAL POR LA DTIC" / "RECIBE COMO USUARIO FINAL"
  - las cláusulas salen del contrato (3.1)
- **Acta de traspaso** y **acta de devolución:** derivadas de la de asignación, con las mismas cláusulas aplicables. Adapta la redacción de la constancia y la aceptación al tipo de trámite. **Márcame claramente los textos nuevos** para que los revise con el área legal antes de producción.
- **Acta de salida:** basada en `templates/pdf_form7.php`.
- Cada acta lleva el **código del trámite**, el **QR de verificación** y un **recuadro reservado por firmante**, con tamaño y posición fijos y conocidos por el código de firma.
- El acta se genera una sola vez al crear el trámite. Desde ahí solo crece con firmas incrementales: **nunca se regenera**.

---

## 10. Fases y criterios de aceptación

### Fase 0: Prueba de concepto de la firma (detente al terminar)

Prototipo mínimo, fuera del sistema, que demuestre:

- Un PDF generado con DomPDF, firmado **en el navegador** (Chrome y Edge) por 3 certificados `.p12` distintos, en orden, con las firmas **visibles**.
- `pdfsig` muestra las 3 firmas como "Signature is Valid".
- Las 3 firmas se ven válidas en **Adobe Reader** y en **FirmaEC de escritorio**. Para esto dame el PDF y yo lo pruebo.
- La verificación PHP de la sección 4.4 acepta el documento correcto y **rechaza**: un byte alterado, un PDF distinto, la firma de una cédula que no corresponde y un certificado vencido.
- La clave `.p12` incorrecta muestra un mensaje claro.
- Informe de qué opción usaste (A o B), por qué, el tamaño del JS y las librerías con sus licencias.

### Fase 1: Base de datos, contratos, equipos y carga inicial

- Migración idempotente: `install.php` y un script de actualización para el servidor que ya está en producción, **sin borrar datos**.
- CRUD de contratos y equipos, el importador CSV con vista previa y la ficha del equipo.

### Fase 2: Trámites y firma en línea

- Asistente "Nuevo trámite" en el admin (asignación, traspaso y devolución), con la firma del administrador al crear.
- El circuito de firmas completo, borradores, rechazo, anulación, reenvío, bitácora y notificaciones por SMTP con PHPMailer.
- Prueba de punta a punta: un traspaso con 3 firmas (administrador, custodio anterior y nuevo custodio), donde el custodio cambia solo al final, con la transacción, la bitácora y la alerta de cierre correctas.
- Prueba negativa: un usuario admin que firma con un certificado cuya cédula no es la del administrador del contrato debe ser rechazado.

### Fase 3: Formulario 7 adaptado

- Salida con equipo del inventario, firma del custodio y del jefe, marca "fuera de las instalaciones" y registro del retorno.

### Fase 4: Reportes, verificación pública, tablero y endurecimiento

- `verificar.php` con QR, reportes CSV, tablero, revisión de seguridad de la sección 7 y cabeceras CSP.

### Fase 5: Cierre

- Pruebas automatizadas de lo crítico: verificación de firmas, reglas de estados y permisos.
- Un `docs/MODULO_EQUIPOS_RENTADOS.md` con: cómo funciona, cómo se instala o actualiza en CWP, cómo recompilar el JS y un manual corto para el usuario final ("cómo firmo un acta").
- Lista de lo que queda pendiente.

---

## 11. Lo que NO debes hacer

- No uses FirmaEC web ni servicios del MINTEL, ni ningún servicio externo de firma.
- No envíes el `.p12` ni la clave al servidor bajo ninguna circunstancia.
- No uses `exec` ni comandos del sistema en el servidor.
- No cargues scripts de CDN en la página de firma.
- No pongas en el código nombres de personas, cédulas, correos ni el número de contrato. Todo eso es configurable.
- No regeneres un acta que ya tiene firmas.
- No subas certificados reales, claves ni datos personales al repositorio.
- No cambies el comportamiento de los formularios 1 a 6.
- No crees formularios públicos para traspasos, asignaciones ni devoluciones: eso es solo del panel admin.
- No pongas la clave SMTP en el código ni en el repositorio.
