# Informe de la Fase 1 — Base de datos, contratos, equipos y carga inicial

**Fecha:** 2026-11-16 · **Estado: COMPLETADA (pendiente del visto bueno del usuario)**

## 1. Qué se construyó

| Pieza | Archivo | Qué hace |
|---|---|---|
| Esquema del módulo | `config/eqr_schema.php` | Las 7 tablas `eqr_*` (contratos, equipos, personas, trámites, firmas, bitácora, notificaciones) + fila 8 de `formularios_meta` + configuración del módulo. **Migración idempotente**: se puede ejecutar mil veces sin borrar nada. |
| Textos del acta | `config/eqr_textos_default.php` | Compromisos, consideraciones y aceptación copiados fielmente de `docs/actas/ACTA01.DOC`; se usan como valor inicial editable de cada contrato. |
| Común del módulo | `admin/eqr_common.php` | Sesión de admin obligatoria, **CSRF en todos los formularios**, validación de cédula ecuatoriana (módulo 10), bitácora solo-inserción, layout propio. |
| Migración en producción | `admin/eqr_migrar.php` | Botón para crear las tablas en el servidor que ya está en producción. `install.php` también la ejecuta en instalaciones nuevas. |
| Tablero | `admin/eqr_dashboard.php` | Equipos por estado, fuera de instalaciones, trámites en firma y última actividad. |
| Contratos | `admin/eqr_contratos.php` | CRUD del contrato con los datos del administrador del contrato (nada fijo en el código) y los 3 textos de cláusulas prellenados del acta modelo. |
| Equipos | `admin/eqr_equipos.php` | Listado con filtros (estado, contrato, texto libre), ficha técnica completa (los 13 campos del acta), sugerencia automática del siguiente código `REN-NNNN`. |
| Ficha del equipo | `admin/eqr_equipo_ficha.php` | Datos técnicos + historial de trámites + bitácora. |
| Importador CSV | `admin/eqr_importar.php` + `eqr_plantilla_csv.php` | Dos pasos: vista previa con **errores por fila** (nada se guarda) → confirmación en **una transacción** (todo o nada). Plantilla CSV descargable con BOM UTF-8 para Excel. |

## 2. Reglas de negocio implementadas

- Fila del CSV **con custodio** → equipo `asignado` + trámite `carga_inicial` **completado** que guarda la referencia del acta física + persona registrada para el futuro autocompletado.
- Fila **sin custodio** → equipo `en_stock`.
- Cédulas validadas con el algoritmo módulo 10 de Ecuador.
- Duplicados detectados contra la BD y dentro del propio archivo (código interno y serie).
- La bitácora `eqr_bitacora` solo acepta inserciones (nunca se edita ni borra desde el código).
- `formularios_meta` fila 8 (`GTIC.PA.FO.08`) editable en el admin ("Encabezados por Form.").

## 3. Pruebas realizadas (en un entorno local con MariaDB + sesión y CSRF reales)

- Migración ejecutada dos veces: idempotente, sin errores ni pérdida de datos. ✅
- Contrato ARCONEL-005-2026 creado vía web con tildes correctas (UTF-8 verificado en BD). ✅
- CSV con 3 filas (2 buenas, 1 con cédula inválida): la vista previa marca la fila mala y
  **la confirmación se niega** mientras haya errores. ✅
- CSV limpio confirmado: equipos, trámite `carga_inicial` completado con acta física,
  persona y bitácora, todo en una transacción. ✅
- Reimportar el mismo CSV: detecta "el código ya existe" y "la serie ya existe". ✅
- POST sin token CSRF → **403**. Sin sesión → redirige al login. ✅
- Formularios públicos 1–7: **no se tocó ninguno**.

## 4. Para probar en su servidor

1. `git pull` + fix de permisos (como siempre).
2. Entrar al panel admin → botón verde **"Equipos Rentados"** → le pedirá ejecutar la migración (un clic).
3. Registrar el contrato real en **Contratos** (los textos de cláusulas ya vienen prellenados del acta).
4. Descargar la **plantilla CSV**, llenar los 60 equipos (o unos pocos de prueba) e importar.

## 5. Qué sigue (Fase 2)

Trámites de asignación/traspaso/devolución con el circuito de firmas en línea,
notificaciones SMTP con PHPMailer y la firma del administrador al crear cada trámite.
