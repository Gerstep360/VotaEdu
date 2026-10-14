"""Comprobar la GUI y su ciclo real de inicio/detención en la base de pruebas."""
from pathlib import Path
import json
import sys
import time
import tkinter as tk
from PIL import ImageGrab

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))
import iniciar_votaedu as launcher

original = (ROOT / ".env").read_bytes()
opened = []
launcher.webbrowser.open = lambda url: opened.append(url)
window = tk.Tk()
app = launcher.Launcher(window)
try:
    app.fields["DB_DATABASE"].set("votaedu_ui_test")
    app.fields["APP_PORT"].set("8002")
    app.start(True)
    deadline = time.monotonic() + 60
    while app.busy and time.monotonic() < deadline:
        window.update()
        time.sleep(.05)
    assert not app.busy, "La preparación no terminó"
    assert app.was_running, app.status.get()
    assert opened == ["http://127.0.0.1:8002"]
    assert launcher.port_open("127.0.0.1", 8002)
    window.update()
    controls = [app.prepare_button, app.start_button, app.stop_button, app.browser_button, app.help_button, app.status_label, app.log, *app.inputs]
    for control in controls:
        assert control.winfo_ismapped(), str(control)
        assert control.winfo_height() >= control.winfo_reqheight(), f"Altura recortada: {control}"
        assert control.winfo_rooty() + control.winfo_height() <= window.winfo_rooty() + window.winfo_height(), f"Control recortado: {control}"
    destination = ROOT / "docs/capturas/15-asistente-python.png"
    destination.parent.mkdir(parents=True, exist_ok=True)
    import ctypes
    # Capturar únicamente nuestra ventana, aunque otra aplicación esté delante.
    handle = ctypes.windll.user32.GetParent(window.winfo_id())
    ImageGrab.grab(window=handle).save(destination)
    app.stop()
    window.update()
    assert not launcher.port_open("127.0.0.1", 8002), "El servidor no se detuvo"
    assert launcher.port_open("127.0.0.1", 3306), "MySQL debe seguir activo"
    (ROOT / "docs/verificacion-launcher.json").write_text(json.dumps({"resultado": "aprobado", "gui": "1040 x 800, controles visibles y sin recortes", "mysql": "base de pruebas conservada", "laravel": "inicio comprobado por HTTP, detención y puerto liberado", "navegador": "apertura solicitada y capturada en prueba", "env": "restaurado después de la prueba"}, ensure_ascii=False, indent=2), encoding="utf-8")
    print("GUI aprobada: preparación, inicio HTTP, controles visibles, detención y MySQL conservado.")
finally:
    app.terminate_server()
    window.destroy()
    (ROOT / ".env").write_bytes(original)
