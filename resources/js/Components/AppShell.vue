<script setup lang="ts">
import { computed } from "vue";
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
</script>

<template>
    <div
        class="min-h-screen bg-[radial-gradient(ellipse_at_top,_#0b1022_0%,_#0f172a_45%,_#111827_100%)] text-slate-100"
    >
        <header
            class="border-b border-slate-800/80 bg-slate-950/50 backdrop-blur"
        >
            <div
                class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4"
            >
                <Link
                    href="/"
                    class="text-sm font-black uppercase tracking-[0.25em] text-emerald-300"
                >
                    Cancha Alquiler
                </Link>

                <nav class="flex items-center gap-3 text-sm">
                    <Link
                        href="/"
                        class="rounded-md px-3 py-2 hover:bg-slate-800"
                        >Inicio</Link
                    >

                    <template v-if="user">
                        <Link
                            href="/panel"
                            class="rounded-md px-3 py-2 hover:bg-slate-800"
                            >Panel</Link
                        >
                        <span
                            class="hidden rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-slate-300 sm:inline"
                        >
                            {{ user.name }} · {{ roleLabel }}
                        </span>
                        <button
                            type="button"
                            class="rounded-md bg-rose-500 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-400"
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
