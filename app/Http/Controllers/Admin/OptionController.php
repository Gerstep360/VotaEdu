<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\Option;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OptionController extends Controller
{
    /**
     * Store a newly created option for a pending election.
     */
    public function store(Request $request, Election $election): RedirectResponse
    {
        Gate::authorize('update', $election);
        if (! $election->isPending()) {
            return back()->with('error', 'Solo se pueden agregar opciones a votaciones en estado pendiente.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'members' => ['nullable', 'string', 'max:5000'],
            'proposals' => ['nullable', 'string', 'max:10000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name.required' => 'El nombre de la opción (candidato, frente o propuesta) es obligatorio.',
            'name.max' => 'El nombre no puede exceder los 180 caracteres.',
        ]);

        $election->options()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'members' => $validated['members'] ?? null,
            'proposals' => $validated['proposals'] ?? null,
            'photo_path' => $request->hasFile('photo') ? $request->file('photo')->store('candidates', 'public') : null,
        ]);

        return back()->with('success', 'Opción agregada exitosamente.');
    }

    /**
     * Update the specified option in storage (only if election is pending).
     */
    public function update(Request $request, Option $option): RedirectResponse
    {
        if (! $option->election->isPending()) {
            return back()->with('error', 'No se pueden modificar opciones de votaciones abiertas o cerradas.');
        }
        Gate::authorize('update', $option->election);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'members' => ['nullable', 'string', 'max:5000'],
            'proposals' => ['nullable', 'string', 'max:10000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name.required' => 'El nombre de la opción es obligatorio.',
            'name.max' => 'El nombre no puede exceder los 180 caracteres.',
        ]);

        $option->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'members' => $validated['members'] ?? $option->members,
            'proposals' => $validated['proposals'] ?? $option->proposals,
            'photo_path' => $request->hasFile('photo') ? $request->file('photo')->store('candidates', 'public') : $option->photo_path,
        ]);

        return back()->with('success', 'Opción actualizada correctamente.');
    }

    /**
     * Remove the specified option from storage (only if election is pending).
     */
    public function destroy(Option $option): RedirectResponse
    {
        if (! $option->election->isPending()) {
            return back()->with('error', 'No se pueden eliminar opciones de votaciones abiertas o cerradas.');
        }
        Gate::authorize('update', $option->election);

        $option->delete();

        return back()->with('success', 'Opción eliminada correctamente.');
    }
}
