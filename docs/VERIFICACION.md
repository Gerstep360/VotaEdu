# Verificación de la entrega

Fecha: 13 de octubre de 2026. PHP 8.4.12, Laravel 13, servidor MySQL compatible de XAMPP y Microsoft Edge.

| Comprobación | Resultado observado |
| --- | --- |
| `php artisan test` | 19 pruebas aprobadas, 97 aserciones; SQLite en memoria |
| `php artisan view:cache` | Todas las vistas Blade compiladas |
| Migraciones en MySQL | Siete migraciones completas, incluidas las cinco tablas del dominio |
| Preparación repetida con `--demo` | Conserva datos existentes y omite la demostración |
| Prueba de concurrencia MySQL | Dos procesos PHP simultáneos: exactamente 1 voto + 1 participación |
| Recorrido en navegador | Registro y edición de estudiante, deshabilitar/habilitar, restablecer contraseña, agregar/editar/eliminar opciones, apertura, voto cancelado y confirmado, bloqueo de repetición, cierre, resultados, filtros y eliminación de borrador |
| Escritorio | 1440 × 1000, sin desbordamiento horizontal de página |
| Celular | 390 × 844, menú y formularios utilizables; tablas administrativas adaptadas a listas |
| JavaScript | Sin errores durante el recorrido automatizado |
| Asistente Python | GUI creada, preparación real, inicio verificado por HTTP, botones visibles, detención con puerto liberado y MySQL conservado |
| Contraseña y navegación | Mostrar/ocultar contraseña, menú móvil y cierre por Escape comprobados |
| Estados adicionales | Vistas vacías, búsqueda sin coincidencias, resultados sin votos y empate comprobados en PHPUnit |

Las pruebas visuales usan `votaedu_ui_test`. La prueba concurrente usa `votaedu_concurrency_test`. La base principal es `votaedu`. No se usaron borrados de bases ni reinicios destructivos para la validación.

Se corrigió el encadenamiento `constrained()->index()` en dos migraciones, que producía claves foráneas repetidas al ejecutarse en MySQL. Los índices requeridos quedan cubiertos por las claves foráneas y el índice único compuesto. Se verificó su creación real.

## Capturas auténticas

- [Ingreso en escritorio](capturas/01-ingreso-escritorio.png) y [celular](capturas/02-ingreso-celular.png).
- [Administración en escritorio](capturas/03-administracion-escritorio.png) y [celular](capturas/04-administracion-celular.png).
- [Gestión de estudiantes](capturas/05-estudiantes-escritorio.png) y [lista en celular](capturas/14-estudiantes-celular.png).
- [Creación de votación](capturas/06-crear-votacion.png) y [opciones del borrador](capturas/07-borrador-opciones.png).
- [Portal estudiantil](capturas/08-estudiante-escritorio.png) y [celular](capturas/09-estudiante-celular.png).
- [Boleta en celular](capturas/10-boleta-celular.png) y [confirmación](capturas/11-confirmacion-voto.png).
- [Resultados en escritorio](capturas/12-resultados-escritorio.png) y [celular](capturas/13-resultados-celular.png).
- [Asistente Python](capturas/15-asistente-python.png).

Reportes: [interfaz](verificacion-ui.json), [asistente](verificacion-launcher.json) y [concurrencia](verificacion-concurrencia.json).

## Límites de la comprobación

La concurrencia se verificó contra el controlador real y MySQL con procesos PHP independientes; no mediante un servidor HTTP con múltiples trabajadores. La prueba de GUI cubre este equipo con PHP y Composer disponibles y MySQL iniciado. La instalación inicial de dependencias en un equipo sin `vendor` y el inicio automático de un MySQL detenido son rutas implementadas, pero no se simularon desinstalando dependencias ni deteniendo el servidor compartido.

Las capturas contienen únicamente datos ficticios. No certifican seguridad electoral ni anonimato criptográfico. Para repetir las comprobaciones, sigue los comandos del README.
