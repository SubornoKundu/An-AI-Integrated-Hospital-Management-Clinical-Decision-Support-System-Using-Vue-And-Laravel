<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\Doctor;
use App\Models\LoginAttempt;
use App\Models\Patient;
use App\Models\PasswordResetCode;
use App\Models\StaffAdmin;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AuthController extends Controller
{
    protected NotificationService $ns;

    public function __construct(NotificationService $ns)
    {
        $this->ns = $ns;
    }

    // ── Show Login Page ──────────────────────────────────────────
    public function showLogin(Request $request)
    {
        // Redirect already logged in users
        if (Auth::guard('patient')->check()) {
            return redirect()->route('patient.dashboard');
        }
        if (Auth::guard('doctor')->check()) {
            return redirect()->route('doctor.dashboard');
        }
        if (session('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Login', [
            'initialView' => $request->routeIs('register') ? 'register' : 'login',
        ]);
    }

    // ── Patient Register ─────────────────────────────────────────
    public function registerPatient(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:patients,email',
            'contact' => 'required|string|max:15',
            'gender' => 'required|in:Male,Female,Other',
            'password' => 'required|min:8|confirmed',
        ]);

        $patient = Patient::create([
            'first_name' => trim($request->first_name),
            'last_name' => trim($request->last_name),
            'email' => strtolower(trim($request->email)),
            'contact' => $request->contact,
            'gender' => $request->gender,
            'password' => Hash::make($request->password),
        ]);

        $patient->update([
            'photo' => \App\Support\AvatarGenerator::saveFor($patient->full_name, 'patients', $patient->id, $patient->gender),
        ]);

        Auth::guard('patient')->login($patient);

        try {
            $this->ns->send($patient->id, 'patient',
                'Welcome to MediCare!',
                'Your account is ready. Book your first appointment.',
                'general');
        } catch (\Exception $e) {
            Log::warning('Welcome notification failed to send', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('patient.dashboard')
            ->with('success', 'Account created successfully! Welcome, '.$patient->first_name.'!');
    }

    // ── Patient Login ────────────────────────────────────────────
    public function loginPatient(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $email = strtolower(trim($request->email));

        if ($this->isLocked($email, 'patient')) {
            return back()
                ->with('error', 'Too many failed attempts. Please wait '.config('hospital.lockout_minutes', 15).' minutes.')
                ->with('error_tab', 'patient')
                ->withInput();
        }

        $patient = Patient::where('email', $email)->first();

        if ($patient && Hash::check($request->password, $patient->password)) {
            $this->clearAttempts($email, 'patient');
            Auth::guard('patient')->login($patient, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->intended(route('patient.dashboard'));
        }

        $this->recordAttempt($email, 'patient', $request->ip());

        return back()
            ->with('error', 'Invalid email or password. Please try again.')
            ->with('error_tab', 'patient')
            ->withInput();
    }

    // ── Doctor Login ─────────────────────────────────────────────
    public function loginDoctor(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $email = strtolower(trim($request->email));

        if ($this->isLocked($email, 'doctor')) {
            return back()
                ->with('error', 'Too many failed attempts. Please wait '.config('hospital.lockout_minutes', 15).' minutes.')
                ->with('error_tab', 'doctor')
                ->withInput();
        }

        $doctor = Doctor::where('email', $email)->first();

        if ($doctor && Hash::check($request->password, $doctor->password)) {
            $this->clearAttempts($email, 'doctor');
            Auth::guard('doctor')->login($doctor, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->route('doctor.dashboard');
        }

        $this->recordAttempt($email, 'doctor', $request->ip());

        return back()
            ->with('error', 'Invalid email or password. Please try again.')
            ->with('error_tab', 'doctor')
            ->withInput();
    }

    // ── Admin Login ──────────────────────────────────────────────
    public function loginAdmin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $email = trim($request->email);

        if ($this->isLocked($email, 'admin')) {
            return back()
                ->with('error', 'Too many failed attempts.')
                ->with('error_tab', 'admin')
                ->withInput();
        }

        // ১. প্রথমে hardcoded admin check (config থেকে — শুধু .env-এ explicit ভাবে সেট থাকলে)
        // Dev/test convenience only. Hard-blocked outside local/testing so a
        // forgotten default ADMIN_PASSWORD in a production .env can never be
        // used to log in — production must go through the DB-backed
        // StaffAdmin check below, with a real hashed password.
        $envEmail = config('hospital.admin_username');
        $envPassword = config('hospital.admin_password');

        if ($envEmail && $envPassword && ! app()->isProduction() &&
            $email === $envEmail && $request->password === $envPassword) {
            $this->clearAttempts($email, 'admin');

            // Link this login to a real, editable admin record — created
            // once, reused after that — so the profile page (photo, name,
            // password) works exactly like doctor/patient instead of
            // having no account to attach a photo to. The generated
            // password here is random and unrelated to $envPassword: this
            // linked record isn't meant to be logged into directly via
            // the normal admin/database login, only through this .env path.
            $admin = StaffAdmin::firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'Administrator',
                    'password' => Hash::make(Str::random(40)),
                    'role' => 'admin',
                    'is_active' => true,
                ]
            );

            session([
                'admin_logged_in' => true,
                'admin_name' => $admin->name,
                'admin_id' => $admin->id,
                // Soft flag (not a hard block): lets the profile page show
                // a gentle nudge to move off the .env-based login, without
                // preventing photo/profile editing.
                'admin_via_env_login' => true,
            ]);
            $request->session()->regenerate();

            return redirect()->route('admin.dashboard');
        }

        // ২. তারপর database এ StaffAdmin check করো
        $admin = StaffAdmin::where('role', 'admin')
            ->where('is_active', true)
            ->where(function ($q) use ($email) {
                $q->where('email', $email)->orWhere('username', $email);
            })
            ->first();

        if ($admin && Hash::check($request->password, $admin->password)) {
            $this->clearAttempts($email, 'admin');
            session(['admin_logged_in' => true, 'admin_name' => $admin->name, 'admin_id' => $admin->id]);
            $request->session()->regenerate();

            return redirect()->route('admin.dashboard');
        }

        $this->recordAttempt($email, 'admin', $request->ip());

        return back()
            ->with('error', 'Invalid admin credentials. Please try again.')
            ->with('error_tab', 'admin')
            ->withInput();
    }

    // ── Logout ───────────────────────────────────────────────────
    public function logoutPatient()
    {
        Auth::guard('patient')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Logged out successfully.');
    }

    public function logoutDoctor()
    {
        Auth::guard('doctor')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Logged out successfully.');
    }

    public function logoutAdmin()
    {
        session()->forget(['admin_logged_in', 'admin_name', 'admin_id', 'admin_via_env_login']);
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Logged out successfully.');
    }

    // ── Forgot Password (email code) ──────────────────────────────

    private function accountExists(string $email, string $role): bool
    {
        return match ($role) {
            'patient' => Patient::where('email', $email)->exists(),
            'doctor' => Doctor::where('email', $email)->exists(),
            'admin' => StaffAdmin::where('role', 'admin')->where('email', $email)->exists(),
            default => false,
        };
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'patient' => 'Patient',
            'doctor' => 'Doctor',
            'admin' => 'Admin',
            default => 'account',
        };
    }

    public function sendResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:patient,doctor,admin',
        ]);

        $email = strtolower(trim($request->email));
        $role = $request->role;

        // Rate limit: max 3 code requests per 10 minutes per email+role,
        // so this can't be used to spam someone's inbox.
        $recent = PasswordResetCode::where('email', $email)
            ->where('role', $role)
            ->where('created_at', '>', now()->subMinutes(10))
            ->count();

        if ($recent >= 3) {
            return back()
                ->with('error', 'Too many reset requests. Please wait a few minutes and try again.')
                ->with('error_tab', $role);
        }

        // Only actually send an email if the account exists, but always
        // return the same message either way — this stops someone from
        // using this form to check which emails have accounts here.
        if ($this->accountExists($email, $role)) {
            $code = (string) random_int(100000, 999999);

            PasswordResetCode::create([
                'email' => $email,
                'role' => $role,
                'code' => Hash::make($code),
                'expires_at' => now()->addMinutes(15),
            ]);

            try {
                Mail::to($email)->send(new PasswordResetCodeMail($code, $this->roleLabel($role)));
            } catch (\Exception $e) {
                Log::error('Password reset email failed to send', [
                    'email' => $email,
                    'role' => $role,
                    'error' => $e->getMessage(),
                ]);

                return back()
                    ->with('error', 'Could not send the email — please check the mail server configuration.')
                    ->with('error_tab', $role);
            }
        }

        return back()->with('success', 'If an account exists with that email, a reset code has been sent.');
    }

    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:patient,doctor,admin',
            'code' => 'required|digits:6',
        ]);

        $email = strtolower(trim($request->email));

        $reset = PasswordResetCode::where('email', $email)
            ->where('role', $request->role)
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if (! $reset || $reset->attempts >= 5) {
            return back()
                ->with('error', 'This code has expired or had too many attempts. Please request a new one.')
                ->with('error_tab', $request->role);
        }

        if (! Hash::check($request->code, $reset->code)) {
            $reset->increment('attempts');

            return back()
                ->with('error', 'Incorrect code. Please check your email and try again.')
                ->with('error_tab', $request->role);
        }

        $reset->update(['verified' => true]);

        return back()->with('success', 'Code verified — set your new password below.');
    }

    public function resetPasswordWithCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:patient,doctor,admin',
            'code' => 'required|digits:6',
            'password' => 'required|min:8|confirmed',
        ]);

        $email = strtolower(trim($request->email));

        $reset = PasswordResetCode::where('email', $email)
            ->where('role', $request->role)
            ->where('verified', true)
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if (! $reset || ! Hash::check($request->code, $reset->code)) {
            return back()
                ->with('error', 'Your reset session has expired. Please request a new code.')
                ->with('error_tab', $request->role);
        }

        $hashed = Hash::make($request->password);

        match ($request->role) {
            'patient' => Patient::where('email', $email)->update(['password' => $hashed]),
            'doctor' => Doctor::where('email', $email)->update(['password' => $hashed]),
            'admin' => StaffAdmin::where('role', 'admin')->where('email', $email)->update(['password' => $hashed]),
        };

        // Clean up every reset code for this email+role — used or not.
        PasswordResetCode::where('email', $email)->where('role', $request->role)->delete();

        return redirect()->route('login')
            ->with('success', 'Password reset successfully — please sign in with your new password.')
            ->with('error_tab', $request->role);
    }

    // ── Helpers ──────────────────────────────────────────────────
    private function isLocked(string $id, string $role): bool
    {
        $cutoff = Carbon::now()->subMinutes(config('hospital.lockout_minutes', 15));

        return LoginAttempt::where('identifier', $id)
            ->where('role', $role)
            ->where('attempt_time', '>', $cutoff)
            ->count() >= config('hospital.max_attempts', 5);
    }

    private function recordAttempt(string $id, string $role, string $ip): void
    {
        LoginAttempt::create(['identifier' => $id, 'role' => $role, 'ip' => $ip]);
    }

    private function clearAttempts(string $id, string $role): void
    {
        LoginAttempt::where('identifier', $id)->where('role', $role)->delete();
    }
}
