# Informe de la Fase 4 — Firma en línea para TODOS los formularios (1 a 6)

**Fecha:** 2026-11-17 · **Estado: COMPLETADA (pendiente del visto bueno del usuario)**

## 1. El cambio de proceso

Los formularios 1 a 6 ya **no generan un PDF para descargar, firmar con FirmaEC aparte y
enviar por correo a soporte**. Ahora el proceso es el mismo de las actas de equipos:

1. El funcionario llena el formulario y pulsa **"Continuar y firmar en línea"**.
2. El PDF se genera **una sola vez** (con los recuadros de firma reservados en una página
   final) y el **solicitante lo firma ahí mismo** con su archivo .p12.
3. Los demás firmantes reciben su **enlace personal por correo, uno por uno y en orden**;
   cualquiera puede **rechazar** con motivo (se avisa al solicitante y a soporte).
4. Al completarse, el **PDF firmado llega por correo a todos los firmantes y al correo de
   soporte** (email_admin). Nadie envía nada manualmente.

Por decisión institucional **no hay verificación en línea**: los documentos se validan
únicamente en FirmaEC (los QR de los sellos lo indican).

## 2. Quién firma en cada formulario (tomado de las plantillas aprobadas)

| Formulario | Orden de firmas |
|---|---|
| 1. Acuerdo de confidencialidad | 1) Receptor (externo) → 2) OSI |
| 2. Acceso a sistemas (terceros) | 1) Solicitante → 2) Director de la entidad externa → 3) Director del área ARCONEL → 4) Director/a TIC |
| 3. Acceso a sistemas informáticos | 1) Solicitante → 2) Director/Jefe de área |
| 4. Acceso VPN | 1) Solicitante → 2) Su jefe inmediato → 3) Área ARCONEL responsable (solo si se la indica, externos) → 4) Director/a TIC |
| 5. Privilegios de directorio activo | 1) Solicitante → 2) Director/Jefe de área |
| 6. Accesos especiales de internet | 1) Solicitante → 2) Director/Jefe de área |

- Los formularios ahora piden el **correo de cada firmante** (ahí llega su enlace). Para
  firmantes de la ARCONEL se exige el dominio institucional; para externos, cualquier correo.
- **Toda persona que firma debe tener cédula ecuatoriana válida** (la firma se valida
  contra ella). Un externo con pasaporte no puede firmar electrónicamente con FirmaEC.
- El OSI y la Dirección TIC firman con los datos precargados del admin: hay que
  **configurar sus correos** en el panel (pestaña Autoridades, campos nuevos).

## 3. Qué se construyó

| Pieza | Archivo | Qué hace |
|---|---|---|
| Esquema | `config/fir_schema.php` | Tablas `fir_documentos` y `fir_firmas` (migración idempotente, botón del admin o install). |
| Flujo | `includes/eqr/fir_flujo.php` | Mismo motor probado (PdfSigner/CmsVerifier): crear documento, tokens con hash, firmas en orden, rechazo, anulación, reenvío, y el armado del circuito de firmantes por formulario. |
| Proceso | `process.php` | Tras validar, arma el circuito, genera el PDF con los recuadros (hasta 4, en cuadrícula) y lleva al solicitante directo a `firmar.php`. Los PDF van a `storage/documentos/` (no público). |
| Página de firma | `firmar.php` + `firmar_api.php` + `firmar_acta.php` | Ahora atienden los dos circuitos (actas de equipos y formularios) con el mismo token y la misma pantalla. |
| Correos | `Notificador` | Turno de firma, rechazo (solicitante + soporte) y entrega final con el PDF adjunto a todos + soporte, registrados en `eqr_notificaciones` (columna nueva `documento_id`). |
| Bandeja admin | `admin/eqr_documentos.php` | "Formularios en firma": estado de cada firma, reenviar enlace, anular y descargar el PDF. |
| Formularios | `forms/form1..6.php` | Campos de correo de los firmantes, textos e instrucciones del nuevo proceso, botón "Continuar y firmar en línea". |
| Plantillas | `templates/pdf_form1..6.php` | Los bloques de firmas manuscritas se reemplazaron por la página de recuadros reservados. |

## 4. Pruebas (todas en verde)

- Formulario 5 de punta a punta: solicitante firma al crear → autorizador por token →
  completado → PDF final "enviado" a los 2 firmantes + soporte (registrado; SMTP local
  simulado). `pdfsig`: 2 firmas válidas. ✅
- **Negativa:** solicitante con certificado de cédula ajena → rechazado por el servidor. ✅
- Correo no institucional de un firmante interno → formulario rechazado con mensaje claro. ✅
- Rechazo por el autorizador → documento Rechazado + avisos registrados. ✅
- **Cuadrícula de 4 firmas** (como el formulario 2): las 4 estampadas en su recuadro,
  `pdfsig` 4 válidas. ✅
- Migración `fir` ejecutada dos veces: idempotente. ✅
- Regresión completa: Fase 2 (25 comprobaciones) y Fase 3 (19) siguen pasando. ✅

## 5. Para poner en producción

```bash
cd /home/arconel/formularios.arconel.gob.ec
git pull
chown -R arconel:arconel .
chmod -R 775 storage
```

1. Panel admin → `admin/eqr_migrar.php` → **Ejecutar migración** (crea las tablas fir_*).
2. Panel admin → pestaña **Autoridades** → completar el **correo del OSI** y el **correo
   de la Dirección TIC** (sin ellos, los formularios 1, 2 y 4 avisan que falta configurarlos).
3. Probar un formulario sencillo (5 o 6) de punta a punta con certificados reales y
   validar el PDF final en FirmaEC.

## 6. Notas y pendientes

- Los certificados de prueba de la Fase 0 tienen cédulas ficticias que NO pasan el módulo 10;
  para las pruebas de formularios se generaron certificados de prueba con cédulas válidas
  (no están en el repositorio).
- El flujo antiguo (PDF descargable público) queda solo como respaldo interno en
  `process.php`; ningún formulario lo usa ya.
- Fase 5 (cierre): pruebas consolidadas, manual de uso y retiro de `poc/` del servidor.
