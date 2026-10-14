<?php

// Ejecutar únicamente desde verify_mysql_concurrency.py, sobre una base separada.
use App\Http\Controllers\VotingController;
use App\Models\Election;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (config('database.default') !== 'mysql' || config('database.connections.mysql.database') !== 'votaedu_concurrency_test') {
    throw new RuntimeException('Esta prueba requiere la base aislada votaedu_concurrency_test.');
}

$mode = $argv[1] ?? '';
if ($mode === 'init') {
    $student = User::create([
        'name' => 'Estudiante ficticio de concurrencia',
        'username' => 'concurrency.'.bin2hex(random_bytes(5)),
        'password' => 'password123',
        'role' => 'student',
        'is_active' => true,
    ]);
    $election = Election::create(['title' => 'Demostración técnica de doble envío', 'status' => 'open', 'opened_at' => now()]);
    $option = $election->options()->create(['name' => 'Propuesta A']);
    $election->options()->create(['name' => 'Propuesta B']);
    echo json_encode(['student' => $student->id, 'election' => $election->id, 'option' => $option->id]);
} elseif ($mode === 'vote') {
    $student = User::findOrFail((int) $argv[2]);
    $election = Election::findOrFail((int) $argv[3]);
    auth()->login($student);
    $session = app('session')->driver();
    $session->start();
    $request = Request::create('/elections/'.$election->id.'/vote', 'POST', ['option_id' => (int) $argv[4]]);
    $request->setLaravelSession($session);
    $target = (float) $argv[5];
    while (microtime(true) < $target) {
        usleep(1000);
    }
    app(VotingController::class)->vote($request, $election);
    echo json_encode(['success' => (bool) $session->get('success'), 'error' => $session->get('error')]);
} elseif ($mode === 'count') {
    $election = Election::findOrFail((int) $argv[2]);
    echo json_encode(['votes' => $election->votes()->count(), 'participations' => $election->participations()->count()]);
} else {
    throw new RuntimeException('Modo no reconocido.');
}
