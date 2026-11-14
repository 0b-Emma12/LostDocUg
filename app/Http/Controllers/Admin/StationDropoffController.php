<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportKind;
use App\Enums\ReportStatus;
use App\Enums\StationDropoffStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\Claim;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StationDropoffController extends Controller
{
    public function update(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                StationDropoffStatus::Deposited->value,
                StationDropoffStatus::Collected->value,
            ])],
            'confirmation' => ['accepted'],
        ]);

        $nextStatus = StationDropoffStatus::from($validated['status']);

        DB::transaction(function () use ($request, $report, $nextStatus): void {
            $lockedReport = Report::query()->lockForUpdate()->findOrFail($report->id);

            if (
                $lockedReport->kind !== ReportKind::Found
                || blank($lockedReport->station_name)
                || blank($lockedReport->station_address)
            ) {
                throw ValidationException::withMessages([
                    'status' => 'This found report does not have a station drop-off point.',
                ]);
            }

            if ($nextStatus === StationDropoffStatus::Deposited) {
                if ($lockedReport->station_dropoff_status !== StationDropoffStatus::AwaitingConfirmation) {
                    throw ValidationException::withMessages([
                        'status' => 'Station receipt can only be confirmed while awaiting confirmation.',
                    ]);
                }

                $lockedReport->station_dropoff_status = StationDropoffStatus::Deposited;
                $lockedReport->station_received_at = now();
                $lockedReport->station_received_by = $request->user()->id;
            } else {
                if ($lockedReport->station_dropoff_status !== StationDropoffStatus::Deposited) {
                    throw ValidationException::withMessages([
                        'status' => 'A document can only be collected after station receipt is confirmed.',
                    ]);
                }

                $approvedClaim = Claim::query()
                    ->where('report_id', $lockedReport->id)
                    ->where('status', 'approved')
                    ->first();

                if (! $approvedClaim) {
                    throw ValidationException::withMessages([
                        'status' => 'A claim must be approved before station collection can be recorded.',
                    ]);
                }

                $lockedReport->station_dropoff_status = StationDropoffStatus::Collected;
                $lockedReport->station_collected_at = now();
                $lockedReport->station_collected_by = $request->user()->id;
                $lockedReport->status = ReportStatus::Recovered;
                $approvedClaim->recovered_at = now();
                $approvedClaim->save();
            }

            $lockedReport->save();

            AuditEvent::log(
                $nextStatus === StationDropoffStatus::Deposited
                    ? 'station.receipt_confirmed_offline'
                    : 'station.collection_confirmed_offline',
                $lockedReport,
                [
                    'status' => $nextStatus->value,
                    'reviewer_id' => $request->user()->id,
                ],
            );
        });

        return back()->with(
            'success',
            $nextStatus === StationDropoffStatus::Deposited
                ? 'Police station receipt was recorded after offline confirmation.'
                : 'Station collection was recorded after offline confirmation.',
        );
    }
}
