<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatientAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('patient')->check()) {
            return redirect()->route('login')->with('error', 'Please login as a patient.');
        }

        $patient = Auth::guard('patient')->user();
        if (!$patient->last_seen_at || $patient->last_seen_at->lt(now()->subMinute())) {
            $patient->update(['last_seen_at' => now()]);
        }

        return $next($request);
    }
}
