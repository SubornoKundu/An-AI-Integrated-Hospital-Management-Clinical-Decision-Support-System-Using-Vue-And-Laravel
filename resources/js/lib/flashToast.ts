import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

// NOTE: this deliberately listens on 'navigate', not 'flash'. Inertia's
// native 'flash' event only fires for data sent via the PHP-side
// Inertia::flash() helper (a separate channel from regular props). This
// app shares toast data as a normal prop instead (see
// HandleInertiaRequests::flashToast()), so it shows up in
// page.props.flash.toast on every visit — which 'navigate' correctly
// reflects, while 'flash' would silently never fire for it.
let lastShownKey = '';

export function initializeFlashToast(): void {
    router.on('navigate', (event) => {
        const data = event.detail.page.props.flash?.toast as
            FlashToast | undefined;

        if (!data) {
            return;
        }

        // Avoid re-showing the same toast if 'navigate' fires more than
        // once for the same page state (e.g. on first load).
        const key = `${data.type}:${data.message}:${event.detail.page.props.flash?.error_tab ?? ''}`;

        if (key === lastShownKey) {
            return;
        }

        lastShownKey = key;

        toast[data.type](data.message);
    });
}
