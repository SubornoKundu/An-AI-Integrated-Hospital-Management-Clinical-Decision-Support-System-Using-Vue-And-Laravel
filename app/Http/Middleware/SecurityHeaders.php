<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Baseline security headers. Deliberately NOT including a
     * Content-Security-Policy here: a strict CSP can break the app in ways
     * that only show up in the browser (blocked Vite/inline scripts, blocked
     * styles) — there's no way to verify that from this sandbox, so a wrong
     * CSP is a real regression risk rather than a safe default. Recommend
     * building one deliberately, tested in a real browser, as a follow-up.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // microphone=(self) — not blocked outright: the symptom checker's
        // voice input (resources/js/pages/patient/ai/SymptomChecker.vue)
        // genuinely uses getUserMedia. Camera/geolocation are confirmed
        // unused anywhere in the frontend, so those stay blocked.
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(self), geolocation=()');

        return $response;
    }
}
