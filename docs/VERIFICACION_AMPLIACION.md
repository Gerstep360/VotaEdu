# Verificación de frontend y actas de la ampliación

Fecha: 14 de octubre de 2026.

## Resultado

| Comprobación | Resultado ejecutado |
| --- | --- |
| Suite `php artisan test` | 30 pruebas aprobadas, 185 aserciones. |
| Compilación Blade | `php artisan view:cache` completado. |
| Formato PHP | Pint aplicado a los archivos modificados. |
| Interfaz | 24 capturas auténticas en 1440 × 1000 y 390 × 844, sin desbordamiento de página. |
| Roles | Acceso verificado para administrador, director, docente y estudiante. |
| CI | Completo en estudiantes, docentes y perfil; comprobado por prueba y navegador. |
| Primer ingreso | Registro de estudiante/docente y cambio obligatorio antes de acceder al panel. |
| Candidaturas | Integrantes y propuestas guardados y visibles en la boleta; imagen validada y guardada en prueba automatizada. |
| Docencia | Crear votación de curso, agregar opciones, abrir y cerrar desde una cuenta docente. |
| Votación | Estudiante incluido en el padrón registra un voto y ve su participación. |
| Exportación HTTP | Descarga real con cabecera `%PDF-` desde el botón del navegador. |
| Permisos PDF | Estudiante y docente ajeno rechazados; administrador y docente autorizado aceptados; elección abierta rechazada. |
| Acta normal | A4 vertical, una página, tildes, logo, porcentajes 60/40/0, participación 83,33 %, espacios de firma y pie. |
| Empate | Una página A4, 50/50 y estado «Empate», sin ganador arbitrario. |
| Sin votos | Una página A4, ceros y mensaje correspondiente. |
| Acta extensa | Cuatro páginas A4, 37 opciones y título largo; encabezados de tabla, emisión y numeración en las páginas. |
| Render del PDF | Revisión de PNG producidos por Poppler y comprobación de texto/tamaño con pypdf. |

La suite usa SQLite en memoria. El recorrido del navegador utiliza exclusivamente `votaedu_expansion_ui_test`, una base MySQL separada. Las actas de `output/pdf` son ejemplos ficticios generados por las pruebas, no registros de la base principal.

## Evidencia

- [Reporte del navegador](verificacion-ampliacion-ui.json).
- [Reporte de PDF](verificacion-pdf.json).
- [Panel administrativo](capturas-ampliacion/01-panel-administrativo.png).
- [Estudiantes con CI completo](capturas-ampliacion/05-estudiantes-ci-completo.png).
- [Asignaciones docentes](capturas-ampliacion/09-registro-docente-asignaciones.png).
- [Cambio obligatorio](capturas-ampliacion/12-cambio-obligatorio.png).
- [Boleta y propuestas en celular](capturas-ampliacion/14-candidaturas-boleta-celular.png).
- [Panel docente](capturas-ampliacion/16-panel-docente.png).
- [Resultados y descarga](capturas-ampliacion/17-resultados-y-descarga.png).
- [Vista previa del acta](capturas-ampliacion/18-vista-previa-acta.png).
- [Acta en celular](capturas-ampliacion/19-acta-celular.png).
- [Auditoría](capturas-ampliacion/24-auditoria-celular.png).
- [Ejemplo de acta A4](../output/pdf/acta-votaedu-demostracion.pdf).
- [Acta A4 renderizada](capturas-ampliacion/25-acta-pdf-a4.png), [empate](capturas-ampliacion/26-acta-pdf-empate.png) y [sin votos](capturas-ampliacion/27-acta-pdf-sin-votos.png).
- Acta extensa: [página 1](capturas-ampliacion/acta-pdf-extensa-1.png), [página 2](capturas-ampliacion/acta-pdf-extensa-2.png), [página 3](capturas-ampliacion/acta-pdf-extensa-3.png) y [página 4](capturas-ampliacion/acta-pdf-extensa-4.png).

## Cómo repetir la revisión

```powershell
php artisan test
php artisan view:cache

# Ejemplos PDF sobre fixtures aislados de PHPUnit:
$env:VOTAEDU_EXPORT_FIXTURES='1'
php artisan test --filter=ExpansionInterfaceAndPdfTest
Remove-Item Env:VOTAEDU_EXPORT_FIXTURES

# Servidor para verificar el frontend, en una terminal dedicada:
$env:DB_DATABASE='votaedu_expansion_ui_test'
php artisan votaedu:prepare --demo
php artisan serve --host=127.0.0.1 --port=8001 --no-reload

# En otra terminal:
python -m pip install playwright
python scripts/verify_expansion_ui.py
```

El script agrega cuentas y votaciones ficticias a esa base separada y requiere Microsoft Edge. No debe apuntarse a un servidor conectado a datos reales. Dompdf funciona dentro de PHP; las herramientas Python y Poppler usadas para revisión no son requisitos de la descarga del usuario.

Los módulos sin backend operativo se identifican en `EXPLICACION_SISTEMA_VOTAEDU.md`: CSV, transición automática por Scheduler, configuración institucional editable y reporte histórico de participación por curso. No se muestran controles de importación simulados.
