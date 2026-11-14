<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportKind;
use App\Enums\ReportStatus;
use App\Enums\StationDropoffStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\Claim;
use App\Models\MatchCandidate;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $reports = Report::query()->count();
        $lostReports = Report::query()->where('kind', ReportKind::Lost)->count();
        $foundReports = Report::query()->where('kind', ReportKind::Found)->count();
        $activeReports = Report::query()->whereIn('status', [
            ReportStatus::Active,
            ReportStatus::Processing,
            ReportStatus::UnderReview,
        ])->count();
        $recoveredReports = Report::query()->where('status', ReportStatus::Recovered)->count();

        $recoveryRate = $lostReports > 0 ? round(($recoveredReports / $lostReports) * 100, 1) : 0.0;

        $totalMatches = MatchCandidate::query()->count();
        $activeMatches = MatchCandidate::query()->whereIn('status', ['pending', 'under_review'])->count();
        $highPriorityMatches = MatchCandidate::query()
            ->whereIn('status', ['pending', 'under_review'])
            ->where('score', '>=', 80)
            ->count();
        $confirmedMatches = MatchCandidate::query()->where('status', 'confirmed')->count();

        $totalClaims = Claim::query()->count();
        $pendingClaims = Claim::query()->where('status', 'pending')->count();
        $approvedClaims = Claim::query()->where('status', 'approved')->count();

        $pendingVerificationCount = $activeMatches + $pendingClaims;

        $users = User::query()->count();
        $verifiedUsers = User::query()->whereNotNull('email_verified_at')->count();
        $adminCount = User::query()->where('role', UserRole::Admin)->count();
        $reviewerCount = User::query()->where('role', UserRole::Reviewer)->count();

        $pendingMatchCandidates = MatchCandidate::query()
            ->with(['lostReport.user', 'foundReport.user'])
            ->whereIn('status', ['pending', 'under_review'])
            ->orderByDesc('score')
            ->latest('created_at')
            ->limit(8)
            ->get();

        $pendingClaimsList = Claim::query()
            ->with(['claimant', 'report', 'matchCandidate'])
            ->latest('created_at')
            ->limit(8)
            ->get();

        // Searchable and filterable ID Management Records Table
        $manageQuery = Report::query()
            ->with(['user', 'images'])
            ->withCount(['claims as approved_claims_count' => fn ($query) => $query->where('status', 'approved')])
            ->latest('created_at');

        if ($request->filled('report_q')) {
            $term = '%'.trim((string) $request->query('report_q')).'%';
            $manageQuery->where(function ($sub) use ($term): void {
                $sub->where('public_reference', 'like', $term)
                    ->orWhere('district', 'like', $term)
                    ->orWhere('location_description', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term));
            });
        }

        if ($request->filled('report_kind') && $request->query('report_kind') !== 'all') {
            $manageQuery->where('kind', $request->query('report_kind'));
        }

        if ($request->filled('report_type') && $request->query('report_type') !== 'all') {
            $manageQuery->where('document_type', $request->query('report_type'));
        }

        if ($request->filled('report_status') && $request->query('report_status') !== 'all') {
            $manageQuery->where('status', $request->query('report_status'));
        }

        $manageReports = $manageQuery->paginate(12, ['*'], 'reports_page')->withQueryString();

        $recentReports = Report::query()
            ->latest('created_at')
            ->limit(10)
            ->get();

        $recentEvents = AuditEvent::query()
            ->with('user')
            ->latest('created_at')
            ->limit(12)
            ->get();

        $reportsByType = Report::query()
            ->select('document_type', DB::raw('count(*) as count'))
            ->groupBy('document_type')
            ->pluck('count', 'document_type')
            ->all();

        $topDistricts = Report::query()
            ->select('district', DB::raw('count(*) as count'))
            ->whereNotNull('district')
            ->groupBy('district')
            ->orderByDesc('count')
            ->limit(6)
            ->get();

        return view('admin.dashboard', compact(
            'reports',
            'lostReports',
            'foundReports',
            'activeReports',
            'recoveredReports',
            'recoveryRate',
            'totalMatches',
            'activeMatches',
            'highPriorityMatches',
            'confirmedMatches',
            'totalClaims',
            'pendingClaims',
            'approvedClaims',
            'pendingVerificationCount',
            'users',
            'verifiedUsers',
            'adminCount',
            'reviewerCount',
            'pendingMatchCandidates',
            'pendingClaimsList',
            'manageReports',
            'recentReports',
            'recentEvents',
            'reportsByType',
            'topDistricts',
        ));
    }

    public function updateReportStatus(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:processing,active,under_review,matched,recovered,closed,rejected,expired',
            'admin_notes' => 'nullable|string|max:500',
        ]);

        if (
            $validated['status'] === ReportStatus::Recovered->value
            && $report->kind === ReportKind::Found
            && $report->station_dropoff_status !== null
            && $report->station_dropoff_status !== StationDropoffStatus::Collected
        ) {
            return back()->withErrors([
                'status' => 'A found document can only be marked recovered after station collection is confirmed.',
            ]);
        }

        $oldStatus = $report->status->value;
        $report->status = ReportStatus::from($validated['status']);
        $report->save();

        AuditEvent::log('report.status_updated', $report, [
            'old_status' => $oldStatus,
            'new_status' => $validated['status'],
            'notes' => $validated['admin_notes'] ?? null,
            'admin_id' => $request->user()->id,
        ]);

        return redirect()->back()->with('success', 'Document record #'.$report->public_reference.' updated to '.ucfirst($validated['status']).'.');
    }

    public function updateClaim(Request $request, Claim $claim): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,reviewing,approved,rejected',
            'reviewer_notes' => 'nullable|string|max:1000',
            'decision_reason' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($claim, $request, $validated): void {
            $lockedClaim = Claim::query()->findOrFail($claim->id);
            $report = Report::query()->lockForUpdate()->findOrFail($lockedClaim->report_id);
            $lockedClaim = Claim::query()->lockForUpdate()->findOrFail($claim->id);

            if ($report->station_dropoff_status === StationDropoffStatus::Collected) {
                throw ValidationException::withMessages([
                    'status' => 'A claim cannot be changed after station collection is confirmed.',
                ]);
            }

            if ($validated['status'] === 'approved' && $report->station_dropoff_status !== null) {
                if ($report->station_dropoff_status !== StationDropoffStatus::Deposited) {
                    throw ValidationException::withMessages([
                        'status' => 'A claim can only be approved after police station receipt is confirmed.',
                    ]);
                }

                if ($report->claims()->where('status', 'approved')->where('id', '!=', $lockedClaim->id)->exists()) {
                    throw ValidationException::withMessages([
                        'status' => 'Another claimant is already approved for this document.',
                    ]);
                }
            }

            $lockedClaim->status = $validated['status'];
            $lockedClaim->reviewer_notes = $validated['reviewer_notes'] ?? null;
            $lockedClaim->decision_reason = $validated['decision_reason'] ?? null;
            $lockedClaim->reviewer_id = $request->user()->id;
            $lockedClaim->reviewed_at = now();

            if ($validated['status'] === 'approved' && $report->station_dropoff_status === null) {
                $lockedClaim->recovered_at = now();
                $report->update(['status' => ReportStatus::Recovered]);
            }

            $lockedClaim->save();

            AuditEvent::log('claim.reviewed', $lockedClaim, [
                'status' => $validated['status'],
                'reviewer_id' => $request->user()->id,
            ]);
        });

        return redirect()->back()->with('success', 'Claim #'.$claim->id.' updated to '.ucfirst($validated['status']).'.');
    }
}
