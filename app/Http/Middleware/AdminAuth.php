<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request;

/**
 * SECURITY REVIEW (see MEDICARE_FINAL_REPORT.md §D for the full trace):
 *
 * This checks a raw session boolean instead of Auth::guard('admin')->check(),
 * unlike PatientAuth/DoctorAuth. Reviewed deliberately, not left by oversight:
 *
 * Traced every place `admin_logged_in` is written (exactly one: AuthController's
 * loginAdmin(), gated behind either a Hash::check() against a real StaffAdmin
 * password, or the .env bootstrap credential — which is itself hard-blocked
 * outside production, see loginAdmin()) and every place it's read (11 sites
 * across 6 files). No code path sets it without going through that one gated
 * check; no session($request->all())-style pattern or debug/impersonation
 * route exists anywhere in the app that could set it another way.
 *
 * Conclusion: not an active vulnerability today. It IS an architectural
 * inconsistency worth fixing eventually — migrating to a real guard would
 * mean updating all 11 read sites consistently in one pass, which isn't
 * safe to do without the ability to run the app and confirm nothing was
 * missed. Left as a deliberate, documented decision rather than a silent
 * gap or a risky unverified refactor.
 */
class AdminAuth {
    public function handle(Request $request, Closure $next) {
        if (!session('admin_logged_in')) {
            return redirect()->route('login')->with('error', 'Admin access required.');
        }
        return $next($request);
    }
}
