import { createInertiaApp, router } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AdminLayout from '@/layouts/AdminLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import DoctorLayout from '@/layouts/DoctorLayout.vue';
import PatientLayout from '@/layouts/PatientLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const PUBLIC_PAGES = ['Home', 'About', 'Contact'];

// Keeps the browser tab icon in sync with the hospital's uploaded logo.
// app.blade.php already sets the favicon correctly on a full page load, but
// Inertia visits only swap the page content (not the static <head> tag), so
// without this, a freshly-uploaded logo wouldn't show as the tab icon until
// the next hard refresh.
let currentFaviconUrl: string | null = null;
function syncFavicon(logoUrl?: string | null) {
    if (!logoUrl || logoUrl === currentFaviconUrl) {
        return;
    }

    currentFaviconUrl = logoUrl;

    document.querySelectorAll("link[rel~='icon']").forEach((el) => el.remove());
    const link = document.createElement('link');
    link.rel = 'icon';
    link.href = logoUrl;
    document.head.appendChild(link);
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name === 'Login':
                return null;
            case PUBLIC_PAGES.includes(name):
                return PublicLayout;
            case name.startsWith('patient/'):
                return PatientLayout;
            case name.startsWith('doctor/'):
                return DoctorLayout;
            case name.startsWith('admin/'):
                return AdminLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            default:
                // No current page name falls through to here — every
                // existing page matches one of the cases above. This is a
                // defensive fallback only, in case a future top-level page
                // is added without a patient/doctor/admin/auth prefix.
                return PublicLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();

// Keep the browser tab icon synced with the hospital's uploaded logo
// across Inertia SPA navigations (see syncFavicon() above for why this
// is needed in addition to the favicon already set in app.blade.php).
router.on('navigate', (event) => {
    syncFavicon(
        event.detail.page.props.hospital?.logo as string | null | undefined,
    );
});
