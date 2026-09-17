<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Bot,
    Calendar,
    FileText,
    FolderOpen,
    MessageCircle,
    Pill,
    Shield,
    Sprout,
    Star,
} from '@lucide/vue';
import Reveal from '@/components/public/Reveal.vue';
import { useCountUp } from '@/composables/useCountUp';

interface DoctorCard {
    id: number;
    name: string;
    category: string;
    icon: string;
    consultancy_fee: number;
    avg_rating: number;
    rating_count: number;
}
interface CategoryCard {
    id: number;
    name: string;
    icon: string;
}

const props = defineProps<{
    stats: { doctors: number; patients: number; completed: number };
    categories: CategoryCard[];
    featuredDoctors: DoctorCard[];
}>();

const doctorsCount = useCountUp(props.stats.doctors);
const patientsCount = useCountUp(props.stats.patients, { duration: 2000 });
const completedCount = useCountUp(props.stats.completed, { duration: 2200 });

const whyFeatures = [
    {
        icon: Calendar,
        title: 'Smart Appointment Booking',
        desc: 'Choose specialty → doctor → time slot. Instant confirmation.',
    },
    {
        icon: Bot,
        title: 'AI Symptom Checker',
        desc: 'Describe symptoms and get instant specialist recommendations.',
    },
    {
        icon: Pill,
        title: 'AI Prescription Explainer',
        desc: 'AI explains every medicine in simple language after your visit.',
    },
    {
        icon: Shield,
        title: 'Health Risk Assessment',
        desc: 'AI analyses your history to generate a personalised risk score.',
    },
    {
        icon: Sprout,
        title: 'Personalized Health Plan',
        desc: 'AI creates a weekly diet, exercise & lifestyle plan for you.',
    },
    {
        icon: MessageCircle,
        title: 'Secure Doctor Chat',
        desc: 'Message your doctor directly. Fast follow-ups without another visit.',
    },
    {
        icon: FileText,
        title: 'Prescription & Invoice PDF',
        desc: 'Download your prescription and invoice as PDF anytime.',
    },
    {
        icon: FolderOpen,
        title: 'Digital Health Records',
        desc: 'Upload lab reports, X-rays, scans. Full history in one place.',
    },
];

function fmtTaka(n: number) {
    return new Intl.NumberFormat('en-BD').format(Math.round(n));
}
</script>

<template>
    <Head title="Home" />

    <!-- ══════════ HERO ══════════ -->
    <section
        class="relative overflow-hidden bg-gradient-to-b from-[#f0f5ff] via-[#eef6f3] to-white pt-36 pb-24 sm:pt-40"
    >
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div
                class="absolute -top-24 -left-24 size-96 animate-blob rounded-full bg-brand-300/30 blur-3xl"
            />
            <div
                class="absolute top-1/3 -right-32 size-[28rem] animate-blob rounded-full bg-sky-300/30 blur-3xl"
                style="animation-delay: -7s"
            />
            <div
                class="absolute -bottom-32 left-1/4 size-96 animate-blob rounded-full bg-brand-200/30 blur-3xl"
                style="animation-delay: -13s"
            />
        </div>

        <div class="relative mx-auto max-w-4xl px-4 text-center sm:px-6">
            <Reveal :duration="600">
                <span
                    class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-white/80 px-4 py-1.5 text-[13px] font-medium text-brand-700 shadow-sm backdrop-blur-sm"
                >
                    🇧🇩 Digital Hospital Management · Dhaka, Bangladesh
                </span>
            </Reveal>

            <Reveal :delay="80">
                <h1
                    class="mt-7 font-heading text-4xl leading-[1.08] font-extrabold tracking-tight text-ink-900 sm:text-5xl lg:text-6xl"
                >
                    Expert Doctors,<br />
                    <span
                        class="text-gradient-brand inline-block animate-gradient"
                        >Zero Waiting</span
                    >
                </h1>
            </Reveal>

            <Reveal :delay="160">
                <p
                    class="mx-auto mt-6 max-w-xl text-base leading-relaxed text-ink-600 sm:text-lg"
                >
                    Book specialist appointments online, get AI-powered symptom
                    analysis, understand your prescriptions, and manage your
                    health records — from anywhere in Bangladesh.
                </p>
            </Reveal>

            <Reveal :delay="240">
                <div
                    class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row"
                >
                    <Link
                        href="/login"
                        class="group inline-flex items-center gap-2 rounded-full bg-brand-600 px-7 py-3.5 text-[15px] font-semibold text-white shadow-lg shadow-brand-600/30 transition-all hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-xl hover:shadow-brand-600/40"
                    >
                        <Calendar class="size-[18px]" />
                        Book Appointment
                        <ArrowRight
                            class="size-4 transition-transform group-hover:translate-x-1"
                        />
                    </Link>
                    <Link
                        href="/about"
                        class="inline-flex items-center gap-2 rounded-full border border-ink-200 bg-white/70 px-7 py-3.5 text-[15px] font-semibold text-ink-700 backdrop-blur-sm transition-colors hover:bg-white"
                    >
                        Learn More
                    </Link>
                </div>
            </Reveal>

            <Reveal :delay="320" direction="scale">
                <dl class="mx-auto mt-16 grid max-w-2xl grid-cols-3 gap-4">
                    <div
                        :ref="
                            (el) =>
                                (doctorsCount.el.value =
                                    el as HTMLElement | null)
                        "
                        class="text-center"
                    >
                        <dt
                            class="font-mono-brand text-3xl font-bold text-ink-900 sm:text-4xl"
                        >
                            {{ doctorsCount.value }}+
                        </dt>
                        <dd class="mt-1 text-[13px] font-medium text-ink-500">
                            Specialist Doctors
                        </dd>
                    </div>
                    <div
                        :ref="
                            (el) =>
                                (patientsCount.el.value =
                                    el as HTMLElement | null)
                        "
                        class="text-center"
                    >
                        <dt
                            class="font-mono-brand text-3xl font-bold text-ink-900 sm:text-4xl"
                        >
                            {{ patientsCount.value }}+
                        </dt>
                        <dd class="mt-1 text-[13px] font-medium text-ink-500">
                            Registered Patients
                        </dd>
                    </div>
                    <div
                        :ref="
                            (el) =>
                                (completedCount.el.value =
                                    el as HTMLElement | null)
                        "
                        class="text-center"
                    >
                        <dt
                            class="font-mono-brand text-3xl font-bold text-ink-900 sm:text-4xl"
                        >
                            {{ completedCount.value }}+
                        </dt>
                        <dd class="mt-1 text-[13px] font-medium text-ink-500">
                            Completed Visits
                        </dd>
                    </div>
                </dl>
            </Reveal>
        </div>
    </section>

    <!-- ══════════ FEATURED DOCTORS ══════════ -->
    <section
        v-if="featuredDoctors.length"
        class="mx-auto max-w-6xl px-4 py-20 sm:px-6"
    >
        <Reveal class="mb-10 flex items-end justify-between">
            <h2
                class="font-heading text-2xl font-bold text-ink-900 sm:text-3xl"
            >
                Our Specialist Doctors
            </h2>
            <Link
                href="/login"
                class="text-sm font-semibold text-brand-600 hover:text-brand-700"
            >
                View All →
            </Link>
        </Reveal>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Reveal
                v-for="(doc, i) in featuredDoctors"
                :key="doc.id"
                :delay="i * 70"
                class="group rounded-2xl border border-ink-100 bg-white p-5 shadow-sm transition-all hover:-translate-y-1 hover:border-brand-200 hover:shadow-lg hover:shadow-ink-900/5"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-xl ring-1 ring-brand-100 transition-transform group-hover:scale-110"
                    >
                        {{ doc.icon }}
                    </div>
                    <div class="min-w-0">
                        <div
                            class="truncate text-[15px] font-semibold text-ink-900"
                        >
                            {{ doc.name }}
                        </div>
                        <div class="text-[13px] text-ink-500">
                            {{ doc.category }}
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between text-sm">
                    <span
                        v-if="doc.avg_rating > 0"
                        class="flex items-center gap-1 text-amber-500"
                    >
                        <Star
                            class="size-[15px] fill-amber-400 text-amber-400"
                        />
                        <span class="font-semibold">{{ doc.avg_rating }}</span>
                        <span class="text-ink-400"
                            >({{ doc.rating_count }})</span
                        >
                    </span>
                    <span v-else class="text-[13px] text-ink-400"
                        >No reviews yet</span
                    >
                    <span class="font-mono-brand font-bold text-brand-600"
                        >৳{{ fmtTaka(doc.consultancy_fee) }}</span
                    >
                </div>
            </Reveal>
        </div>
    </section>

    <!-- ══════════ SPECIALTIES ══════════ -->
    <section v-if="categories.length" class="bg-ink-50/60 py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <Reveal class="mb-10">
                <h2
                    class="font-heading text-2xl font-bold text-ink-900 sm:text-3xl"
                >
                    Browse by Specialty
                </h2>
            </Reveal>
            <div
                class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6"
            >
                <Reveal
                    v-for="(cat, i) in categories"
                    :key="cat.id"
                    :delay="i * 40"
                >
                    <Link
                        href="/login"
                        class="group flex flex-col items-center gap-2.5 rounded-2xl border border-ink-100 bg-white px-3 py-6 text-center shadow-sm transition-all hover:-translate-y-1 hover:border-brand-300 hover:shadow-md"
                    >
                        <span
                            class="text-2xl transition-transform group-hover:scale-110"
                            >{{ cat.icon }}</span
                        >
                        <span
                            class="text-[13px] leading-tight font-semibold text-ink-800"
                            >{{ cat.name }}</span
                        >
                    </Link>
                </Reveal>
            </div>
        </div>
    </section>

    <!-- ══════════ WHY MEDICARE ══════════ -->
    <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
        <Reveal class="mb-12 text-center">
            <h2
                class="font-heading text-2xl font-bold text-ink-900 sm:text-3xl"
            >
                Why MediCare?
            </h2>
        </Reveal>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <Reveal
                v-for="(f, i) in whyFeatures"
                :key="f.title"
                :delay="(i % 4) * 90"
                class="group rounded-2xl border border-ink-100 bg-white p-6 shadow-sm transition-all hover:-translate-y-1.5 hover:border-brand-200 hover:shadow-lg hover:shadow-ink-900/5"
            >
                <div
                    class="mb-4 flex size-11 items-center justify-center rounded-xl bg-gradient-to-br from-brand-50 to-sky-50 text-brand-600 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6"
                >
                    <component :is="f.icon" class="size-5" />
                </div>
                <h3 class="mb-1.5 text-[15px] font-semibold text-ink-900">
                    {{ f.title }}
                </h3>
                <p class="text-[13.5px] leading-relaxed text-ink-500">
                    {{ f.desc }}
                </p>
            </Reveal>
        </div>
    </section>
</template>
