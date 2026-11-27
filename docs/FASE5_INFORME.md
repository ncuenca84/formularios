# Informe de la Fase 5 — Cierre del proyecto

**Fecha:** 2026-11-18 · **Estado: COMPLETADA**

## 1. Qué se hizo en esta fase

| Pieza | Detalle |
|---|---|
| Pruebas automatizadas en el repositorio | Carpeta `tests/`: tres suites (equipos/firmas, salidas/retorno, formularios en línea) con ejecutor único `PRUEBAS_OK=1 php tests/ejecutar_pruebas.php`. Protegidas con una variable de entorno para que jamás corran por accidente contra producción. Los certificados de prueba con cédulas válidas se generan automáticamente (requiere Node, solo en desarrollo). |
| Manual del sistema | `docs/MODULO_EQUIPOS_RENTADOS.md`: cómo funciona todo (formularios 1-8 y equipos rentados), instalación/actualización en CWP, configuración que vive en el servidor, manual corto "cómo firmo", cómo recompilar el JS y la lista de pendientes/decisiones. |
| Retiro de `poc/` | La prueba de concepto de la Fase 0 salió del repositorio (los certificados de prueba se movieron a `tests/certs/`). En el servidor debe eliminarse con `rm -rf poc`. |

## 2. Resultado de la última corrida de pruebas

Las tres suites (63+ comprobaciones en total) pasaron completas desde el repositorio:
circuito de traspaso con 3 firmas, prueba negativa de cédula ajena, rechazo y anulación,
bloqueo de trámites dobles, salidas con marca "fuera" y retorno, firma en línea de
formularios (2 y 4 firmantes) y validación de todas las firmas con `pdfsig`.

## 3. Para cerrar en el servidor

```bash
cd /home/arconel/formularios.arconel.gob.ec
git pull
rm -rf poc
chown -R arconel:arconel .
```

(`git pull` elimina los archivos de `poc/` que estaban versionados; el `rm -rf poc`
limpia los restos generados en las pruebas de la Fase 0.)

## 4. Recordatorios finales

- Verificar **SPF/DKIM** del dominio `app.arconel.gob.ec` con el administrador de correo.
- Completar la **cédula de los 8 custodios** que quedaron pendientes en la carga inicial.
- El **sello de tiempo (TSA)** quedó pendiente por decisión del usuario.
- Revisar con el área legal los **textos de las actas de traspaso y devolución**
  (marcados en el informe de la Fase 2) si aún no se hizo.
