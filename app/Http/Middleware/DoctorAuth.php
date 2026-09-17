<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request; use Illuminate\Support\Facades\Auth;
class DoctorAuth {
    public function handle(Request $request, Closure $next) {
        if (!Auth::guard('doctor')->check()) {
            return redirect()->route('login')->with('error', 'Please login as a doctor.');
        }
        $doctor = Auth::guard('doctor')->user();
        if (!$doctor->last_seen_at || $doctor->last_seen_at->lt(now()->subMinute())) {
            $doctor->update(['last_seen_at' => now()]);
        }
        return $next($request);
    }
}
