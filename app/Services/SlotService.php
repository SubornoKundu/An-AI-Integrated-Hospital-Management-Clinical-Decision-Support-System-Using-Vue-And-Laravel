<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\DoctorBlockedDate;
use App\Models\DoctorBreak;
use App\Models\DoctorSchedule;
use App\Models\HospitalHoliday;
use Carbon\Carbon;

class SlotService
{
    /**
     * Get available slots for a doctor on a given date.
     * Returns ['holiday' => true] if it's a hospital holiday.
     * Returns [] if no schedule / blocked.
     */
    public function getSlots(int $doctorId, string $date): array
    {
        // Hospital holiday?
        if (HospitalHoliday::whereDate('holiday_date', $date)->exists()) {
            return ['holiday' => true];
        }

        // Doctor blocked date?
        if (DoctorBlockedDate::where('doctor_id', $doctorId)->whereDate('blocked_date', $date)->exists()) {
            return [];
        }

        $dayOfWeek = (int) Carbon::parse($date)->dayOfWeek; // 0=Sun

        $schedule = DoctorSchedule::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$schedule) return [];

        $slotMinutes = (int) \App\Models\HospitalSetting::get('slot_duration', '15');
        $start = Carbon::parse($date . ' ' . $schedule->start_time);
        $end   = Carbon::parse($date . ' ' . $schedule->end_time);

        // Breaks
        $breaks = DoctorBreak::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->get()
            ->map(fn($b) => [
                Carbon::parse($date . ' ' . $b->break_start),
                Carbon::parse($date . ' ' . $b->break_end),
            ])->toArray();

        // Already booked slots
        $booked = Appointment::where('doctor_id', $doctorId)
            ->where('appointment_date', $date)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('slot_start')
            ->map(fn($t) => substr($t, 0, 5))
            ->toArray();

        // A same-day slot whose start time has already gone by is not
        // realistically bookable (a patient can't arrive in the past).
        // Comparing against "now" only matters when $date is today — for
        // any future date every slot is naturally still upcoming.
        $isToday = Carbon::parse($date)->isToday();
        $now     = Carbon::now();

        $slots = [];
        $cur = $start->copy();

        while ($cur->copy()->addMinutes($slotMinutes)->lte($end)) {
            $slotStart = $cur->format('H:i');
            $slotEnd   = $cur->copy()->addMinutes($slotMinutes)->format('H:i');

            $inBreak = false;
            foreach ($breaks as [$bStart, $bEnd]) {
                if ($cur->gte($bStart) && $cur->lt($bEnd)) {
                    $inBreak = true;
                    break;
                }
            }

            if (!$inBreak) {
                // isPast folds into 'booked' so the existing "is this slot
                // takeable?" check in bookConfirm() — which re-reads this
                // same list inside its DB transaction — rejects it too,
                // with a single source of truth instead of a second,
                // easy-to-forget time check duplicated at the controller
                // level. 'past' is kept separately just so the UI can show
                // an accurate reason instead of the misleading "Already booked".
                $isPast = $isToday && $cur->lte($now);

                $slots[] = [
                    'start'  => $slotStart,
                    'end'    => $slotEnd,
                    'booked' => in_array($slotStart, $booked) || $isPast,
                    'past'   => $isPast,
                ];
            }

            $cur->addMinutes($slotMinutes);
        }

        return $slots;
    }
}
