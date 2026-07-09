# Workflow: BUILD_PWA_APK_AAB

## Proposito
Preparar PWA, APK o AAB Android solo con autorizacion expresa del usuario.

## Secuencia recomendada
1. Orchestrator confirma autorizacion expresa.
2. PWA Capacitor Engineer revisa requisitos.
3. Architect valida impacto.
4. Frontend Engineer ajusta assets si aplica.
5. Security Engineer revisa configuracion sensible.
6. QA Engineer valida navegacion y build.
7. Documentation Engineer documenta comandos y pasos.
8. Orchestrator entrega reporte.

## Restricciones
- No tocar keystore sin autorizacion.
- No publicar en Play Store.
- No modificar produccion.
- No instalar dependencias sin permiso cuando el entorno lo requiera.

## Criterio de cierre
PWA/APK/AAB preparado o planificado, con pasos documentados y riesgos claros.

