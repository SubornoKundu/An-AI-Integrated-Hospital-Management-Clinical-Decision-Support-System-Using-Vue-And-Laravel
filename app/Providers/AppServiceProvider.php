<?php

namespace App\Providers;

use App\Services\NotificationService;
use App\Services\SlotService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SlotService::class);
        $this->app->singleton(NotificationService::class);
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Every Groq-backed AI endpoint (symptom checker, prescription
        // explainer, health-risk/plan, doctor's AI summary/safety-check,
        // admin AI insights) had zero rate limiting until now — a real
        // cost/abuse exposure on a paid external API. Keyed per-user, not
        // per-IP: patient/doctor use real Auth guards (like the 'login'
        // limiter already does), admin doesn't (see the admin-auth
        // architecture note in the audit report), so admin falls back to
        // its actual session key. IP is only a last-resort fallback — these
        // routes are already auth-gated, so it shouldn't normally be hit.
        // 20/hour is a starting point: generous for genuine use, bounded
        // enough to stop a single account looping the endpoint. Adjust via
        // this one place if your real usage pattern needs different limits.
        RateLimiter::for('ai', function (Request $request) {
            $key = match (true) {
                Auth::guard('patient')->check() => 'patient:'.Auth::guard('patient')->id(),
                Auth::guard('doctor')->check() => 'doctor:'.Auth::guard('doctor')->id(),
                (bool) $request->session()->get('admin_logged_in') => 'admin:'.$request->session()->get('admin_id'),
                default => $request->ip(),
            };

            return Limit::perHour(20)->by($key);
        });

        // Renders the app's own branded page for these statuses instead of
        // Laravel's default error screen — excluded in local/testing and
        // whenever debug mode is on, since you want real error details while
        // developing, not a page hiding what went wrong. This is Inertia's
        // own purpose-built mechanism for this (handles Inertia's root
        // view/version/shared-data resolution correctly on its own) rather
        // than something hand-rolled.
        if (! app()->environment(['local', 'testing']) && ! config('app.debug')) {
            Inertia::handleExceptionsUsing(function ($exceptionResponse) {
                $status = $exceptionResponse->statusCode();
                if (in_array($status, [403, 404, 419, 429, 500, 503], true)) {
                    return $exceptionResponse->render('errors/Error', ['status' => $status]);
                }
                return null;
            });
        }

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
            DB::connection()->disableQueryLog();
            Model::shouldBeStrict(false);

            // Same safety net as before, moved here: cookies are HTTPS-only
            // in production even if SESSION_SECURE_COOKIE is never set in
            // .env. This was originally in config/session.php directly, but
            // config files load too early in the bootstrap for
            // $this->app->environment() to be safe to call — that's what
            // broke `php artisan config:clear` (and every other artisan
            // command) after that change. boot() runs after config is fully
            // loaded, so the same check here is safe.
            config(['session.secure' => config('session.secure') ?? true]);
        }

        if ($this->app->environment('local')) {
            Model::preventLazyLoading();
        }
    }
}
