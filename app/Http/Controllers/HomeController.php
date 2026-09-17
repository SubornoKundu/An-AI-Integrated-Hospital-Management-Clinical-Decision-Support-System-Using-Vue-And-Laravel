<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorCategory;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        // If already logged in, redirect to their dashboard
        if (Auth::guard('patient')->check()) {
            return redirect()->route('patient.dashboard');
        }
        if (Auth::guard('doctor')->check()) {
            return redirect()->route('doctor.dashboard');
        }
        if (session('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }

        try {
            $stats = [
                'doctors' => Doctor::count(),
                'patients' => Patient::count(),
                'completed' => Appointment::where('status', 'completed')->count(),
            ];
            $categories = DoctorCategory::where('is_active', 1)->orderBy('name')->get();
            $featuredDoctors = Doctor::with('category')->inRandomOrder()->take(6)->get();
        } catch (\Exception $e) {
            $stats = ['doctors' => 0, 'patients' => 0, 'completed' => 0];
            $categories = collect();
            $featuredDoctors = collect();
        }

        return Inertia::render('Home', [
            'stats' => $stats,
            'categories' => $categories->map(fn (DoctorCategory $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'icon' => $c->icon,
            ]),
            'featuredDoctors' => $featuredDoctors->map(fn (Doctor $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'category' => $d->category?->name ?? 'Specialist',
                'icon' => $d->category?->icon ?? '🩺',
                'consultancy_fee' => (float) $d->consultancy_fee,
                'avg_rating' => $d->avg_rating,
                'rating_count' => $d->rating_count,
            ]),
        ]);
    }

    public function about(): Response
    {
        return Inertia::render('About');
    }

    public function contact(): Response
    {
        return Inertia::render('Contact');
    }
}
