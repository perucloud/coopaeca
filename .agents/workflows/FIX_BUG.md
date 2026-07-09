# Workflow: FIX_BUG

## Proposito
Diagnosticar, corregir, probar y documentar un error sin introducir regresiones.

## Secuencia recomendada
1. Orchestrator reproduce o delimita el bug.
2. Selecciona especialista principal segun area.
3. Especialista identifica causa raiz.
4. Se aplica correccion minima y segura.
5. Security Engineer revisa si el bug afecta seguridad.
6. QA Engineer valida reproduccion, fix y regresion.
7. Documentation Engineer documenta si cambia comportamiento.
8. Orchestrator reporta resultado.

## Criterio de cierre
Bug corregido, prueba ejecutada y riesgo residual informado.

