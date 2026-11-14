<?php

namespace App\Http\Controllers;

use App\Models\MatchCandidate;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $reports = $request->user()->reports()
            ->withCount('images')
            ->latest('created_at')
            ->latest('id')
            ->get()
            ->map(fn ($report): array => [
                'id' => $report->id,
                'reference' => $report->public_reference,
                'kind' => $report->kind->value,
                'documentType' => $report->document_type->value,
                'status' => $report->status->value,
                'district' => $report->district,
                'createdAt' => $report->created_at->toIso8601String(),
                'images' => $report->images_count,
            ])
            ->values();

        $matches = MatchCandidate::query()
            ->whereNotIn('status', ['dismissed', 'expired'])
            ->whereHas('lostReport', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with('foundReport')
            ->withCount(['claims as current_user_claims_count' => fn ($query) => $query->where('claimant_id', $request->user()->id)])
            ->latest('created_at')
            ->get()
            ->map(fn (MatchCandidate $match): array => [
                'reference' => $match->foundReport->public_reference,
                'documentType' => $match->foundReport->category_label,
                'district' => $match->foundReport->district,
                'incidentDate' => $match->foundReport->incident_date->format('M Y'),
                'url' => route('directory.show', $match->foundReport),
                'claimSubmitted' => $match->current_user_claims_count > 0,
            ])
            ->values();

        return view('dashboard', compact('reports', 'matches'));
    }
}
