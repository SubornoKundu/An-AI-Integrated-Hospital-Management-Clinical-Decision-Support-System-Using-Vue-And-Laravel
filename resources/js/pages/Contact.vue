<script setup lang="ts">
import { Head, usePage, useForm } from '@inertiajs/vue3';
import { Mail, MapPin, Phone, Send } from '@lucide/vue';
import { computed } from 'vue';
import Reveal from '@/components/public/Reveal.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const page = usePage();
const hospital = computed(() => page.props.hospital);

const form = useForm({
    name: '',
    email: '',
    contact: '',
    message: '',
});

function submit() {
    form.post('/contact', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

const infoCards = computed(() => [
    { icon: MapPin, label: 'Address', value: hospital.value?.address },
    { icon: Phone, label: 'Phone', value: hospital.value?.phone },
    { icon: Mail, label: 'Email', value: hospital.value?.email },
]);
</script>

<template>
    <Head title="Contact" />

    <section
        class="bg-gradient-to-b from-[#f0f5ff] to-white pt-32 pb-16 sm:pt-36"
    >
        <div class="mx-auto max-w-3xl px-4 text-center sm:px-6">
            <Reveal>
                <h1
                    class="font-heading text-3xl font-extrabold text-ink-900 sm:text-4xl"
                >
                    Contact Us
                </h1>
                <p class="mt-3 text-base text-ink-500">
                    Send us a message and we'll get back to you shortly
                </p>
            </Reveal>
        </div>
    </section>

    <section class="mx-auto max-w-3xl px-4 pb-24 sm:px-6">
        <Reveal :delay="80" class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div
                v-for="card in infoCards"
                :key="card.label"
                class="rounded-2xl border border-ink-100 bg-white p-5 text-center shadow-sm transition-shadow hover:shadow-md"
            >
                <span
                    class="mx-auto mb-3 flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"
                >
                    <component :is="card.icon" class="size-[18px]" />
                </span>
                <div
                    class="text-[11px] font-bold tracking-wide text-ink-400 uppercase"
                >
                    {{ card.label }}
                </div>
                <div
                    class="mt-1.5 text-[13.5px] leading-relaxed break-words text-ink-700"
                >
                    {{ card.value || '—' }}
                </div>
            </div>
        </Reveal>

        <Reveal
            :delay="160"
            class="rounded-2xl border border-ink-100 bg-white p-7 shadow-sm sm:p-8"
        >
            <h2
                class="mb-5 flex items-center gap-2 text-[15px] font-bold text-ink-900"
            >
                <Send class="size-[17px] text-brand-600" /> Send a Message
            </h2>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <Label for="name">Full Name *</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="Your name"
                            :aria-invalid="!!form.errors.name"
                            required
                        />
                        <p
                            v-if="form.errors.name"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="email">Email *</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="your@email.com"
                            :aria-invalid="!!form.errors.email"
                            required
                        />
                        <p
                            v-if="form.errors.email"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label for="contact">Phone Number *</Label>
                    <Input
                        id="contact"
                        v-model="form.contact"
                        placeholder="01XXXXXXXXX"
                        :aria-invalid="!!form.errors.contact"
                        required
                    />
                    <p
                        v-if="form.errors.contact"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.contact }}
                    </p>
                </div>

                <div class="space-y-1.5">
                    <Label for="message">Message *</Label>
                    <textarea
                        id="message"
                        v-model="form.message"
                        rows="5"
                        placeholder="Write your message here..."
                        required
                        :aria-invalid="!!form.errors.message"
                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive"
                    />
                    <p
                        v-if="form.errors.message"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.message }}
                    </p>
                </div>

                <Button
                    type="submit"
                    class="w-full gap-2 bg-brand-600 py-5 text-[15px] hover:bg-brand-700"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Sending…' : 'Send Message' }}
                    <Send class="size-4" />
                </Button>
            </form>
        </Reveal>
    </section>
</template>
