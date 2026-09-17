<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Bot, Check, Target } from '@lucide/vue';
import Reveal from '@/components/public/Reveal.vue';

const stats = [
    ['50+', 'Specialist Doctors'],
    ['15+', 'Specialties'],
    ['10,000+', 'Patients Served'],
    ['24/7', 'Online Platform'],
];

const whyChoose = [
    'Online specialist booking',
    'AI-powered health tools',
    'Digital prescriptions & PDF download',
    'Secure patient-doctor chat',
    'Full medical history tracking',
    'Health documents storage',
    'Doctor rating system',
    'Mobile-friendly design',
];
</script>

<template>
    <Head title="About" />

    <section
        class="bg-gradient-to-b from-[#f0f5ff] to-white pt-32 pb-16 sm:pt-36"
    >
        <div class="mx-auto max-w-3xl px-4 text-center sm:px-6">
            <Reveal>
                <h1
                    class="font-heading text-3xl font-extrabold text-ink-900 sm:text-4xl"
                >
                    About <span class="text-gradient-brand">MediCare</span>
                </h1>
                <p class="mt-3 text-base text-ink-500">
                    Quality healthcare, delivered with technology and compassion
                </p>
            </Reveal>

            <Reveal :delay="120" direction="scale">
                <dl class="mt-12 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div
                        v-for="[n, l] in stats"
                        :key="l"
                        class="rounded-2xl border border-ink-100 bg-white px-3 py-5 shadow-sm"
                    >
                        <dt
                            class="font-mono-brand text-2xl font-extrabold text-brand-600"
                        >
                            {{ n }}
                        </dt>
                        <dd
                            class="mt-1 text-[12.5px] font-semibold text-ink-500"
                        >
                            {{ l }}
                        </dd>
                    </div>
                </dl>
            </Reveal>
        </div>
    </section>

    <section class="mx-auto max-w-3xl space-y-5 px-4 pb-24 sm:px-6">
        <Reveal
            class="rounded-2xl border border-ink-100 bg-white p-7 shadow-sm"
        >
            <h2
                class="mb-3 flex items-center gap-2 text-[15px] font-bold text-ink-900"
            >
                <Target class="size-[18px] text-brand-600" /> Our Mission
            </h2>
            <p class="text-[14.5px] leading-relaxed text-ink-600">
                MediCare is committed to providing accessible, high-quality
                healthcare through a seamless digital platform. We combine
                experienced medical professionals with modern technology to
                deliver the best possible care to every patient in Bangladesh.
            </p>
        </Reveal>

        <Reveal
            :delay="100"
            class="rounded-2xl border border-brand-100 bg-gradient-to-br from-brand-50/60 to-sky-50/60 p-7 shadow-sm"
        >
            <h2
                class="mb-3 flex items-center gap-2 text-[15px] font-bold text-ink-900"
            >
                <Bot class="size-[18px] text-brand-600" /> AI-Powered Features
            </h2>
            <p class="text-[14.5px] leading-relaxed text-ink-600">
                Our platform is powered by Groq AI (LLaMA 3.3 70B) to offer
                unique health features:
                <strong class="text-brand-700">AI Symptom Checker</strong> that
                recommends the right specialist,
                <strong class="text-brand-700"
                    >AI Prescription Explainer</strong
                >
                that explains medicines in plain language,
                <strong class="text-brand-700">Health Risk Assessment</strong>
                that predicts risk scores, and
                <strong class="text-brand-700">Personalized Health Plan</strong>
                tailored to your conditions.
            </p>
        </Reveal>

        <Reveal
            :delay="180"
            class="rounded-2xl border border-ink-100 bg-white p-7 shadow-sm"
        >
            <h2 class="mb-4 text-[15px] font-bold text-ink-900">
                ✅ Why Choose MediCare?
            </h2>
            <div class="grid grid-cols-1 gap-x-6 gap-y-2.5 sm:grid-cols-2">
                <div
                    v-for="f in whyChoose"
                    :key="f"
                    class="flex items-center gap-2.5 py-1 text-[14px] text-ink-600"
                >
                    <span
                        class="flex size-5 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600"
                    >
                        <Check class="size-3.5" />
                    </span>
                    <span>{{ f }}</span>
                </div>
            </div>
        </Reveal>
    </section>
</template>
