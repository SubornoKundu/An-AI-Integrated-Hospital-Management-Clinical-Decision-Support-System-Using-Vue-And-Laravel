<script setup lang="ts">
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import {
    Building2,
    Eye,
    EyeOff,
    Lock,
    Mail,
    Phone,
    Shield,
    Stethoscope,
    User,
    VenusAndMars,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Toaster } from '@/components/ui/sonner';

const props = defineProps<{
    initialView?: 'login' | 'register';
}>();

const page = usePage();
const hospital = computed(() => page.props.hospital);

type Role = 'patient' | 'doctor' | 'admin';
type ViewName = 'login' | 'register' | 'forgot';

const activeView = ref<ViewName>(props.initialView ?? 'login');
const activeRole = ref<Role>('patient');

const roles: { key: Role; label: string; icon: unknown }[] = [
    { key: 'patient', label: 'Patient', icon: Building2 },
    { key: 'doctor', label: 'Doctor', icon: Stethoscope },
    { key: 'admin', label: 'Admin', icon: Shield },
];
const roleIndex = computed(() =>
    roles.findIndex((r) => r.key === activeRole.value),
);

// Persistent inline banner as a backup to the toast — shows the actual
// reason a login attempt failed (wrong password, no account, locked out,
// etc.) directly on the form, so it can't be missed or dismissed away.
const loginErrorMessage = computed(() => {
    const toastData = page.props.flash?.toast;

    if (toastData?.type === 'error' && page.props.flash?.error_tab) {
        return toastData.message;
    }

    return null;
});

// Same flash mechanism as loginErrorMessage, shown on the forgot-password
// panel instead — the backend reuses 'error' + 'error_tab' for both flows.
const forgotErrorMessage = computed(() => {
    if (activeView.value !== 'forgot') {
        return null;
    }

    const toastData = page.props.flash?.toast;

    if (toastData?.type === 'error' && page.props.flash?.error_tab) {
        return toastData.message;
    }

    return null;
});

onMounted(() => {
    const errorTab = page.props.flash?.error_tab;

    if (errorTab) {
        activeView.value = 'login';
        activeRole.value = errorTab;

        return;
    }

    const validationErrors = page.props.errors as
        Record<string, string> | undefined;

    if (validationErrors && 'first_name' in validationErrors) {
        activeView.value = 'register';
    }
});

// ── Password visibility ────────────────────────────────────────────────
const showPw = ref<Record<string, boolean>>({});
function toggle(key: string) {
    showPw.value[key] = !showPw.value[key];
}

// ── Forms ────────────────────────────────────────────────────────────
const patientForm = useForm({ email: '', password: '', remember: false });
const doctorForm = useForm({ email: '', password: '', remember: false });
const adminForm = useForm({ email: '', password: '' });
const registerForm = useForm({
    first_name: '',
    last_name: '',
    email: '',
    contact: '',
    gender: '',
    password: '',
    password_confirmation: '',
});

function submitPatient() {
    patientForm.post('/login/patient');
}
function submitDoctor() {
    doctorForm.post('/login/doctor');
}
function submitAdmin() {
    adminForm.post('/login/admin');
}
function submitRegister() {
    registerForm.post('/register/patient');
}

// ── Forgot password (email code) ───────────────────────────────
const forgotStep = ref<'request' | 'verify' | 'reset'>('request');

const sendCodeForm = useForm({ email: '', role: 'patient' as Role });
const verifyForm = useForm({ email: '', role: 'patient' as Role, code: '' });
const resetPwForm = useForm({
    email: '',
    role: 'patient' as Role,
    code: '',
    password: '',
    password_confirmation: '',
});

function openForgotPassword() {
    forgotStep.value = 'request';
    sendCodeForm.reset();
    sendCodeForm.clearErrors();
    activeView.value = 'forgot';
}

function submitSendCode() {
    sendCodeForm.role = activeRole.value;
    sendCodeForm.post('/forgot-password/send-code', {
        preserveScroll: true,
        onSuccess: () => {
            verifyForm.email = sendCodeForm.email;
            verifyForm.role = sendCodeForm.role;
            verifyForm.code = '';
            forgotStep.value = 'verify';
        },
    });
}

function resendCode() {
    sendCodeForm.post('/forgot-password/send-code', { preserveScroll: true });
}

function submitVerifyCode() {
    verifyForm.post('/forgot-password/verify-code', {
        preserveScroll: true,
        onSuccess: () => {
            resetPwForm.email = verifyForm.email;
            resetPwForm.role = verifyForm.role;
            resetPwForm.code = verifyForm.code;
            forgotStep.value = 'reset';
        },
    });
}

function submitResetPassword() {
    resetPwForm.post('/forgot-password/reset', {
        onSuccess: () => {
            activeView.value = 'login';
        },
    });
}
</script>

<template>
    <Head title="Sign In" />

    <div class="flex min-h-screen flex-col bg-ink-50/50 font-heading">
        <!-- Topbar -->
        <div
            class="flex items-center justify-between border-b border-ink-100 bg-white px-5 py-3.5 shadow-sm"
        >
            <Link href="/" class="flex items-center gap-2.5">
                <span
                    v-if="hospital?.logo"
                    class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-lg bg-white ring-1 ring-ink-200"
                >
                    <img
                        :src="hospital.logo"
                        :alt="hospital.name"
                        class="h-full w-full object-contain"
                    />
                </span>
                <span
                    v-else
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-sky-600 text-sm"
                >
                    🏥
                </span>
                <span class="text-[15px] font-bold text-ink-900">{{
                    hospital?.name ?? 'MediCare Hospital'
                }}</span>
            </Link>
            <Link
                href="/"
                class="text-sm text-ink-400 transition-colors hover:text-ink-700"
                >← Home</Link
            >
        </div>

        <!-- Main -->
        <div
            class="flex flex-1 items-start justify-center px-4 py-8 sm:items-center"
        >
            <div
                class="w-full max-w-[440px] overflow-hidden rounded-[20px] border border-ink-100 bg-white shadow-[0_8px_32px_rgba(16,31,61,0.1)]"
            >
                <div
                    class="h-[3px] bg-gradient-to-r from-brand-500 to-sky-500"
                />

                <Transition name="view-swap" mode="out-in">
                    <!-- ══ LOGIN ══ -->
                    <div
                        v-if="activeView === 'login'"
                        key="login"
                        class="p-7 sm:p-8"
                    >
                        <div class="mb-5">
                            <h2 class="text-xl font-bold text-ink-900">
                                Welcome back 👋
                            </h2>
                            <p class="mt-0.5 text-[13px] text-ink-400">
                                Sign in to your MediCare account
                            </p>
                        </div>

                        <!-- Role tabs -->
                        <div
                            class="relative mb-6 grid grid-cols-3 gap-1 rounded-[10px] border border-ink-100 bg-ink-50 p-1"
                        >
                            <div
                                class="absolute top-1 bottom-1 rounded-lg bg-white shadow-sm transition-transform duration-300 ease-out"
                                style="width: calc((100% - 8px) / 3)"
                                :style="{
                                    transform: `translateX(calc(${roleIndex} * (100% + 4px)))`,
                                }"
                            />
                            <button
                                v-for="r in roles"
                                :key="r.key"
                                type="button"
                                class="relative z-10 flex items-center justify-center gap-1.5 rounded-lg py-2 text-[13px] font-semibold transition-colors"
                                :class="
                                    activeRole === r.key
                                        ? 'text-ink-900'
                                        : 'text-ink-400 hover:text-ink-600'
                                "
                                @click="activeRole = r.key"
                            >
                                <component :is="r.icon" class="size-3.5" />
                                {{ r.label }}
                            </button>
                        </div>

                        <div
                            v-if="loginErrorMessage"
                            class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-[12.5px] text-red-600"
                        >
                            <span class="mt-0.5">⚠️</span>
                            <span>{{ loginErrorMessage }}</span>
                        </div>

                        <Transition name="fade-quick" mode="out-in">
                            <!-- Patient login -->
                            <form
                                v-if="activeRole === 'patient'"
                                key="patient"
                                class="space-y-3.5"
                                @submit.prevent="submitPatient"
                            >
                                <div class="space-y-1.5">
                                    <Label class="gap-1.5 text-ink-600"
                                        ><Mail class="size-3.5 text-ink-300" />
                                        Email Address</Label
                                    >
                                    <Input
                                        v-model="patientForm.email"
                                        type="email"
                                        placeholder="Enter your email"
                                        :aria-invalid="
                                            !!patientForm.errors.email
                                        "
                                        required
                                    />
                                    <p
                                        v-if="patientForm.errors.email"
                                        class="text-xs text-destructive"
                                    >
                                        {{ patientForm.errors.email }}
                                    </p>
                                </div>
                                <div class="space-y-1.5">
                                    <Label class="text-ink-600">Password</Label>
                                    <div class="relative">
                                        <Input
                                            v-model="patientForm.password"
                                            :type="
                                                showPw.p1 ? 'text' : 'password'
                                            "
                                            placeholder="Enter your password"
                                            class="pr-9"
                                            required
                                        />
                                        <button
                                            type="button"
                                            class="absolute top-1/2 right-2.5 -translate-y-1/2 text-ink-300 hover:text-ink-500"
                                            @click="toggle('p1')"
                                        >
                                            <component
                                                :is="showPw.p1 ? EyeOff : Eye"
                                                class="size-4"
                                            />
                                        </button>
                                    </div>
                                    <p
                                        v-if="patientForm.errors.password"
                                        class="text-xs text-destructive"
                                    >
                                        {{ patientForm.errors.password }}
                                    </p>
                                </div>
                                <div class="!mt-1.5 text-right">
                                    <button
                                        type="button"
                                        class="text-[12.5px] font-semibold text-brand-600 hover:underline"
                                        @click="openForgotPassword"
                                    >
                                        Forgot password?
                                    </button>
                                </div>
                                <Button
                                    type="submit"
                                    class="w-full bg-gradient-to-r from-brand-500 to-brand-600 py-5 shadow-md shadow-brand-500/25 hover:from-brand-600 hover:to-brand-700"
                                    :disabled="patientForm.processing"
                                >
                                    Sign In →
                                </Button>
                                <p
                                    class="pt-2 text-center text-[13px] text-ink-400"
                                >
                                    Don't have an account?
                                    <button
                                        type="button"
                                        class="font-semibold text-brand-600 hover:underline"
                                        @click="activeView = 'register'"
                                    >
                                        Create Account
                                    </button>
                                </p>
                            </form>

                            <!-- Doctor login -->
                            <form
                                v-else-if="activeRole === 'doctor'"
                                key="doctor"
                                class="space-y-3.5"
                                @submit.prevent="submitDoctor"
                            >
                                <div class="space-y-1.5">
                                    <Label class="gap-1.5 text-ink-600"
                                        ><Mail class="size-3.5 text-ink-300" />
                                        Doctor Email</Label
                                    >
                                    <Input
                                        v-model="doctorForm.email"
                                        type="email"
                                        placeholder="Enter your email"
                                        :aria-invalid="
                                            !!doctorForm.errors.email
                                        "
                                        required
                                    />
                                    <p
                                        v-if="doctorForm.errors.email"
                                        class="text-xs text-destructive"
                                    >
                                        {{ doctorForm.errors.email }}
                                    </p>
                                </div>
                                <div class="space-y-1.5">
                                    <Label class="text-ink-600">Password</Label>
                                    <div class="relative">
                                        <Input
                                            v-model="doctorForm.password"
                                            :type="
                                                showPw.p2 ? 'text' : 'password'
                                            "
                                            placeholder="Enter your password"
                                            class="pr-9"
                                            required
                                        />
                                        <button
                                            type="button"
                                            class="absolute top-1/2 right-2.5 -translate-y-1/2 text-ink-300 hover:text-ink-500"
                                            @click="toggle('p2')"
                                        >
                                            <component
                                                :is="showPw.p2 ? EyeOff : Eye"
                                                class="size-4"
                                            />
                                        </button>
                                    </div>
                                    <p
                                        v-if="doctorForm.errors.password"
                                        class="text-xs text-destructive"
                                    >
                                        {{ doctorForm.errors.password }}
                                    </p>
                                </div>
                                <div class="!mt-1.5 text-right">
                                    <button
                                        type="button"
                                        class="text-[12.5px] font-semibold text-sky-600 hover:underline"
                                        @click="openForgotPassword"
                                    >
                                        Forgot password?
                                    </button>
                                </div>
                                <Button
                                    type="submit"
                                    class="!mt-5 w-full bg-gradient-to-r from-sky-500 to-sky-600 py-5 shadow-md shadow-sky-500/25 hover:from-sky-600 hover:to-sky-700"
                                    :disabled="doctorForm.processing"
                                >
                                    Sign In as Doctor →
                                </Button>
                            </form>

                            <!-- Admin login -->
                            <form
                                v-else
                                key="admin"
                                class="space-y-3.5"
                                @submit.prevent="submitAdmin"
                            >
                                <div class="space-y-1.5">
                                    <Label class="gap-1.5 text-ink-600"
                                        ><Shield
                                            class="size-3.5 text-ink-300"
                                        />
                                        Admin Email</Label
                                    >
                                    <Input
                                        v-model="adminForm.email"
                                        type="email"
                                        placeholder="Enter your email"
                                        :aria-invalid="
                                            !!adminForm.errors.email
                                        "
                                        required
                                    />
                                    <p
                                        v-if="adminForm.errors.email"
                                        class="text-xs text-destructive"
                                    >
                                        {{ adminForm.errors.email }}
                                    </p>
                                </div>
                                <div class="space-y-1.5">
                                    <Label class="text-ink-600">Password</Label>
                                    <div class="relative">
                                        <Input
                                            v-model="adminForm.password"
                                            :type="
                                                showPw.p3 ? 'text' : 'password'
                                            "
                                            placeholder="Enter your password"
                                            class="pr-9"
                                            required
                                        />
                                        <button
                                            type="button"
                                            class="absolute top-1/2 right-2.5 -translate-y-1/2 text-ink-300 hover:text-ink-500"
                                            @click="toggle('p3')"
                                        >
                                            <component
                                                :is="showPw.p3 ? EyeOff : Eye"
                                                class="size-4"
                                            />
                                        </button>
                                    </div>
                                    <p
                                        v-if="adminForm.errors.password"
                                        class="text-xs text-destructive"
                                    >
                                        {{ adminForm.errors.password }}
                                    </p>
                                </div>
                                <div class="!mt-1.5 text-right">
                                    <button
                                        type="button"
                                        class="text-[12.5px] font-semibold text-red-600 hover:underline"
                                        @click="openForgotPassword"
                                    >
                                        Forgot password?
                                    </button>
                                </div>
                                <Button
                                    type="submit"
                                    class="!mt-5 w-full bg-gradient-to-r from-red-500 to-red-600 py-5 shadow-md shadow-red-500/25 hover:from-red-600 hover:to-red-700"
                                    :disabled="adminForm.processing"
                                >
                                    Sign In as Admin →
                                </Button>
                            </form>
                        </Transition>
                    </div>

                    <!-- ══ REGISTER ══ -->
                    <div
                        v-else-if="activeView === 'register'"
                        key="register"
                        class="p-7 sm:p-8"
                    >
                        <div class="mb-5">
                            <h2 class="text-xl font-bold text-ink-900">
                                Create account 🎉
                            </h2>
                            <p class="mt-0.5 text-[13px] text-ink-400">
                                Join MediCare — free for patients
                            </p>
                        </div>

                        <form
                            class="space-y-3.5"
                            @submit.prevent="submitRegister"
                        >
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <Label class="gap-1.5 text-ink-600"
                                        ><User class="size-3.5 text-ink-300" />
                                        First Name</Label
                                    >
                                    <Input
                                        v-model="registerForm.first_name"
                                        placeholder="First name"
                                        :aria-invalid="
                                            !!registerForm.errors.first_name
                                        "
                                        required
                                    />
                                    <p
                                        v-if="registerForm.errors.first_name"
                                        class="text-xs text-destructive"
                                    >
                                        {{ registerForm.errors.first_name }}
                                    </p>
                                </div>
                                <div class="space-y-1.5">
                                    <Label class="gap-1.5 text-ink-600"
                                        ><User class="size-3.5 text-ink-300" />
                                        Last Name</Label
                                    >
                                    <Input
                                        v-model="registerForm.last_name"
                                        placeholder="Last name"
                                        :aria-invalid="
                                            !!registerForm.errors.last_name
                                        "
                                        required
                                    />
                                    <p
                                        v-if="registerForm.errors.last_name"
                                        class="text-xs text-destructive"
                                    >
                                        {{ registerForm.errors.last_name }}
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <Label class="gap-1.5 text-ink-600"
                                    ><Mail class="size-3.5 text-ink-300" />
                                    Email Address</Label
                                >
                                <Input
                                    v-model="registerForm.email"
                                    type="email"
                                    placeholder="you@email.com"
                                    :aria-invalid="!!registerForm.errors.email"
                                    required
                                />
                                <p
                                    v-if="registerForm.errors.email"
                                    class="text-xs text-destructive"
                                >
                                    {{ registerForm.errors.email }}
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <Label class="gap-1.5 text-ink-600"
                                        ><Phone class="size-3.5 text-ink-300" />
                                        Phone</Label
                                    >
                                    <Input
                                        v-model="registerForm.contact"
                                        placeholder="01XXXXXXXXX"
                                        :aria-invalid="
                                            !!registerForm.errors.contact
                                        "
                                        required
                                    />
                                    <p
                                        v-if="registerForm.errors.contact"
                                        class="text-xs text-destructive"
                                    >
                                        {{ registerForm.errors.contact }}
                                    </p>
                                </div>
                                <div class="space-y-1.5">
                                    <Label class="gap-1.5 text-ink-600"
                                        ><VenusAndMars
                                            class="size-3.5 text-ink-300"
                                        />
                                        Gender</Label
                                    >
                                    <select
                                        v-model="registerForm.gender"
                                        required
                                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive"
                                        :aria-invalid="
                                            !!registerForm.errors.gender
                                        "
                                    >
                                        <option value="">Select</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <p
                                        v-if="registerForm.errors.gender"
                                        class="text-xs text-destructive"
                                    >
                                        {{ registerForm.errors.gender }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <Label class="gap-1.5 text-ink-600"
                                        ><Lock class="size-3.5 text-ink-300" />
                                        Password</Label
                                    >
                                    <div class="relative">
                                        <Input
                                            v-model="registerForm.password"
                                            :type="
                                                showPw.p4 ? 'text' : 'password'
                                            "
                                            placeholder="Min 8 chars"
                                            class="pr-9"
                                            required
                                        />
                                        <button
                                            type="button"
                                            class="absolute top-1/2 right-2.5 -translate-y-1/2 text-ink-300 hover:text-ink-500"
                                            @click="toggle('p4')"
                                        >
                                            <component
                                                :is="showPw.p4 ? EyeOff : Eye"
                                                class="size-4"
                                            />
                                        </button>
                                    </div>
                                    <p
                                        v-if="registerForm.errors.password"
                                        class="text-xs text-destructive"
                                    >
                                        {{ registerForm.errors.password }}
                                    </p>
                                </div>
                                <div class="space-y-1.5">
                                    <Label class="text-ink-600"
                                        >Confirm Password</Label
                                    >
                                    <div class="relative">
                                        <Input
                                            v-model="
                                                registerForm.password_confirmation
                                            "
                                            :type="
                                                showPw.p5 ? 'text' : 'password'
                                            "
                                            placeholder="Repeat"
                                            class="pr-9"
                                            required
                                        />
                                        <button
                                            type="button"
                                            class="absolute top-1/2 right-2.5 -translate-y-1/2 text-ink-300 hover:text-ink-500"
                                            @click="toggle('p5')"
                                        >
                                            <component
                                                :is="showPw.p5 ? EyeOff : Eye"
                                                class="size-4"
                                            />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <Button
                                type="submit"
                                class="w-full bg-gradient-to-r from-brand-500 to-brand-600 py-5 shadow-md shadow-brand-500/25 hover:from-brand-600 hover:to-brand-700"
                                :disabled="registerForm.processing"
                            >
                                Create Account →
                            </Button>
                        </form>
                        <p class="pt-3 text-center text-[13px] text-ink-400">
                            Already have an account?
                            <button
                                type="button"
                                class="font-semibold text-brand-600 hover:underline"
                                @click="activeView = 'login'"
                            >
                                Sign In
                            </button>
                        </p>
                    </div>

                    <!-- ══ FORGOT ══ -->
                    <div v-else key="forgot" class="p-7 sm:p-8">
                        <div class="mb-5">
                            <h2 class="text-xl font-bold text-ink-900">
                                Forgot password? 🔑
                            </h2>
                            <p class="mt-0.5 text-[13px] text-ink-400">
                                {{
                                    forgotStep === 'request'
                                        ? "We'll email you a reset code"
                                        : forgotStep === 'verify'
                                          ? `Enter the code sent to ${sendCodeForm.email}`
                                          : 'Choose your new password'
                                }}
                            </p>
                        </div>

                        <div
                            v-if="forgotErrorMessage"
                            class="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-[12.5px] text-red-600"
                        >
                            <span class="mt-0.5">⚠️</span>
                            <span>{{ forgotErrorMessage }}</span>
                        </div>

                        <!-- Step 1: request code -->
                        <form
                            v-if="forgotStep === 'request'"
                            class="space-y-3.5"
                            @submit.prevent="submitSendCode"
                        >
                            <div class="space-y-1.5">
                                <Label
                                    class="flex items-center gap-1.5 text-[12.5px]"
                                    ><Mail class="size-3.5" />
                                    {{
                                        activeRole === 'admin'
                                            ? 'Admin Email'
                                            : activeRole === 'doctor'
                                              ? 'Doctor Email'
                                              : 'Email Address'
                                    }}</Label
                                >
                                <Input
                                    v-model="sendCodeForm.email"
                                    type="email"
                                    placeholder="Enter your email"
                                    required
                                />
                                <p
                                    v-if="sendCodeForm.errors.email"
                                    class="text-xs text-destructive"
                                >
                                    {{ sendCodeForm.errors.email }}
                                </p>
                            </div>
                            <Button
                                type="submit"
                                class="w-full bg-gradient-to-r from-brand-500 to-brand-600 py-5"
                                :disabled="sendCodeForm.processing"
                            >
                                Send Reset Code →
                            </Button>
                        </form>

                        <!-- Step 2: verify code -->
                        <form
                            v-else-if="forgotStep === 'verify'"
                            class="space-y-3.5"
                            @submit.prevent="submitVerifyCode"
                        >
                            <div class="space-y-1.5">
                                <Label class="text-[12.5px]"
                                    >6-Digit Code</Label
                                >
                                <Input
                                    v-model="verifyForm.code"
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="6"
                                    placeholder="123456"
                                    class="text-center text-lg tracking-[0.5em]"
                                    required
                                />
                                <p
                                    v-if="verifyForm.errors.code"
                                    class="text-xs text-destructive"
                                >
                                    {{ verifyForm.errors.code }}
                                </p>
                            </div>
                            <Button
                                type="submit"
                                class="w-full bg-gradient-to-r from-brand-500 to-brand-600 py-5"
                                :disabled="verifyForm.processing"
                            >
                                Verify Code →
                            </Button>
                            <p class="text-center text-[12.5px] text-ink-400">
                                Didn't get it?
                                <button
                                    type="button"
                                    class="font-semibold text-brand-600 hover:underline"
                                    :disabled="sendCodeForm.processing"
                                    @click="resendCode"
                                >
                                    Resend code
                                </button>
                            </p>
                        </form>

                        <!-- Step 3: set new password -->
                        <form
                            v-else
                            class="space-y-3.5"
                            @submit.prevent="submitResetPassword"
                        >
                            <div class="space-y-1.5">
                                <Label class="text-[12.5px]"
                                    >New Password</Label
                                >
                                <PasswordInput
                                    v-model="resetPwForm.password"
                                    placeholder="Enter your password"
                                    required
                                />
                                <p
                                    v-if="resetPwForm.errors.password"
                                    class="text-xs text-destructive"
                                >
                                    {{ resetPwForm.errors.password }}
                                </p>
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-[12.5px]"
                                    >Confirm Password</Label
                                >
                                <PasswordInput
                                    v-model="resetPwForm.password_confirmation"
                                    placeholder="Re-enter your password"
                                    required
                                />
                            </div>
                            <Button
                                type="submit"
                                class="w-full bg-gradient-to-r from-brand-500 to-brand-600 py-5"
                                :disabled="resetPwForm.processing"
                            >
                                Reset Password →
                            </Button>
                        </form>

                        <p class="pt-3 text-center text-[13px] text-ink-400">
                            <button
                                type="button"
                                class="font-semibold text-brand-600 hover:underline"
                                @click="activeView = 'login'"
                            >
                                ← Back to Sign In
                            </button>
                        </p>
                    </div>
                </Transition>
            </div>
        </div>

        <!-- Page footer -->
        <div
            class="border-t border-ink-100 bg-white py-3.5 text-center text-xs text-ink-300"
        >
            © {{ new Date().getFullYear() }}
            {{ hospital?.name ?? 'MediCare Hospital' }} · Dhaka, Bangladesh ·
            <Link href="/" class="text-brand-600 hover:underline">Home</Link>
            ·
            <Link href="/contact" class="text-brand-600 hover:underline"
                >Contact</Link
            >
        </div>
    </div>

    <Toaster position="top-right" rich-colors />
</template>

<style scoped>
.view-swap-enter-active,
.view-swap-leave-active {
    transition:
        opacity 0.22s ease,
        transform 0.22s ease;
}
.view-swap-enter-from {
    opacity: 0;
    transform: translateY(8px);
}
.view-swap-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
.fade-quick-enter-active,
.fade-quick-leave-active {
    transition: opacity 0.15s ease;
}
.fade-quick-enter-from,
.fade-quick-leave-to {
    opacity: 0;
}
</style>
