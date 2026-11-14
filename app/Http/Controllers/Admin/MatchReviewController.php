<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\MatchCandidate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MatchReviewController extends Controller
{
    public function index(Request $request): View
    {
        $query = MatchCandidate::query()
            ->with(['lostReport.user', 'foundReport.user', 'claims'])
            ->latest('created_at');

        if ($request->filled('status') && $request->query('status') !== 'all') {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('min_score')) {
            $query->where('score', '>=', (int) $request->query('min_score'));
        }

        if ($request->filled('q')) {
            $term = '%'.$request->query('q').'%';
            $query->where(function ($sub) use ($term): void {
                $sub->whereHas('lostReport', fn ($r) => $r->where('public_reference', 'like', $term)->orWhere('district', 'like', $term))
                    ->orWhereHas('foundReport', fn ($r) => $r->where('public_reference', 'like', $term)->orWhere('district', 'like', $term));
            });
        }

        $matches = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => MatchCandidate::query()->count(),
            'pending' => MatchCandidate::query()->whereIn('status', ['pending', 'under_review'])->count(),
            'high_priority' => MatchCandidate::query()->where('score', '>=', 80)->count(),
            'confirmed' => MatchCandidate::query()->where('status', 'confirmed')->count(),
            'dismissed' => MatchCandidate::query()->where('status', 'dismissed')->count(),
        ];

        return view('admin.matches.index', compact('matches', 'stats'));
    }

    public function show(MatchCandidate $match): View
    {
        $match->load([
            'lostReport.user',
            'lostReport.extractedFields',
            'foundReport.user',
            'foundReport.extractedFields',
            'claims.claimant',
            'reviewer',
        ]);

        return view('admin.matches.show', compact('match'));
    }

    public function update(Request $request, MatchCandidate $match): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,under_review,confirmed,dismissed',
            'reviewer_notes' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $match->status;

        $match->update([
            'status' => $validated['status'],
            'reviewer_notes' => $validated['reviewer_notes'] ?? null,
            'reviewer_id' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        AuditEvent::log('match_candidate.reviewed', $match, [
            'old_status' => $oldStatus,
            'new_status' => $validated['status'],
            'reviewer_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.matches.show', $match)
            ->with('success', 'Match decision and notes recorded successfully.');
    }
}
