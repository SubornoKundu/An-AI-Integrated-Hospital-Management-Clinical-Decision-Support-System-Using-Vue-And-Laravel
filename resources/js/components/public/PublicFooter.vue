<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Mail, MapPin, Phone } from '@lucide/vue';
import { computed } from 'vue';

const page = usePage();
const hospital = computed(() => page.props.hospital);
const year = new Date().getFullYear();
</script>

<template>
    <footer class="border-t border-ink-100 bg-ink-50/40">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
            <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <div class="flex items-center gap-2.5">
                        <span
                            v-if="hospital?.logo"
                            class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-lg bg-white ring-1 ring-ink-200"
                        >
                            <img
                                :src="hospital.logo"
                                :alt="hospital.name"
                                class="h-full w-full object-contain"
                            />
                        </span>
                        <span
                            v-else
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-sky-600 text-lg"
                        >
                            🏥
                        </span>
                        <span
                            class="font-heading text-lg font-bold text-ink-900"
                            >{{ hospital?.name ?? 'MediCare Hospital' }}</span
                        >
                    </div>
                    <p
                        class="mt-4 max-w-sm text-sm leading-relaxed text-ink-500"
                    >
                        Quality healthcare, delivered with technology and
                        compassion — book specialists, manage prescriptions, and
                        track your health, all in one place.
                    </p>
                </div>

                <div>
                    <h3
                        class="text-[13px] font-semibold tracking-wide text-ink-400 uppercase"
                    >
                        Quick Links
                    </h3>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li>
                            <Link
                                href="/"
                                class="text-ink-600 transition-colors hover:text-brand-600"
                                >Home</Link
                            >
                        </li>
                        <li>
                            <Link
                                href="/about"
                                class="text-ink-600 transition-colors hover:text-brand-600"
                                >About</Link
                            >
                        </li>
                        <li>
                            <Link
                                href="/contact"
                                class="text-ink-600 transition-colors hover:text-brand-600"
                                >Contact</Link
                            >
                        </li>
                        <li>
                            <Link
                                href="/login"
                                class="text-ink-600 transition-colors hover:text-brand-600"
                                >Login / Register</Link
                            >
                        </li>
                    </ul>
                </div>

                <div>
                    <h3
                        class="text-[13px] font-semibold tracking-wide text-ink-400 uppercase"
                    >
                        Contact
                    </h3>
                    <ul class="mt-4 space-y-3 text-sm text-ink-600">
                        <li
                            v-if="hospital?.address"
                            class="flex items-start gap-2.5"
                        >
                            <MapPin
                                class="mt-0.5 size-4 shrink-0 text-brand-500"
                            />
                            <span>{{ hospital.address }}</span>
                        </li>
                        <li
                            v-if="hospital?.phone"
                            class="flex items-center gap-2.5"
                        >
                            <Phone class="size-4 shrink-0 text-brand-500" />
                            <span>{{ hospital.phone }}</span>
                        </li>
                        <li
                            v-if="hospital?.email"
                            class="flex items-center gap-2.5"
                        >
                            <Mail class="size-4 shrink-0 text-brand-500" />
                            <span class="break-all">{{ hospital.email }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="mt-12 flex flex-col items-center justify-between gap-3 border-t border-ink-100 pt-6 text-[13px] text-ink-400 sm:flex-row"
            >
                <p>
                    © {{ year }} {{ hospital?.name ?? 'MediCare Hospital' }} ·
                    Dhaka, Bangladesh
                </p>
                <Link
                    href="/contact"
                    class="font-medium text-brand-600 hover:text-brand-700"
                    >Contact Us →</Link
                >
            </div>
        </div>
    </footer>
</template>
