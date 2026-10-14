<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\Option;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoterParticipation;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class VotingController extends Controller
{
    /**
     * Show the voting ballot for an open election.
     */
    public function show(Election $election): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $election->isOpen()) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Esta votación no está disponible para emitir votos.');
        }

        if (! $election->isWithinScheduledTime()) {
            return redirect()->route('student.dashboard')
                ->with('error', 'La votación no se encuentra dentro del horario previsto.');
        }

        if (! $election->isUserEligible($user)) {
            return redirect()->route('student.dashboard')
                ->with('error', 'No te encuentras registrado en el padrón electoral habilitado para esta votación.');
        }

        if ($election->hasStudentVoted($user)) {
            return redirect()->route('student.dashboard')
                ->with('info', 'Ya has participado en esta votación. Tu voto ya se encuentra registrado.');
        }

        $election->load('options');

        return view('voting.show', compact('election'));
    }

    /**
     * Securely register the vote and voter participation in a single atomic transaction.
     */
    public function vote(Request $request, Election $election): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // 1. Role and active status check
        if (! $user || ! $user->isStudent()) {
            abort(403, 'Solo los estudiantes habilitados pueden emitir votos.');
        }

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['username' => 'Tu cuenta ha sido desactivada.']);
        }

        // 2. Election status, scheduling and eligibility checks
        if (! $election->isOpen()) {
            return redirect()->route('student.dashboard')
                ->with('error', 'La votación no está abierta. No se admiten votos.');
        }

        if (! $election->isWithinScheduledTime()) {
            return redirect()->route('student.dashboard')
                ->with('error', 'La votación está fuera del rango de horario programado.');
        }

        if (! $election->isUserEligible($user)) {
            return redirect()->route('student.dashboard')
                ->with('error', 'No perteneces al padrón electoral de esta votación.');
        }

        // 3. Option validation: must belong to this specific election
        $validated = $request->validate([
            'option_id' => [
                'required',
                'integer',
                Rule::exists('options', 'id')->where(function ($query) use ($election) {
                    $query->where('election_id', $election->id);
                }),
            ],
        ], [
            'option_id.required' => 'Debes seleccionar una opción para poder votar.',
            'option_id.exists' => 'La opción seleccionada no pertenece a esta votación.',
        ]);

        try {
            DB::transaction(function () use ($user, $election, $validated) {
                // Lock the user record for update to prevent concurrent duplicate votes
                User::where('id', $user->id)->lockForUpdate()->first();

                // Re-verify election is still open inside transaction
                $currentElection = Election::where('id', $election->id)
                    ->lockForUpdate()
                    ->first();

                if (! $currentElection || ! $currentElection->isOpen()) {
                    throw new RuntimeException('La votación ya no se encuentra abierta.');
                }

                // Check if already voted
                $alreadyParticipated = VoterParticipation::where('election_id', $election->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if ($alreadyParticipated) {
                    throw new RuntimeException('Ya registraste tu voto en esta votación.');
                }

                // Register the anonymous vote (NO user_id stored)
                Vote::create([
                    'election_id' => $election->id,
                    'option_id' => $validated['option_id'],
                ]);

                // Register participation (links student and election, protected by UNIQUE key)
                VoterParticipation::create([
                    'election_id' => $election->id,
                    'user_id' => $user->id,
                ]);
            });

            return redirect()->route('student.dashboard')
                ->with('success', '¡Tu voto se registró correctamente! Gracias por participar.');
        } catch (UniqueConstraintViolationException $e) {
            return redirect()->route('student.dashboard')
                ->with('warning', 'Ya has participado en esta votación. No se permiten votos múltiples.');
        } catch (Throwable $e) {
            return redirect()->route('student.dashboard')
                ->with('error', $e->getMessage() ?: 'Ocurrió un error al registrar tu voto. Intenta nuevamente.');
        }
    }
}
