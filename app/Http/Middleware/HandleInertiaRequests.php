<?php

namespace App\Http\Middleware;

use App\Models\HospitalSetting;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'hospital' => fn () => $this->hospitalProps($request),
            'flash' => [
                'toast' => fn () => $this->flashToast($request),
                'error_tab' => fn () => $request->session()->get('error_tab'),
            ],
        ];
    }

    /**
     * Role-aware auth + branding data shared with every Inertia page,
     * used by the public navbar/footer and the login screen.
     *
     * @return array<string, mixed>
     */
    protected function hospitalProps(Request $request): array
    {
        $role = null;
        $user = null;
        $unread = 0;

        if (Auth::guard('patient')->check()) {
            $role = 'patient';
            $user = Auth::guard('patient')->user();
            $unread = Notification::forUser($user->id, 'patient')->unread()->count();
        } elseif (Auth::guard('doctor')->check()) {
            $role = 'doctor';
            $user = Auth::guard('doctor')->user();
            $unread = Notification::forUser($user->id, 'doctor')->unread()->count();
        } elseif ($request->session()->get('admin_logged_in')) {
            $role = 'admin';
            $adminId = $request->session()->get('admin_id');
            $user = (object) [
                'id' => $adminId,
                'name' => $request->session()->get('admin_name', 'Administrator'),
                'email' => null,
                'photo' => $adminId ? \App\Models\StaffAdmin::find($adminId)?->photo : null,
            ];
        }

        $logo = HospitalSetting::get('hospital_logo');

        return [
            'role' => $role,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user instanceof Model ? $user->full_name : $user->name,
                'email' => $user->email ?? null,
                'photo' => isset($user->photo) && $user->photo ? asset($user->photo) : null,
            ] : null,
            'unreadNotifications' => $unread,
            'name' => HospitalSetting::get('hospital_name', config('app.name')),
            'logo' => $logo ? asset($logo) : null,
            'address' => HospitalSetting::get('hospital_address', ''),
            'phone' => HospitalSetting::get('hospital_phone', ''),
            'email' => HospitalSetting::get('hospital_email', ''),
        ];
    }

    /**
     * Convert the legacy session('success') / session('error') flash
     * messages into the {type, message} shape vue-sonner expects.
     */
    protected function flashToast(Request $request): ?array
    {
        if ($success = $request->session()->get('success')) {
            return ['type' => 'success', 'message' => $success];
        }

        if ($error = $request->session()->get('error')) {
            return ['type' => 'error', 'message' => $error];
        }

        return null;
    }
}
