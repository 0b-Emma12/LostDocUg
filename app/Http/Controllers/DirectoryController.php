<?php

namespace App\Http\Controllers;

use App\Enums\ReportKind;
use App\Enums\ReportStatus;
use App\Enums\StationDropoffStatus;
use App\Models\AuditEvent;
use App\Models\Claim;
use App\Models\MatchCandidate;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DirectoryController extends Controller
{
    public function home(): View
    {
        return view('home');
    }

    public function index(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function show(Request $request, Report $report): View
    {
        $matchCandidate = MatchCandidate::query()
            ->where('found_report_id', $report->id)
            ->whereNotIn('status', ['dismissed', 'expired'])
            ->whereHas('lostReport', fn ($query) => $query->where('user_id', $request->user()->id))
            ->first();

        abort_unless($matchCandidate, 404);

        $claim = $matchCandidate->claims()
            ->where('claimant_id', $request->user()->id)
            ->latest('created_at')
            ->first();

        $reportIsAvailable = in_array($report->status, [
            ReportStatus::Processing,
            ReportStatus::Active,
            ReportStatus::UnderReview,
            ReportStatus::Matched,
        ], true);
        $reportWasCollectedByApprovedClaimant = $report->status === ReportStatus::Recovered
            && $report->station_dropoff_status === StationDropoffStatus::Collected
            && $claim?->status === 'approved';

        abort_unless(
            $report->kind === ReportKind::Found
                && ($reportIsAvailable || $reportWasCollectedByApprovedClaimant),
            404,
        );

        $claimSubmitted = $claim !== null;

        return view('directory.show', compact('report', 'matchCandidate', 'claim', 'claimSubmitted'));
    }

    public function claim(Request $request, Report $report): RedirectResponse
    {
        $user = $request->user();
        abort_unless(
            $report->kind === ReportKind::Found
                && in_array($report->status, [
                    ReportStatus::Processing,
                    ReportStatus::Active,
                    ReportStatus::UnderReview,
                    ReportStatus::Matched,
                ], true),
            404,
        );

        $matchCandidate = MatchCandidate::query()
            ->where('found_report_id', $report->id)
            ->whereNotIn('status', ['dismissed', 'expired'])
            ->whereHas('lostReport', fn ($query) => $query->where('user_id', $user->id))
            ->first();

        abort_unless($matchCandidate, 404);

        if ($matchCandidate->claims()->where('claimant_id', $user->id)->exists()) {
            return redirect()
                ->route('directory.show', $report)
                ->with('success', 'Your claim has already been submitted and is awaiting human review.');
        }

        $validated = $request->validate([
            'claimant_statement' => ['required', 'string', 'min:15', 'max:1500'],
        ]);

        $claim = Claim::create([
            'match_candidate_id' => $matchCandidate->id,
            'claimant_id' => $user->id,
            'report_id' => $report->id,
            'status' => 'pending',
            'claimant_statement' => $validated['claimant_statement'],
        ]);

        AuditEvent::log('claim.submitted', $claim, [
            'report_reference' => $report->public_reference,
            'claimant_id' => $user->id,
            'match_candidate_id' => $matchCandidate?->id,
        ]);

        return redirect()
            ->route('directory.show', $report)
            ->with('success', 'Your ownership claim for document #'.$report->public_reference.' has been submitted. An authorized reviewer will verify your credentials.');
    }
}
