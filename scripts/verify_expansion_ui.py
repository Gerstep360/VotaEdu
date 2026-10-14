"""Verifica la ampliación en el servidor de pruebas de :8001, sin tocar la base principal."""
from pathlib import Path
import json
import re
import time
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "docs/capturas-ampliacion"
OUT.mkdir(parents=True, exist_ok=True)
BASE = "http://127.0.0.1:8001"
report = []

with sync_playwright() as p:
    browser = p.chromium.launch(channel="msedge", headless=True)
    context = browser.new_context(viewport={"width": 1440, "height": 1000}, reduced_motion="reduce", accept_downloads=True)
    page = context.new_page()
    errors = []
    page.on("pageerror", lambda error: errors.append(str(error)))
    page.set_default_timeout(10000)

    def visit(path):
        response = page.goto(BASE + path)
        assert response.status == 200, (path, response.status)

    def capture(name):
        assert page.evaluate("document.documentElement.scrollWidth <= innerWidth"), name
        page.screenshot(path=str(OUT / (name + ".png")), full_page=True)
        report.append({"pantalla": name, "viewport": page.viewport_size, "resultado": "aprobado"})

    def login(username, password, target):
        visit("/login")
        page.get_by_label("Carnet de identidad o usuario").fill(username)
        page.get_by_label("Contraseña", exact=True).fill(password)
        page.get_by_role("button", name="Iniciar sesión", exact=True).click()
        page.wait_for_url("**/" + target)

    def logout():
        page.get_by_role("button", name="Cerrar sesión", exact=True).click()
        page.wait_for_url("**/login")

    def confirm(name):
        page.get_by_role("dialog").get_by_role("button", name=name, exact=True).click()
        page.wait_for_load_state("networkidle")

    def change_password(password):
        page.get_by_label("Nueva contraseña", exact=True).fill(password)
        page.get_by_label("Repite la nueva contraseña", exact=True).fill(password)
        page.get_by_role("button", name="Guardar y continuar", exact=True).click()
        page.wait_for_load_state("networkidle")

    login("10000001", "admin123", "admin")
    capture("01-panel-administrativo")
    for path, name in [("/admin/academic-years", "02-gestiones"), ("/admin/courses", "03-cursos"), ("/admin/teachers", "04-docentes"), ("/admin/students", "05-estudiantes-ci-completo"), ("/admin/elections", "06-historial"), ("/profile", "07-perfil-ci-completo")]:
        visit(path)
        capture(name)
    assert page.get_by_text("10000001", exact=True).is_visible()
    stamp = str(int(time.time()))
    student_ci = "9900" + stamp[-5:]
    teacher_ci = "8800" + stamp[-5:]
    visit("/admin/students/create")
    page.get_by_label("Nombres", exact=False).fill("María de prueba")
    page.get_by_label("Apellido paterno", exact=False).fill("Rojas")
    page.get_by_label("Apellido materno", exact=False).fill("Cruz")
    page.get_by_label("Carnet de identidad", exact=False).fill(student_ci)
    course_value = page.locator("#registered-course option").filter(has_text="6to de Secundaria A").first.get_attribute("value")
    page.locator("#registered-course").select_option(course_value)
    assert page.locator("#parallel").input_value() == "A"
    capture("08-registro-estudiante")
    page.get_by_role("button", name="Registrar estudiante", exact=True).click()
    assert page.get_by_text("Estudiante registrado correctamente.").is_visible()
    visit("/admin/teachers/create")
    page.get_by_label("Nombre completo", exact=False).fill("Docente de prueba visual")
    page.get_by_label("Carnet de identidad", exact=False).fill(teacher_ci)
    assert page.locator("#username").input_value() == teacher_ci
    page.get_by_label("Contraseña inicial", exact=False).fill("DocenteInicial123")
    page.get_by_label("6to de Secundaria A", exact=True).check()
    capture("09-registro-docente-asignaciones")
    page.get_by_role("button", name="Registrar docente", exact=True).click()
    assert page.get_by_text("Docente registrado exitosamente.").is_visible()
    visit("/admin/elections/create")
    page.get_by_label("Título de la votación", exact=False).fill("Representación estudiantil · prueba visual " + stamp)
    page.get_by_label("Tipo de votación", exact=True).select_option("eleccion_estudiantil")
    page.get_by_label("6to de Secundaria A", exact=True).check()
    capture("10-votacion-tipo-alcance")
    page.get_by_role("button", name="Crear borrador", exact=True).click()
    election_id = re.search(r"/admin/elections/(\d+)", page.url).group(1)
    for name, proposals in [("Frente Aprender Juntos", "Biblioteca, tutorías entre pares y talleres de tecnología."), ("Frente Comunidad Activa", "Actividades culturales y proyectos de cuidado del colegio.")]:
        option_form = page.locator('.add-option')
        option_form.get_by_label("Nombre de la opción", exact=True).fill(name)
        option_form.get_by_label("Descripción (opcional)", exact=True).fill("Una propuesta para escuchar a toda la comunidad.")
        option_form.get_by_label("Integrantes (opcional)", exact=True).fill("Integrantes ficticios de la demostración")
        option_form.get_by_label("Propuestas (opcional)", exact=True).fill(proposals)
        option_form.get_by_role("button", name="Agregar opción", exact=True).click()
    capture("11-candidaturas-y-propuestas")
    page.get_by_role("button", name="Abrir votación", exact=True).click()
    confirm("Abrir votación")
    logout()
    login(student_ci, "Rc." + student_ci, "change-password")
    capture("12-cambio-obligatorio")
    change_password("EstudianteNueva123")
    assert page.url.endswith("/student")
    page.set_viewport_size({"width": 390, "height": 844})
    capture("13-portal-estudiante-celular")
    visit(f"/elections/{election_id}/vote")
    assert page.get_by_text("Biblioteca, tutorías entre pares y talleres de tecnología.", exact=True).is_visible()
    page.get_by_role("radio").first.check()
    capture("14-candidaturas-boleta-celular")
    page.get_by_role("button", name="Continuar", exact=True).click()
    confirm("Confirmar mi voto")
    assert page.get_by_text("Ya participaste", exact=True).is_visible()
    visit("/profile")
    assert page.get_by_text(student_ci, exact=True).is_visible()
    capture("15-perfil-participaciones-celular")
    logout()
    page.set_viewport_size({"width": 1440, "height": 1000})
    login(teacher_ci, "DocenteInicial123", "change-password")
    change_password("DocenteNueva123")
    assert page.url.endswith("/teacher")
    capture("16-panel-docente")
    visit("/teacher/elections/create")
    assigned_value = page.locator("#course_id option").filter(has_text="6to de Secundaria A").first.get_attribute("value")
    page.locator("#course_id").select_option(assigned_value)
    page.get_by_label("Título de la votación", exact=True).fill("Actividad del curso · prueba " + stamp)
    page.get_by_role("button", name="Crear borrador", exact=True).click()
    for name in ["Taller de ciencia", "Jornada cultural"]:
        page.locator('.add-option').get_by_label("Nombre de la opción", exact=True).fill(name)
        page.locator('.add-option').get_by_role("button", name="Agregar opción", exact=True).click()
    page.get_by_role("button", name="Abrir votación", exact=True).click()
    confirm("Abrir votación")
    page.get_by_role("button", name="Cerrar votación", exact=True).click()
    confirm("Cerrar y publicar")
    page.get_by_role("link", name="Ver resultados", exact=True).click()
    assert page.get_by_text("No se registraron votos", exact=True).is_visible()
    logout()
    login("20000002", "director123", "admin")
    assert page.get_by_text("Director", exact=True).is_visible()
    visit(f"/admin/elections/{election_id}")
    page.get_by_role("button", name="Cerrar votación", exact=True).click()
    confirm("Cerrar y publicar")
    page.get_by_role("link", name="Ver resultados", exact=True).click()
    capture("17-resultados-y-descarga")
    with page.expect_download() as download:
        page.get_by_role("link", name="Descargar acta PDF", exact=True).click()
    download.value.save_as(str(OUT / "acta-mysql-demostracion.pdf"))
    assert (OUT / "acta-mysql-demostracion.pdf").read_bytes().startswith(b"%PDF-")
    page.get_by_role("link", name="Ver acta", exact=True).click()
    capture("18-vista-previa-acta")
    page.set_viewport_size({"width": 390, "height": 844})
    capture("19-acta-celular")
    for path, name in [("/admin", "20-panel-celular"), ("/admin/teachers", "21-docentes-celular"), ("/admin/courses", "22-cursos-celular"), ("/admin/academic-years", "23-gestiones-celular"), ("/admin/audit-logs", "24-auditoria-celular")]:
        visit(path)
        capture(name)
    assert not errors, errors
    browser.close()

(OUT.parent / "verificacion-ampliacion-ui.json").write_text(json.dumps({"resultado": "aprobado", "base": "votaedu_expansion_ui_test", "capturas": report, "errores_javascript": errors, "recorrido": "CI completo, registro estudiantil, registro docente, asignación, primer cambio, candidatos y propuestas, padrón, voto, docencia, cierre, resultados, vista previa y descarga PDF real"}, ensure_ascii=False, indent=2), encoding="utf-8")
print(f"Ampliación UI aprobada: {len(report)} pantallas, descarga PDF y cuatro roles.")
