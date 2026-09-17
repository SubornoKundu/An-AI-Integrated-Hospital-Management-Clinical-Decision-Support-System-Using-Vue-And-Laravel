import { onMounted, onUnmounted, ref } from 'vue';
import type { Ref } from 'vue';

interface CountUpOptions {
    duration?: number;
    decimals?: number;
}

export function useCountUp(
    target: number,
    options: CountUpOptions = {},
): { value: Ref<number>; el: Ref<HTMLElement | null> } {
    const { duration = 1800, decimals = 0 } = options;
    const value = ref(0);
    const el = ref<HTMLElement | null>(null);
    let observer: IntersectionObserver | null = null;
    let started = false;
    let frame: number | null = null;

    function animate() {
        if (started) {
            return;
        }

        started = true;
        const start = performance.now();

        function tick(now: number) {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            value.value = Number((target * eased).toFixed(decimals));

            if (progress < 1) {
                frame = requestAnimationFrame(tick);
            }
        }

        frame = requestAnimationFrame(tick);
    }

    onMounted(() => {
        if (typeof IntersectionObserver === 'undefined') {
            value.value = target;

            return;
        }

        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        animate();
                        observer?.disconnect();
                    }
                });
            },
            { threshold: 0.4 },
        );

        if (el.value) {
            observer.observe(el.value);
        }
    });

    onUnmounted(() => {
        observer?.disconnect();

        if (frame) {
            cancelAnimationFrame(frame);
        }
    });

    return { value, el };
}
