<?php

namespace App\Http\Controllers;

use App\Models\AdministrativeAuditLog;
use App\Models\Election;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ResultController extends Controller
{
    public function show(Election $election): View|RedirectResponse
    {
        if (! $election->isClosed()) {
            $user = Auth::user();
            $route = $user->canManageSystem() ? 'admin.elections.show'
                : ($user->isTeacher() ? 'teacher.dashboard' : 'student.dashboard');

            return redirect()->route($route, $user->canManageSystem() ? [$election] : [])
                ->with('error', 'Los resultados solo están disponibles una vez que la votación ha sido cerrada.');
        }

        Gate::authorize('view', $election);

        return view('results.show', $this->reportData($election));
    }

    public function acta(Election $election): View
    {
        Gate::authorize('export', $election);
        AdministrativeAuditLog::record('election.acta_view', 'Election', $election->id);

        return view('results.acta', $this->reportData($election));
    }

    public function download(Election $election): Response
    {
        Gate::authorize('export', $election);
        $data = array_merge($this->reportData($election), ['pdf' => true]);
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('defaultMediaType', 'print');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);
        $options->set('chroot', public_path());
        $options->set('tempDir', storage_path('framework/cache'));
        $options->set('fontCache', storage_path('framework/cache'));
        $document = new Dompdf($options);
        $document->loadHtml(view('results.acta', $data)->render(), 'UTF-8');
        $document->setPaper('A4', 'portrait');
        $document->render();
        $font = $document->getFontMetrics()->getFont('DejaVu Sans');
        $document->getCanvas()->page_script(function ($pageNumber, $pageCount, $canvas): void {
            $canvas->line(44, 800, 551, 800, [0.9, 0.92, 0.95], 0.5);
        });
        $document->getCanvas()->page_text(44, 813, 'VotaEdu · Emitido: '.$data['issuedAt']->format('d/m/Y H:i').' (Bolivia)', $font, 6.5, [0.43, 0.47, 0.56]);
        $document->getCanvas()->page_text(441, 813, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 7, [0.43, 0.47, 0.56]);
        AdministrativeAuditLog::record('election.acta_download', 'Election', $election->id);

        return response($document->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="acta-votaedu-'.$election->id.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * La página, la vista previa y el PDF comparten las mismas cifras.
     */
    private function reportData(Election $election): array
    {
        $election->load([
            'options' => fn ($query) => $query->withCount('votes'),
            'courses', 'academicYear', 'creator',
        ]);
        $totalVotes = $election->votes()->count();
        $totalParticipations = $election->participations()->count();
        $totalEligible = $election->eligibleVoters()->count();
        $turnoutPercentage = $totalEligible > 0 ? round($totalParticipations / $totalEligible * 100, 2) : 0.0;
        $optionsResults = $election->options->map(fn ($option) => [
            'id' => $option->id,
            'name' => $option->name,
            'description' => $option->description,
            'photo_path' => $option->photo_path,
            'members' => $option->members,
            'proposals' => $option->proposals,
            'votes' => $option->votes_count,
            'percentage' => $totalVotes > 0 ? round($option->votes_count / $totalVotes * 100, 2) : 0.0,
        ])->sortByDesc('votes')->values();
        $maxVotes = $optionsResults->max('votes') ?? 0;
        $topOptions = $totalVotes > 0 ? $optionsResults->where('votes', $maxVotes) : collect();
        $isTie = $topOptions->count() > 1;
        $tiedOptions = $isTie ? $topOptions->pluck('name')->toArray() : [];
        $winner = $topOptions->count() === 1 ? $topOptions->first() : null;
        $issuedAt = now();

        return compact('election', 'optionsResults', 'totalVotes', 'totalParticipations',
            'totalEligible', 'turnoutPercentage', 'isTie', 'tiedOptions', 'winner', 'maxVotes', 'issuedAt');
    }
}
