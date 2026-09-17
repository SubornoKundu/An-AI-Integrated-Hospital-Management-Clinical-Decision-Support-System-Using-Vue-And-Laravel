<script setup lang="ts">
import PublicFooter from '@/components/public/PublicFooter.vue';
import PublicNavbar from '@/components/public/PublicNavbar.vue';
import { Toaster } from '@/components/ui/sonner';
</script>

<template>
    <div class="min-h-screen bg-white font-heading text-ink-900 antialiased">
        <PublicNavbar />
        <main>
            <slot />
        </main>
        <PublicFooter />
        <Toaster position="top-right" rich-colors />
    </div>
</template>
