# Workflow: IMPLEMENT_FEATURE

## Proposito
Implementar una funcionalidad completa de forma ordenada, desde analisis hasta QA y documentacion.

## Secuencia recomendada
1. Orchestrator analiza solicitud y alcance.
2. Architect define estructura y dependencias.
3. Business Rules Engineer valida reglas criticas.
4. Database Engineer diseña migraciones y permisos.
5. Backend Engineer implementa servicios, controladores y rutas.
6. UI/UX Engineer define flujo y estados visuales.
7. Frontend Engineer implementa vistas e interacciones.
8. Mobile Responsive Engineer ajusta responsive.
9. Security Engineer revisa permisos, CSRF, archivos y datos.
10. QA Engineer prueba casos criticos.
11. Documentation Engineer actualiza docs.
12. Orchestrator entrega reporte final.

## Paralelismo permitido
- UI/UX puede trabajar en paralelo con Database si el contrato funcional esta claro.
- Documentation puede avanzar con decisiones aprobadas.
- QA solo entra cuando exista implementacion verificable.

## Criterio de cierre
La funcionalidad esta implementada, probada, documentada y con riesgos conocidos.

