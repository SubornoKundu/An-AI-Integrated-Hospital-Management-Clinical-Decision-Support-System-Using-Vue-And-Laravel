<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';

interface Props {
    as?: string;
    delay?: number;
    direction?: 'up' | 'down' | 'left' | 'right' | 'none' | 'scale';
    duration?: number;
}

const props = withDefaults(defineProps<Props>(), {
    as: 'div',
    delay: 0,
    direction: 'up',
    duration: 700,
});

const el = ref<HTMLElement | null>(null);
const visible = ref(false);
let observer: IntersectionObserver | null = null;

const hiddenTransform: Record<string, string> = {
    up: 'translate-y-10',
    down: '-translate-y-10',
    left: 'translate-x-10',
    right: '-translate-x-10',
    scale: 'scale-95',
    none: '',
};

onMounted(() => {
    if (typeof IntersectionObserver === 'undefined') {
        visible.value = true;

        return;
    }

    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    visible.value = true;
                    observer?.disconnect();
                }
            });
        },
        { threshold: 0.15, rootMargin: '0px 0px -60px 0px' },
    );

    if (el.value) {
        observer.observe(el.value);
    }
});

onUnmounted(() => observer?.disconnect());
</script>

<template>
    <component
        :is="props.as"
        ref="el"
        :class="[
            'transition-all ease-out will-change-transform',
            visible
                ? 'translate-x-0 translate-y-0 scale-100 opacity-100'
                : `opacity-0 ${hiddenTransform[props.direction]}`,
        ]"
        :style="{
            transitionDuration: `${props.duration}ms`,
            transitionDelay: visible ? `${props.delay}ms` : '0ms',
        }"
    >
        <slot />
    </component>
</template>
