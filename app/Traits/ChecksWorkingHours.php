<?php

namespace App\Traits;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

trait ChecksWorkingHours
{
    /**
     * Check if the current time is within working hours for the specified type.
     * 
     * @param string $type orders, pickups, or material_requests
     * @return void
     */
    protected function checkWorkingHours(string $type): void
    {
        $user = Auth::user();

        // 1. Check for bypass permission
        if ($user && $user->can('setting.bypass-working-hours')) {
            return;
        }

        // 2. Get start and end times from settings
        $startTime = Setting::getValue("working_hours_{$type}_start", '08:00');
        $endTime = Setting::getValue("working_hours_{$type}_end", '22:00');

        $now = Carbon::now();
        $start = Carbon::createFromTimeString($startTime);
        $end = Carbon::createFromTimeString($endTime);

        // Handle overnight shifts if necessary (e.g. 22:00 to 06:00)
        $isWithin = false;
        if ($start->lessThan($end)) {
            $isWithin = $now->between($start, $end);
        } else {
            // Overnight case
            $isWithin = $now->greaterThanOrEqualTo($start) || $now->lessThanOrEqualTo($end);
        }

        if (!$isWithin) {
            abort(Response::HTTP_FORBIDDEN, "Creation is restricted to working hours: {$startTime} - {$endTime}.");
        }
    }
}
