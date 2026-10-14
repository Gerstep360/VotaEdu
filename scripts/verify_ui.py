"""Verificación real con Edge/Playwright. Usar SOLO con votaedu_ui_test en :8001."""
from pathlib import Path
import json
import re
import time
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "docs" / "capturas"
OUT.mkdir(parents=True, exist_ok=True)
BASE = "http://127.0.0.1:8001"
report = []


with sync_playwright() as p:
    browser = p.chromium.launch(channel="msedge", headless=True)
    context = browser.new_context(viewport={"width": 1440, "height": 1000}, reduced_motion="reduce")
    page = context.new_page()
    errors = []
    page.on("pageerror", lambda error: errors.append(str(error)))

    def visit(path):
        response = page.goto(BASE + path)
        assert response.status == 200, (path, response.status)
        assert not page.locator("text=SQLSTATE").count(), path

    def capture(name):
        page.screenshot(path=str(OUT / f"{name}.png"), full_page=True)
        dimensions = page.evaluate("({width: innerWidth, scroll: document.documentElement.scrollWidth})")
        assert dimensions["scroll"] <= dimensions["width"], (name, dimensions)
        report.append({"pantalla": name, "viewport": page.viewport_size, "overflow": False})

    def login(username, password):
        visit("/login")
        page.get_by_label("Nombre de usuario").fill(username)
        page.get_by_label("Contraseña", exact=True).fill(password)
        page.get_by_role("button", name="Iniciar sesión").click()
        page.wait_for_url(re.compile(r"/(admin|student)$"))

    def confirm(name):
        page.get_by_role("dialog").get_by_role("button", name=name, exact=True).click()
        page.wait_for_load_state("networkidle")

    def logout():
        page.get_by_role("button", name="Cerrar sesión", exact=True).click()
        page.wait_for_url("**/login")

    visit("/login")
    capture("01-ingreso-escritorio")
    page.get_by_label("Contraseña", exact=True).fill("prueba")
    page.get_by_role("button", name="Mostrar contraseña").click()
    assert page.locator("#password").get_attribute("type") == "text"
    page.get_by_role("button", name="Ocultar contraseña").click()
    page.set_viewport_size({"width": 390, "height": 844})
    capture("02-ingreso-celular")
    page.set_viewport_size({"width": 1440, "height": 1000})
    login("admin", "admin123")
    capture("03-administracion-escritorio")
    page.set_viewport_size({"width": 390, "height": 844})
    capture("04-administracion-celular")
    page.get_by_role("button", name="Mostrar navegación").click()
    assert page.locator("#navigation").evaluate("el => el.getBoundingClientRect().x") == 0
    page.keyboard.press("Escape")
    assert page.get_by_role("button", name="Mostrar navegación").get_attribute("aria-expanded") == "false"
    page.set_viewport_size({"width": 1440, "height": 1000})
    visit("/admin/students")
    capture("05-estudiantes-escritorio")
    page.get_by_label("Buscar por nombre, usuario o curso").fill("noexiste")
    page.get_by_role("button", name="Buscar", exact=True).click()
    assert page.get_by_text("No encontramos coincidencias").is_visible()
    stamp = str(int(time.time()))
    visit("/admin/students/create")
    page.get_by_label("Nombre completo").fill("Estudiante de prueba visual")
    page.get_by_label("Nombre de usuario").fill("ui.demo." + stamp)
    page.get_by_label("Contraseña inicial").fill("demo1234")
    page.get_by_label("Curso", exact=True).fill("6to de Secundaria")
    page.get_by_label("Paralelo", exact=True).fill("A")
    page.get_by_role("button", name="Registrar estudiante", exact=True).click()
    assert page.get_by_text("Estudiante registrado correctamente.").is_visible()
    student_row = page.get_by_role('row').filter(has_text='ui.demo.' + stamp)
    student_row.get_by_role('link', name='Editar', exact=True).click()
    page.get_by_label('Paralelo', exact=True).fill('B')
    page.get_by_role('button', name='Guardar cambios', exact=True).click()
    student_row = page.get_by_role('row').filter(has_text='ui.demo.' + stamp)
    student_row.get_by_role('button', name='Deshabilitar', exact=True).click()
    confirm('Deshabilitar cuenta')
    student_row = page.get_by_role('row').filter(has_text='ui.demo.' + stamp)
    assert student_row.get_by_text('Deshabilitado', exact=True).is_visible()
    student_row.get_by_role('button', name='Habilitar', exact=True).click()
    confirm('Habilitar cuenta')
    student_row = page.get_by_role('row').filter(has_text='ui.demo.' + stamp)
    student_row.get_by_role('link', name='Editar', exact=True).click()
    page.get_by_label('Nueva contraseña', exact=True).fill('demo5678')
    page.get_by_role('button', name='Restablecer contraseña', exact=True).click()
    confirm('Restablecer contraseña')
    visit("/admin/elections/create")
    capture("06-crear-votacion")
    page.get_by_label("Título de la votación").fill("Prueba visual · elección escolar " + stamp)
    page.get_by_label("Descripción e instrucciones").fill("Datos ficticios para comprobar el recorrido de participación.")
    page.get_by_role("button", name="Crear borrador").click()
    election_path = re.search(r"/admin/elections/\d+", page.url).group(0)
    assert page.get_by_role("button", name="Abrir votación", exact=True).is_disabled()
    for name in ["Propuesta de biblioteca", "Propuesta de laboratorio"]:
        page.get_by_label("Nombre de la opción", exact=True).fill(name)
        page.get_by_label("Descripción (opcional)", exact=True).fill("Una propuesta de ejemplo para nuestra comunidad.")
        page.get_by_role("button", name="Agregar opción", exact=True).click()
        page.wait_for_load_state("networkidle")
    first_option = page.locator('.option-admin').first
    first_option.locator('summary').click()
    first_option.get_by_label('Descripción', exact=True).fill('Una biblioteca con más recursos para aprender juntos.')
    first_option.get_by_role('button', name='Guardar opción', exact=True).click()
    page.get_by_label('Nombre de la opción', exact=True).fill('Opción temporal de prueba')
    page.get_by_role('button', name='Agregar opción', exact=True).click()
    temporary = page.locator('.option-admin').filter(has_text='Opción temporal de prueba')
    temporary.locator('summary').click()
    temporary.get_by_role('button', name='Eliminar opción', exact=True).click()
    confirm('Eliminar opción')
    assert page.locator('.option-admin').count() == 2
    capture("07-borrador-opciones")
    page.get_by_role("button", name="Abrir votación", exact=True).click()
    assert page.get_by_role("dialog").is_visible()
    confirm("Abrir votación")
    assert page.get_by_text("La votación está abierta", exact=True).is_visible()
    logout()
    login("ui.demo." + stamp, "demo5678")
    capture("08-estudiante-escritorio")
    page.set_viewport_size({"width": 390, "height": 844})
    capture("09-estudiante-celular")
    election_id = election_path.rsplit("/", 1)[1]
    visit(f"/elections/{election_id}/vote")
    assert page.get_by_role("button", name="Continuar", exact=True).is_disabled()
    page.get_by_label(re.compile("Propuesta de biblioteca")).check()
    capture("10-boleta-celular")
    page.get_by_role("button", name="Continuar", exact=True).click()
    assert page.get_by_role("dialog").is_visible()
    assert page.locator("#confirm-selection").inner_text() == "Propuesta de biblioteca"
    capture("11-confirmacion-voto")
    confirm("Volver")
    assert page.get_by_role("dialog").is_hidden()
    page.get_by_role("button", name="Continuar", exact=True).click()
    confirm("Confirmar mi voto")
    assert page.get_by_text("¡Tu voto se registró correctamente! Gracias por participar.").is_visible()
    assert page.get_by_text("Ya participaste").is_visible()
    visit(f"/elections/{election_id}/vote")
    assert page.url.endswith("/student")
    page.set_viewport_size({"width": 1440, "height": 1000})
    logout()
    login("admin", "admin123")
    visit(election_path)
    page.get_by_role("button", name="Cerrar votación", exact=True).click()
    confirm("Cerrar y publicar")
    page.get_by_role("link", name="Ver resultados", exact=True).click()
    assert page.get_by_text("100,00 %").count() >= 1
    capture("12-resultados-escritorio")
    page.set_viewport_size({"width": 390, "height": 844})
    capture("13-resultados-celular")
    visit("/admin/students")
    capture("14-estudiantes-celular")
    visit('/admin/elections?status=closed')
    assert page.locator('.state-open').count() == 0
    visit('/admin/elections/create')
    page.get_by_label('Título de la votación').fill('Borrador temporal para eliminar')
    page.get_by_role('button', name='Crear borrador').click()
    page.get_by_role('button', name='Eliminar borrador', exact=True).click()
    confirm('Eliminar borrador')
    assert page.get_by_text('Borrador de votación eliminado correctamente.').is_visible()
    assert not errors, errors
    browser.close()

(OUT.parent / "verificacion-ui.json").write_text(json.dumps({"resultado": "aprobado", "pantallas": report, "errores_javascript": errors, "recorrido": "registro y edición de estudiante, deshabilitar/habilitar, restablecer contraseña, borrador, agregar/editar/eliminar opciones, apertura, voto con cancelación y confirmación, bloqueo de repetición, cierre, resultados, filtros y eliminación de borrador"}, ensure_ascii=False, indent=2), encoding="utf-8")
print(f"UI aprobada: {len(report)} capturas, escritorio y celular, sin desbordamiento ni errores JavaScript.")
