"""Dos procesos PHP emiten simultáneamente el mismo voto en una base MySQL aislada."""
from pathlib import Path
import json
import os
import subprocess
import sys
import time

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))
from iniciar_votaedu import find_php, FLAGS

env = os.environ.copy()
env.update(DB_CONNECTION="mysql", DB_DATABASE="votaedu_concurrency_test", APP_ENV="local", BCRYPT_ROUNDS="4", CACHE_STORE="file", SESSION_DRIVER="file")
php = find_php()
subprocess.run([php, "artisan", "votaedu:prepare"], cwd=ROOT, env=env, check=True, creationflags=FLAGS)
worker = str(ROOT / "scripts/concurrent_vote_worker.php")


def call(*args):
    result = subprocess.run([php, worker, *map(str, args)], cwd=ROOT, env=env, check=True, capture_output=True, text=True, encoding="utf-8", creationflags=FLAGS)
    return json.loads(result.stdout)


data = call("init")
target = time.time() + 2
processes = [subprocess.Popen([php, worker, "vote", str(data["student"]), str(data["election"]), str(data["option"]), str(target)], cwd=ROOT, env=env, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, encoding="utf-8", creationflags=FLAGS) for _ in range(2)]
responses = []
for process in processes:
    output, error = process.communicate(timeout=30)
    assert process.returncode == 0, error
    responses.append(json.loads(output))
counts = call("count", data["election"])
assert counts == {"votes": 1, "participations": 1}, counts
assert sum(response["success"] for response in responses) == 1, responses
report = {"resultado": "aprobado", "base": "votaedu_concurrency_test", "procesos_simultaneos": 2, "registros": counts, "respuestas": responses}
destination = ROOT / "docs/verificacion-concurrencia.json"
destination.parent.mkdir(parents=True, exist_ok=True)
destination.write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")
print("Concurrencia MySQL aprobada: 2 procesos simultáneos, 1 voto y 1 participación.")
