# Informe de la Fase 3 — Formulario 7: salida de equipos conectada al inventario

**Fecha:** 2026-11-17 · **Estado: COMPLETADA (pendiente del visto bueno del usuario)**

## 1. Qué cambió

El formulario público **"Autorización de Salida de Equipo de Cómputo"** dejó de ser un PDF
para imprimir y firmar aparte: ahora es **solo para equipos rentados**, toma el equipo del
inventario y **todo se firma en línea**.

| Pieza | Archivo | Qué hace |
|---|---|---|
| Formulario público | `forms/form7.php` | El custodio escribe su **cédula + código del equipo** (ej. REN-0012) y pulsa Verificar: el sistema confirma que el equipo está a su nombre y muestra marca, modelo y serie. Luego llena motivo, fechas, lugar y los datos de su **jefe inmediato** (correo institucional obligatorio). |
| Verificación AJAX | `eqr_salida_api.php` | Respuesta **neutral** si el par no coincide ("Los datos no coinciden con un equipo a su cargo"), sin revelar a quién pertenece. **Límite de intentos por IP** (15 en 15 minutos). |
| Creación del trámite | `eqr_salida_crear.php` | Revalida TODO en el servidor, crea el trámite `salida` con su acta, lo registra en `solicitudes` y lleva al custodio **directamente a firmar** (firmar.php con su token). |
| Acta de salida | `includes/eqr/acta_pdf.php` | Basada en la plantilla del formulario 7 anterior: datos del custodio, del equipo, motivo y período, compromisos, aceptación, y la página de firmas con 2 recuadros (custodio solicita / jefe autoriza) con el sello estilo FirmaEC. |
| Circuito | `includes/eqr/flujo.php` | Firmas en orden: **1) custodio** (al crear, de inmediato) → **2) jefe inmediato** (enlace por correo). Al completarse: el equipo queda **"Fuera de las instalaciones"** con su fecha estimada de retorno. **El custodio NO cambia.** |
| Retorno | `admin/eqr_tramite_ver.php` | Botón **"Registrar retorno del equipo"** en el detalle del trámite (salidas completadas). Quita la marca "Fuera" y queda en la bitácora. |
| Común público | `includes/eqr/publico.php` | Sesión, CSRF y límite de intentos para las páginas públicas del módulo. |

Reglas de elegibilidad: el equipo debe estar **asignado**, **sin trámite de custodia en
curso** (asignación/traspaso/devolución), **sin otra salida en trámite** y **no estar ya
fuera**. Los mensajes se muestran solo a quien ya demostró el par cédula+código.

**No se tocó `process.php` ni los formularios 1 a 6.** La plantilla antigua
`templates/pdf_form7.php` queda como referencia (ya no se usa en el flujo).

## 2. Flujo completo (ejemplo)

1. El funcionario entra al formulario 7 → cédula + REN-00XX → **Verificar** → aparece su equipo.
2. Llena motivo (teletrabajo, comisión…), fecha de salida, retorno estimado (o indefinido),
   lugar, accesorios y los datos de su jefe.
3. Envía → el sistema crea el acta y lo lleva **directo a firmarla** con su .p12.
4. Al firmar él, el **jefe inmediato** recibe su enlace por correo y autoriza con su firma.
5. Completado: el equipo queda marcado **Fuera de las instalaciones** (visible en el listado
   de Equipos y en el tablero), con copia del acta a todos y alerta al administrador.
6. Cuando el equipo vuelve, la DTIC pulsa **Registrar retorno** en el detalle del trámite.

Si el funcionario cierra sin firmar, queda un **borrador** que el admin puede descartar, y
el enlace de su firma se puede **reenviar** desde la bandeja (ahora también para custodios
y jefes, no solo para el siguiente de la cola).

## 3. Pruebas realizadas (19 comprobaciones de punta a punta + regresión)

- Verificación neutral: cédula ajena o código equivocado → "no coincide", sin filtrar datos. ✅
- Salida creada con 2 firmas en orden; token del custodio listo al instante. ✅
- Tras la firma del custodio el equipo AÚN no está "fuera"; tras la del jefe sí, con su
  fecha de retorno, y **el custodio no cambió**. ✅
- Segunda salida bloqueada mientras está fuera; salida bloqueada si hay devolución en curso. ✅
- Retorno registrado (y el doble registro, rechazado). ✅
- `pdfsig`: las 2 firmas del acta "Signature is Valid". ✅
- Regresión Fase 2: las 25 comprobaciones del circuito admin siguen pasando. ✅

## 4. Para probar en su servidor

```bash
cd /home/arconel/formularios.arconel.gob.ec
git pull
chown -R arconel:arconel .
```
(no hay dependencias nuevas ni migración: usa las tablas existentes)

Prueba sugerida: con un equipo asignado real, entre al formulario 7 público, verifique con
la cédula del custodio y su código REN, complete y firme; el jefe firma desde su correo;
revise la marca "Fuera" en Equipos y registre el retorno desde el detalle del trámite.

## 5. Qué sigue

- **Fase 4:** `verificar.php` (verificación pública por código de trámite), reportes CSV,
  mejoras del tablero (salidas con retorno vencido resaltadas) y revisión de seguridad.
- **Fase 5:** pruebas automatizadas consolidadas y el manual `docs/MODULO_EQUIPOS_RENTADOS.md`.
- Pendientes conocidos: sello de tiempo TSA (decidiste dejarlo pendiente), carpeta `poc/`
  por retirar del servidor al cerrar el proyecto, y los 8 custodios sin cédula registrada
  (no podrán usar el formulario 7 hasta completarla en la ficha del equipo).
