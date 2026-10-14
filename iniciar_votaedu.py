"""Asistente local de VotaEdu. Python 3.10+ con Tkinter; sin paquetes externos."""
from __future__ import annotations

import argparse
import json
import os
from pathlib import Path
import queue
import re
import shutil
import socket
import subprocess
import threading
import time
import tkinter as tk
from tkinter import messagebox, ttk
import urllib.request
import webbrowser

ROOT = Path(__file__).resolve().parent
BLUE, NAVY, BG, WHITE, MUTED, LINE = "#245ce0", "#172844", "#f5f7fb", "#ffffff", "#6d7890", "#e5eaf2"
FLAGS = subprocess.CREATE_NO_WINDOW if os.name == "nt" else 0


def read_env() -> dict[str, str]:
    result = {}
    path = ROOT / ".env"
    if not path.exists():
        path = ROOT / ".env.example"
    for line in path.read_text(encoding="utf-8-sig").splitlines():
        match = re.match(r"^([A-Z_]+)=(.*)$", line)
        if match:
            value = match[2].strip()
            if value.startswith('"') and value.endswith('"'):
                value = re.sub(r'\\([\\"$])', r'\1', value[1:-1])
            elif value.startswith("'") and value.endswith("'"):
                value = value[1:-1]
            result[match[1]] = value
    return result


def save_env(values: dict[str, str]) -> None:
    path = ROOT / ".env"
    source = path if path.exists() else ROOT / ".env.example"
    content = source.read_text(encoding="utf-8-sig")
    for key, value in values.items():
        if any(char in value for char in "\r\n\x00"):
            raise ValueError("Los campos deben contener una sola línea de texto.")
        escaped = value.replace("\\", "\\\\").replace('"', '\\"').replace("$", "\\$")
        replacement = f'{key}="{escaped}"'
        pattern = rf"(?m)^(?:#\s*)?{key}=.*$"
        if re.search(pattern, content):
            content = re.sub(pattern, lambda _: replacement, content)
        else:
            content += "\n" + replacement + "\n"
    temporary = path.with_suffix(".tmp")
    temporary.write_text(content, encoding="utf-8")
    temporary.replace(path)


def find_php() -> str:
    candidates = [shutil.which("php"), r"C:\Dev\php\php.exe", r"C:\xampp\php\php.exe"]
    for candidate in candidates:
        if candidate and Path(candidate).is_file():
            check = subprocess.run([candidate, "-r", "echo json_encode(['version'=>PHP_VERSION_ID,'mysql'=>extension_loaded('pdo_mysql')]);"], capture_output=True, text=True, creationflags=FLAGS)
            try:
                info = json.loads(check.stdout)
                if info["version"] >= 80300 and info["mysql"]:
                    return candidate
            except (ValueError, KeyError):
                continue
    raise RuntimeError("Necesitas PHP 8.3 o superior con pdo_mysql. Agrega PHP al PATH y vuelve a intentar.")


def port_open(host: str, port: int) -> bool:
    try:
        with socket.create_connection((host, port), timeout=1):
            return True
    except OSError:
        return False


class Launcher:
    def __init__(self, window: tk.Tk):
        self.window = window
        self.events: queue.Queue = queue.Queue()
        self.server: subprocess.Popen | None = None
        self.mysql: subprocess.Popen | None = None
        self.busy = False
        self.was_running = False
        self.url = "http://127.0.0.1:8000"
        window.title("VotaEdu · Asistente de inicio")
        window.geometry("1040x800")
        window.minsize(980, 800)
        window.configure(bg=BG)
        window.protocol("WM_DELETE_WINDOW", self.close)
        self.fields: dict[str, tk.StringVar] = {}
        self.inputs = []
        self.status = tk.StringVar(value="Todo comienza con una buena elección.")
        self.demo = tk.BooleanVar(value=True)
        self.show_password = tk.BooleanVar(value=False)
        self.build()
        window.after(100, self.poll)

    def label(self, parent, text, size=11, color=NAVY, bold=False, **kwargs):
        return tk.Label(parent, text=text, bg=parent.cget("bg"), fg=color, font=("Segoe UI", size, "bold" if bold else "normal"), **kwargs)

    def button(self, parent, text, command, primary=False):
        return tk.Button(parent, text=text, command=command, bg=BLUE if primary else "#edf2fb", fg=WHITE if primary else NAVY, activebackground="#1948b8" if primary else "#dfe8fa", activeforeground=WHITE if primary else NAVY, font=("Segoe UI", 10, "bold"), relief="flat", borderwidth=0, cursor="hand2", padx=17, pady=11, disabledforeground="#9aa7bb")

    def build(self):
        style = ttk.Style()
        style.theme_use("clam")
        style.configure("Vota.Horizontal.TProgressbar", background=BLUE, troughcolor=LINE, borderwidth=0, lightcolor=BLUE, darkcolor=BLUE)
        side = tk.Frame(self.window, bg=NAVY, width=260)
        side.pack(side="left", fill="y")
        side.pack_propagate(False)
        self.label(side, "VotaEdu", 29, WHITE, True).pack(anchor="w", padx=30, pady=(33, 3))
        self.label(side, "DEMOCRACIA EN TU COLEGIO", 8, "#9bb5e9", True).pack(anchor="w", padx=30)
        self.label(side, "Todo listo\npara dar voz.", 22, WHITE, True, justify="left").pack(anchor="w", padx=30, pady=(66, 16))
        self.label(side, "Un asistente sencillo para\npreparar e iniciar las\nvotaciones de tu colegio.", 11, "#afbfdb", justify="left").pack(anchor="w", padx=30)
        for number, title, description in [("01", "Conecta tu base", "Revisa los datos de MySQL."), ("02", "Prepara la plataforma", "Conserva los datos existentes."), ("03", "Abre y participa", "Todo listo en tu navegador.")]:
            item = tk.Frame(side, bg=NAVY)
            item.pack(fill="x", padx=30, pady=(26, 0))
            self.label(item, number, 10, "#96b6ff", True).pack(anchor="w")
            self.label(item, title, 11, WHITE, True).pack(anchor="w", pady=(3, 0))
            self.label(item, description, 9, "#9aacca").pack(anchor="w", pady=(4, 0))
        self.label(side, "U.E. Maestro Pitágoras II\nLa Guardia · Gestión 2026", 9, "#9aacca", justify="left").pack(side="bottom", anchor="w", padx=30, pady=27)
        body = tk.Frame(self.window, bg=BG)
        body.pack(side="left", fill="both", expand=True, padx=30, pady=26)
        self.label(body, "ASISTENTE DE INICIO LOCAL", 9, BLUE, True).pack(anchor="w")
        self.label(body, "Preparemos tu comunidad.", 22, NAVY, True).pack(anchor="w", pady=(6, 4))
        self.label(body, "Configura una vez. Después, inicia con un clic.", 11, MUTED).pack(anchor="w")
        card = tk.Frame(body, bg=WHITE, highlightbackground=LINE, highlightthickness=1, padx=22, pady=14)
        card.pack(fill="x", pady=(17, 12))
        self.label(card, "Conexión a MySQL", 13, NAVY, True).grid(row=0, column=0, columnspan=2, sticky="w", pady=(0, 12))
        config = read_env()
        definitions = [("DB_HOST", "Servidor", "127.0.0.1"), ("DB_PORT", "Puerto de MySQL", "3306"), ("DB_DATABASE", "Nombre de la base", "votaedu"), ("DB_USERNAME", "Usuario", "root"), ("DB_PASSWORD", "Contraseña de MySQL", ""), ("APP_PORT", "Puerto de la plataforma", "8000")]
        for i, (key, title, default) in enumerate(definitions):
            frame = tk.Frame(card, bg=WHITE)
            frame.grid(row=1 + i // 2, column=i % 2, sticky="ew", padx=(0, 12) if i % 2 == 0 else (12, 0), pady=(0, 8))
            self.label(frame, title, 9, MUTED).pack(anchor="w", pady=(0, 4))
            value = config.get(key, default)
            if key == "APP_PORT":
                from urllib.parse import urlparse
                try:
                    value = str(urlparse(config.get("APP_URL", "")).port or 8000)
                except ValueError:
                    value = "8000"
            variable = tk.StringVar(value=value)
            self.fields[key] = variable
            entry = tk.Entry(frame, textvariable=variable, bg="#f8faff", fg=NAVY, font=("Segoe UI", 10), relief="flat", highlightthickness=1, highlightbackground="#dfe5ef", highlightcolor=BLUE, insertbackground=BLUE, show="•" if key == "DB_PASSWORD" else "")
            entry.pack(fill="x", ipady=6)
            self.inputs.append(entry)
            if key == "DB_PASSWORD":
                self.password_entry = entry
        card.columnconfigure(0, weight=1, uniform="field")
        card.columnconfigure(1, weight=1, uniform="field")
        self.password_toggle = tk.Checkbutton(card, text="Mostrar contraseña", variable=self.show_password, command=lambda: self.password_entry.configure(show="" if self.show_password.get() else "•"), bg=WHITE, fg=MUTED, selectcolor=WHITE, activebackground=WHITE, font=("Segoe UI", 9))
        self.password_toggle.grid(row=4, column=0, sticky="w")
        self.demo_toggle = tk.Checkbutton(body, text="Cargar datos ficticios de demostración si la base está vacía", variable=self.demo, bg=BG, fg=NAVY, selectcolor=WHITE, activebackground=BG, font=("Segoe UI", 10))
        self.demo_toggle.pack(anchor="w")
        self.label(body, "Crea la base y prepara las tablas. Conserva los datos existentes.", 9, MUTED).pack(anchor="w", pady=(4, 12))
        actions = tk.Frame(body, bg=BG)
        actions.pack(fill="x")
        self.prepare_button = self.button(actions, "Preparar e iniciar", lambda: self.start(True), True)
        self.prepare_button.pack(side="left")
        self.start_button = self.button(actions, "Solo iniciar", lambda: self.start(False))
        self.start_button.pack(side="left", padx=10)
        self.stop_button = self.button(actions, "Detener", self.stop)
        self.stop_button.pack(side="right")
        self.stop_button.configure(state="disabled")
        self.progress = ttk.Progressbar(body, style="Vota.Horizontal.TProgressbar", mode="indeterminate")
        self.progress.pack(fill="x", pady=(12, 8))
        self.status_label = tk.Label(body, textvariable=self.status, bg=BG, fg=MUTED, font=("Segoe UI", 10), anchor="w", wraplength=660)
        self.status_label.pack(fill="x")
        log_frame = tk.Frame(body, bg=WHITE, highlightbackground=LINE, highlightthickness=1)
        log_frame.pack(fill="both", expand=True, pady=(12, 10))
        self.log = tk.Text(log_frame, height=3, bg=WHITE, fg="#71819a", font=("Consolas", 9), relief="flat", wrap="word", padx=12, pady=8, state="disabled")
        self.log.pack(side="left", fill="both", expand=True)
        scrollbar = ttk.Scrollbar(log_frame, command=self.log.yview)
        scrollbar.pack(side="right", fill="y")
        self.log.configure(yscrollcommand=scrollbar.set)
        bottom = tk.Frame(body, bg=BG)
        bottom.pack(fill="x")
        self.browser_button = self.button(bottom, "Abrir plataforma en el navegador", lambda: webbrowser.open(self.url))
        self.browser_button.pack(side="left")
        self.browser_button.configure(state="disabled")
        self.help_button = self.button(bottom, "Ayuda", self.help)
        self.help_button.pack(side="right")

    def help(self):
        messagebox.showinfo("Cómo empezar", "1. Inicia MySQL desde XAMPP si aún no está activo.\n2. Revisa la conexión y pulsa Preparar e iniciar.\n3. El navegador abrirá VotaEdu.\n\nDemostración local:\nAdministrador: admin / admin123\nEstudiante: alejandro.quispe / password123\n\nSolo iniciar usa la base ya preparada. Detener cierra únicamente el servidor Laravel iniciado por este asistente. MySQL permanece activo.", parent=self.window)

    def start(self, prepare: bool):
        if self.busy or (self.server and self.server.poll() is None):
            return
        values = {key: variable.get().strip() if key != "DB_PASSWORD" else variable.get() for key, variable in self.fields.items()}
        try:
            if not re.fullmatch(r"[A-Za-z0-9_]+", values["DB_DATABASE"]):
                raise ValueError("Usa letras, números y guion bajo en el nombre de la base.")
            if not values["DB_HOST"] or not values["DB_USERNAME"]:
                raise ValueError("Completa el servidor y el usuario de MySQL.")
            for field in ("DB_PORT", "APP_PORT"):
                if not values[field].isdigit() or not 1 <= int(values[field]) <= 65535:
                    raise ValueError("Los puertos deben ser números entre 1 y 65535.")
            if any(any(c in value for c in "\r\n\x00") for value in values.values()):
                raise ValueError("Los campos deben contener una sola línea de texto.")
        except ValueError as error:
            messagebox.showerror("Revisa la configuración", str(error), parent=self.window)
            return
        self.busy = True
        self.lock(True)
        self.progress.configure(mode="indeterminate", value=0)
        self.progress.start(12)
        self.status.set("Comprobando el entorno…")
        threading.Thread(target=self.worker, args=(prepare, values, self.demo.get()), daemon=True).start()

    def lock(self, locked):
        state = "disabled" if locked else "normal"
        for control in [self.prepare_button, self.start_button, self.demo_toggle, self.password_toggle, *self.inputs]:
            control.configure(state=state)

    def run_command(self, args: list[str], title: str):
        self.events.put(("status", title))
        # No se incluyen credenciales en los argumentos ni en el registro de actividad.
        if os.name == "nt" and Path(args[0]).suffix.lower() in (".bat", ".cmd"):
            args = [os.environ.get("COMSPEC", "cmd.exe"), "/d", "/s", "/c", subprocess.list2cmdline(args)]
        environment = os.environ.copy()
        for key in tuple(environment):
            if key.startswith("DB_") or key in ("APP_ENV", "APP_URL", "APP_KEY", "APP_NAME"):
                environment.pop(key, None)
        result = subprocess.run(args, cwd=ROOT, env=environment, capture_output=True, text=True, encoding="utf-8", errors="replace", creationflags=FLAGS, timeout=600)
        output = re.sub(r"\x1b\[[0-9;]*[A-Za-z]", "", result.stdout + result.stderr)
        self.events.put(("log", output.strip()))
        if result.returncode:
            raise RuntimeError(f"No se completó: {title} Consulta el detalle de actividad.")

    def worker(self, prepare: bool, values: dict[str, str], demo: bool):
        try:
            php = find_php()
            app_port = int(values.pop("APP_PORT"))
            if port_open("127.0.0.1", app_port):
                raise RuntimeError(f"El puerto {app_port} ya está ocupado. Usa otro puerto o detén la plataforma que está abierta.")
            self.url = f"http://127.0.0.1:{app_port}"
            save_env({**values, "DB_CONNECTION": "mysql", "APP_NAME": "VotaEdu", "APP_ENV": "local", "APP_URL": self.url, "APP_LOCALE": "es", "APP_FALLBACK_LOCALE": "es", "SESSION_DRIVER": "file", "CACHE_STORE": "file", "QUEUE_CONNECTION": "sync"})
            if not (ROOT / "vendor/autoload.php").exists():
                composer = shutil.which("composer")
                if not composer:
                    raise RuntimeError("Instala Composer y agrégalo al PATH para preparar las dependencias de Laravel.")
                self.run_command([composer, "install", "--no-interaction", "--prefer-dist"], "Instalando dependencias de Laravel…")
            self.run_command([php, "artisan", "config:clear"], "Aplicando tu configuración…")
            if not read_env().get("APP_KEY"):
                self.run_command([php, "artisan", "key:generate", "--force"], "Preparando la clave de la aplicación…")
            if not port_open(values["DB_HOST"], int(values["DB_PORT"])):
                self.start_mysql(values)
            if prepare:
                command = [php, "artisan", "votaedu:prepare"]
                if demo:
                    command.append("--demo")
                self.run_command(command, "Preparando MySQL y las tablas…")
            else:
                self.run_command([php, "artisan", "migrate:status"], "Comprobando la base existente…")
            self.run_command([php, "artisan", "view:clear"], "Preparando las pantallas…")
            self.events.put(("status", "Iniciando VotaEdu…"))
            server_log = ROOT / "storage/logs/launcher-server.log"
            server_log.parent.mkdir(parents=True, exist_ok=True)
            with server_log.open("a", encoding="utf-8") as handle:
                self.server = subprocess.Popen([php, "artisan", "serve", "--host=127.0.0.1", f"--port={app_port}", "--tries=1", "--no-reload"], cwd=ROOT, stdout=handle, stderr=handle, creationflags=FLAGS)
            for _ in range(40):
                if self.server.poll() is not None:
                    raise RuntimeError("Laravel no pudo iniciar. Consulta storage/logs/launcher-server.log.")
                try:
                    with urllib.request.urlopen(self.url + "/login", timeout=2) as response:
                        if response.status == 200:
                            self.events.put(("ready", self.url))
                            return
                except OSError:
                    time.sleep(.25)
            raise RuntimeError("Laravel no respondió a tiempo. Revisa storage/logs/laravel.log y vuelve a intentar.")
        except Exception as error:
            self.terminate_server()
            self.events.put(("error", str(error)))

    def start_mysql(self, values):
        executable = Path(r"C:\xampp\mysql\bin\mysqld.exe")
        config = Path(r"C:\xampp\mysql\bin\my.ini")
        if values["DB_HOST"] not in ("127.0.0.1", "localhost") or values["DB_PORT"] != "3306" or not executable.exists() or not config.exists():
            raise RuntimeError("No hay conexión con MySQL. Inícialo desde XAMPP o revisa el servidor y el puerto.")
        self.events.put(("status", "Iniciando MySQL de XAMPP…"))
        log_path = ROOT / "storage/logs/launcher-mysql.log"
        log_path.parent.mkdir(parents=True, exist_ok=True)
        with log_path.open("a", encoding="utf-8") as handle:
            self.mysql = subprocess.Popen([str(executable), f"--defaults-file={config}", "--standalone"], cwd=executable.parent.parent, stdout=handle, stderr=handle, creationflags=FLAGS)
        for _ in range(30):
            if port_open(values["DB_HOST"], int(values["DB_PORT"])):
                self.events.put(("log", "MySQL iniciado. Permanecerá activo al cerrar el asistente."))
                return
            if self.mysql.poll() is not None:
                break
            time.sleep(.3)
        raise RuntimeError("No se pudo iniciar MySQL automáticamente. Revisa XAMPP y storage/logs/launcher-mysql.log.")

    def poll(self):
        while not self.events.empty():
            kind, value = self.events.get_nowait()
            if kind == "status":
                self.status.set(value)
                self.append_log(value)
            elif kind == "log":
                self.append_log(value)
            elif kind in ("ready", "error"):
                self.busy = False
                self.progress.stop()
                if kind == "ready":
                    self.progress.configure(mode="determinate", value=100)
                    self.was_running = True
                    self.status.set(f"Plataforma lista · {value}")
                    self.status_label.configure(fg="#158466")
                    self.browser_button.configure(state="normal")
                    self.stop_button.configure(state="normal")
                    self.append_log("VotaEdu está listo. El navegador abrirá la pantalla de ingreso.")
                    webbrowser.open(value)
                else:
                    self.progress.configure(mode="determinate", value=0)
                    self.lock(False)
                    self.status.set(value)
                    self.status_label.configure(fg="#a23131")
                    self.append_log(value)
                    messagebox.showerror("Necesitamos revisar un paso", value, parent=self.window)
        if self.was_running and self.server and self.server.poll() is not None:
            self.was_running = False
            self.lock(False)
            self.stop_button.configure(state="disabled")
            self.browser_button.configure(state="disabled")
            self.status.set("El servidor se detuvo. Puedes volver a iniciarlo.")
            self.status_label.configure(fg=MUTED)
        self.window.after(100, self.poll)

    def append_log(self, text):
        secret = self.fields["DB_PASSWORD"].get()
        if secret:
            text = text.replace(secret, "[contraseña oculta]")
        self.log.configure(state="normal")
        self.log.insert("end", text + "\n")
        self.log.see("end")
        self.log.configure(state="disabled")

    def terminate_server(self):
        if self.server and self.server.poll() is None:
            if os.name == "nt":
                subprocess.run(["taskkill", "/PID", str(self.server.pid), "/T", "/F"], capture_output=True, creationflags=FLAGS, timeout=10)
            else:
                self.server.terminate()
            self.server.wait(timeout=10)
        self.server = None

    def stop(self):
        if self.busy:
            return
        self.terminate_server()
        self.was_running = False
        self.lock(False)
        self.stop_button.configure(state="disabled")
        self.browser_button.configure(state="disabled")
        self.status.set("Plataforma detenida. Tus datos siguen guardados en MySQL.")
        self.status_label.configure(fg=MUTED)
        self.progress.configure(mode="determinate", value=0)

    def close(self):
        if self.busy:
            messagebox.showinfo("Preparación en curso", "Espera a que termine la preparación para cerrar el asistente.", parent=self.window)
            return
        self.terminate_server()
        self.window.destroy()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--check", action="store_true", help="Comprobar Python, Tkinter y PHP sin abrir una ventana")
    args = parser.parse_args()
    if args.check:
        print(f"Python y Tkinter disponibles. PHP compatible: {find_php()}")
        return
    window = tk.Tk()
    Launcher(window)
    window.mainloop()


if __name__ == "__main__":
    main()
