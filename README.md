# VotaEdu

Plataforma de votación escolar para la U.E. Técnico Humanístico Maestro Pitágoras II, La Guardia, Santa Cruz. Proyecto BTH, gestión 2026. Laravel 13, MySQL, Blade, Bootstrap 5 y JavaScript, con interfaz en español.

## Iniciar con el asistente

Desde esta carpeta:

```powershell
python iniciar_votaedu.py
```

También puedes abrir `iniciar_votaedu.py` desde el Explorador si Python está asociado a los archivos `.py`. La GUI usa Tkinter, incluido en la instalación habitual de Python para Windows; no necesita paquetes adicionales.

1. Revisa servidor, puerto, base, usuario y contraseña de MySQL.
2. Pulsa **Preparar e iniciar**. El asistente guarda la conexión en `.env`, comprueba PHP, instala dependencias con Composer si faltan, crea la base si no existe y aplica migraciones.
3. Si la base está vacía y está marcada la demostración, carga cuentas y votaciones ficticias. Si ya contiene usuarios o votaciones, conserva esos datos.
4. Laravel inicia y el navegador abre el ingreso. En los siguientes arranques usa **Solo iniciar**.
5. **Detener** o cerrar el asistente detiene únicamente su servidor Laravel. MySQL permanece activo y conserva los datos.

Si MySQL no está activo, intenta iniciar XAMPP en `C:\xampp`, únicamente para la conexión local del puerto 3306. En otras instalaciones, inicia MySQL desde su panel. Apache no es necesario. Si el puerto de Laravel está ocupado, elige otro en la GUI.

Si ya hay un servidor iniciado desde una terminal, la GUI no lo detiene ni toma control de él. Puedes detenerlo con Ctrl+C en esa terminal o elegir otro puerto para el asistente.

## Requisitos y configuración actual

- PHP **8.3 o superior**, con PDO MySQL y las extensiones requeridas por Laravel.
- Composer si falta la carpeta `vendor`.
- MySQL o un servidor compatible activo.
- Python **3.10 o superior** con Tkinter para el asistente.

El asistente no instala PHP, Python, Composer ni MySQL; informa si falta un requisito. En este equipo se verificó PHP 8.4.12 en `C:\Dev\php` y el servidor de XAMPP.

La conexión local es `127.0.0.1:3306`, base `votaedu`, usuario `root`, contraseña vacía. Ajusta los campos si tu servidor usa otras credenciales. `.env.example` contiene valores locales de ejemplo; `.env` está excluido de Git. Zona horaria: `America/La_Paz`.

Sesiones y caché usan archivos; las tareas son síncronas. Los recursos están en `public/assets`: **no hace falta iniciar npm, Node ni Vite**, y la interfaz no descarga recursos de Internet. Las dependencias PHP deben estar instaladas. Bootstrap 5.3.3 se distribuye localmente bajo MIT, incluida en su cabecera. Los archivos iniciales de Vite permanecen disponibles, pero las vistas usan recursos locales directamente.

## Arranque manual

```powershell
composer install
# Solo si falta .env:
if (!(Test-Path .env)) { Copy-Item .env.example .env }
# Solo si falta APP_KEY:
if (Select-String -LiteralPath .env -Pattern '^APP_KEY=\s*$' -Quiet) { php artisan key:generate }
php artisan config:clear
php artisan votaedu:prepare --demo
php artisan serve --host=127.0.0.1 --port=8000 --no-reload
```

Abre <http://127.0.0.1:8000>. `votaedu:prepare` crea la base y aplica migraciones sin eliminar registros. Omite `--demo` para no cargar ejemplos. El seeder también evita cargar ejemplos sobre una base con usuarios o votaciones.

## Cuentas locales de demostración

Las cuentas siguientes corresponden a una instalación nueva con los seeders actuales. Las cuentas conservadas de una instalación anterior pueden mantener sus credenciales anteriores; preparar la base no las restablece.

| Rol | Usuario | Contraseña |
| --- | --- | --- |
| Administrador | `admin` | `admin123` |
| Director | `director` o CI `20000002` | `director123` |
| Docente | `docente1` o CI `30000003` | `docente123` |
| Estudiante habilitado, con cambio inicial obligatorio | `alejandro.quispe` o CI `90123456` | `Qf.90123456` |
| Estudiante que ya participó en la votación abierta de ejemplo | `carlos.mamani` o CI `78901234` | `Mc.78901234` |
| Estudiante deshabilitado | `nicole.arteaga` o CI `90123458` | `Av.90123458` |

Los 12 estudiantes y las votaciones del seeder son ficticios. Estas cuentas sirven para demostración local. No existe registro público: el administrador crea las cuentas estudiantiles. Sin demostración, el asistente prepara una base vacía; una instalación para uso real requiere provisionar una cuenta administrativa autorizada.

## Funciones y reglas

Ingreso con usuario y contraseña; resumen administrativo; estudiantes con búsqueda, habilitación y restablecimiento de contraseña; borradores y edición de opciones; apertura con al menos dos opciones; cierre confirmado; portal estudiantil; boleta de selección única con confirmación; resultados finales con barras, tabla, porcentajes, opciones sin votos y empate.

La interfaz adapta las tablas administrativas a listas en celular, ofrece foco visible, controles de teclado y respeta el movimiento reducido.

El voto y la participación se guardan en una transacción. Los bloqueos y el índice único `election_id + user_id` impiden una segunda participación. `votes` no contiene `user_id`; la interfaz no muestra elecciones individuales junto a nombres. Esta separación no equivale a anonimato criptográfico frente a alguien con acceso total a la base.

Los resultados permanecen reservados hasta el cierre y las opciones no se modifican después de abrir. Deshabilitar cuentas conserva su historial.

## Verificación

```powershell
php artisan test
php artisan view:cache
python iniciar_votaedu.py --check
python -m py_compile iniciar_votaedu.py
python scripts/verify_mysql_concurrency.py
```

PHPUnit usa SQLite **en memoria**, separada de MySQL. La prueba concurrente usa exclusivamente `votaedu_concurrency_test` y dos procesos PHP simultáneos sobre la misma pareja estudiante/votación.

Para repetir las pruebas visuales, instala las herramientas opcionales de desarrollo y abre un servidor con la base aislada:

```powershell
python -m pip install playwright pillow
$env:DB_DATABASE='votaedu_ui_test'
php artisan votaedu:prepare --demo
php artisan serve --host=127.0.0.1 --port=8001 --no-reload
# En otra terminal sin DB_DATABASE:
python scripts/verify_ui.py
python scripts/verify_launcher.py
```

`verify_ui.py` requiere Microsoft Edge y agrega registros identificados de prueba. `verify_launcher.py` usa el puerto 8002, restaura `.env` al terminar y comprueba preparación, HTTP y detención. No ejecutes varios asistentes que cambien `.env` simultáneamente.

La evidencia está en [docs/VERIFICACION.md](docs/VERIFICACION.md), con capturas auténticas y reportes JSON. Las herramientas de desarrollo no son necesarias para usar la plataforma.

## Ampliación y documentación del sistema

La interfaz incluye docentes/asignaciones, cursos/paralelos, gestiones, auditoría, perfil, cambio obligatorio de contraseña, tipos de votación, alcance por cursos y campos de candidaturas. Los carnets se muestran completos en las pantallas correspondientes.

En una votación cerrada, las cuentas autorizadas encuentran **Ver acta** y **Descargar acta PDF** en resultados. La descarga utiliza Dompdf desde PHP, sin requerir Python, un navegador automatizado ni acceso a Internet. El acta y la vista previa usan una misma plantilla A4 y los mismos datos de resultados.

- [Explicación completa: base, modelos, controladores y flujo](EXPLICACION_SISTEMA_VOTAEDU.md).
- [Pruebas y capturas de la ampliación](docs/VERIFICACION_AMPLIACION.md).
- [Ejemplo ficticio de acta PDF](output/pdf/acta-votaedu-demostracion.pdf).

## Demostración sugerida

1. Ingresa como administrador y registra un estudiante ficticio.
2. Crea una votación, agrega tres opciones y ábrela.
3. Ingresa con la cuenta estudiantil y confirma un voto.
4. Comprueba «Ya participaste» y el bloqueo del segundo intento.
5. Como administrador, cierra la votación y consulta sus resultados.

Antes de modificar una base con datos importantes, respáldala desde **Exportar** en phpMyAdmin, en formato SQL. Guarda el respaldo fuera del directorio público. El asistente no usa `migrate:fresh`, `db:wipe` ni truncados.
