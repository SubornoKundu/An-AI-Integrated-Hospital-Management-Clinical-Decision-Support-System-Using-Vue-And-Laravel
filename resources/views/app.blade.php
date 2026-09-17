<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- MediCare Hospital is a light-only product by design (matches the
             original app, which had no theme toggle) — we intentionally do not
             read system/stored appearance preference here, so the UI never
             flips to dark mode regardless of the visitor's OS setting. --}}

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }
        </style>

        @php
            try {
                $faviconLogo = \App\Models\HospitalSetting::get('hospital_logo', '');
            } catch (\Exception $e) {
                $faviconLogo = '';
            }
        @endphp
        @if($faviconLogo)
            <link rel="icon" href="{{ asset($faviconLogo) }}">
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
            <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        @endif
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
