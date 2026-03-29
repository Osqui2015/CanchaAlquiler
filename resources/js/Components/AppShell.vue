<script setup lang="ts">
import { computed, ref, onMounted } from "vue";
import { Link, useForm, usePage } from "@inertiajs/vue3";

type AuthUser = {
    id: number;
    name: string;
    email: string;
    role: "cliente" | "admin_cancha" | "super_admin";
    status: "activo" | "suspendido";
};

type SharedPageProps = {
    auth: {
        user: AuthUser | null;
        pending_reservation: any | null;
    };
    flash: {
        success?: string | null;
        error?: string | null;
    };
};

const page = usePage<SharedPageProps>();
const logoutForm = useForm({});

const user = computed(() => page.props.auth?.user ?? null);
const flashSuccess = computed(() => page.props.flash?.success ?? null);
const flashError = computed(() => page.props.flash?.error ?? null);

const roleLabel = computed(() => {
    if (!user.value) return null;

    return {
        cliente: "Cliente",
        admin_cancha: "AdminCancha",
        super_admin: "SuperAdmin",
    }[user.value.role];
});

function logout(): void {
    logoutForm.post("/logout");
}

const isDark = ref(false);

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
});

function toggleTheme() {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.theme = 'dark';
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.theme = 'light';
    }
}
</script>

<template>
    <div
        class="min-h-screen bg-slate-50 dark:bg-[radial-gradient(ellipse_at_top,_#0b1022_0%,_#0f172a_45%,_#111827_100%)] text-slate-900 dark:text-slate-100 transition-colors duration-300"
    >
        <header
            class="border-b border-slate-200 dark:border-slate-800/80 bg-white/70 dark:bg-slate-950/50 backdrop-blur sticky top-0 z-50 transition-colors duration-300"
        >
            <div
                class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4"
            >
                <Link
                    href="/"
                    class="text-sm font-black uppercase tracking-[0.25em] text-emerald-600 dark:text-emerald-300 transition-colors"
                >
                    Cancha Alquiler
                </Link>

                <nav class="flex items-center gap-3 text-sm">
                    <button
                        @click="toggleTheme"
                        class="p-2 rounded-full hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors text-slate-600 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        :aria-label="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                    >
                        <svg v-if="isDark" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                        </svg>
                    </button>

                    <Link
                        href="/"
                        class="rounded-md px-3 py-2 hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors"
                        >Inicio</Link
                    >

                    <template v-if="user">
                        <Link
                            href="/panel"
                            class="rounded-md px-3 py-2 hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors"
                            >Panel</Link
                        >
                        <span
                            class="hidden rounded-md border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 px-3 py-2 text-xs text-slate-700 dark:text-slate-300 sm:inline transition-colors"
                        >
                            {{ user.name }} · {{ roleLabel }}
                        </span>
                        <button
                            type="button"
                            class="rounded-md bg-rose-600 dark:bg-rose-500 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-500 dark:hover:bg-rose-400 transition-colors"
                            @click="logout"
                        >
                            Salir
                        </button>
                    </template>

                    <template v-else>
                        <Link
                            href="/login"
                            class="rounded-md px-3 py-2 hover:bg-slate-800"
                            >Ingresar</Link
                        >
                        <Link
                            href="/register"
                            class="rounded-md bg-emerald-400 px-3 py-2 text-slate-950 hover:bg-emerald-300"
                        >
                            Registrarme
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <div class="mx-auto w-full max-w-6xl px-6 py-6">
            <!-- Global Reservation Alert -->
            <div v-if="page.props.auth.pending_reservation"
                 class="mb-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-slate-800 rounded-2xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="bg-amber-100 dark:bg-amber-800/40 p-2 rounded-xl text-amber-600 dark:text-amber-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold text-amber-800 dark:text-amber-200">Tienes una reserva pendiente de pago</p>
                        <p class="text-sm text-amber-700/80 dark:text-amber-300/60 font-mono">
                            {{ page.props.auth.pending_reservation.code }}
                        </p>
                    </div>
                </div>
                <Link href="/panel/cliente"
                      class="px-5 py-2 bg-amber-500 hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-700 text-white font-bold rounded-xl transition-all shadow-md">
                    Pagar Ahora
                </Link>
            </div>

            <div
                v-if="flashSuccess"
                class="mb-4 rounded-lg border border-emerald-300/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200"
            >
                {{ flashSuccess }}
            </div>
            <div
                v-if="flashError"
                class="mb-4 rounded-lg border border-rose-300/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-200"
            >
                {{ flashError }}
            </div>

            <slot />
        </div>
    </div>
</template>
