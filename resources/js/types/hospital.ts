export type HospitalRole = 'patient' | 'doctor' | 'admin' | null;

export interface HospitalUser {
    id: number;
    name: string;
    email: string;
    photo: string | null;
}

export interface HospitalShared {
    role: HospitalRole;
    user: HospitalUser | null;
    unreadNotifications: number;
    name: string;
    logo: string | null;
    address: string;
    phone: string;
    email: string;
}

import type { FlashToast } from '@/types/ui';

export interface FlashShared {
    toast: FlashToast | null;
    error_tab: 'patient' | 'doctor' | 'admin' | null;
}
