<script setup lang="ts">
import { Link, useForm } from "@inertiajs/vue3";
import AppShell from "../../Components/AppShell.vue";

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

function submit(): void {
    form.post("/login");
}
</script>

<template>
    <AppShell>
        <section
            class="mx-auto w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900/60 p-6"
        >
            <h1 class="text-2xl font-black text-emerald-300">Ingresar</h1>
            <p class="mt-2 text-sm text-slate-400">
                Accede para reservar canchas y gestionar tu panel.
            </p>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div>
                    <label
                        class="mb-1 block text-xs uppercase tracking-wide text-slate-400"
                        >Email</label
                    >
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm outline-none focus:border-emerald-400"
                    />
                    <p
                        v-if="form.errors.email"
                        class="mt-1 text-xs text-rose-300"
                    >
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs uppercase tracking-wide text-slate-400"
                        >Contrasena</label
                    >
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm outline-none focus:border-emerald-400"
                    />
                    <p
                        v-if="form.errors.password"
                        class="mt-1 text-xs text-rose-300"
                    >
                        {{ form.errors.password }}
                    </p>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="rounded border-slate-600 bg-slate-950"
                    />
                    Recordarme
                </label>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-lg bg-emerald-400 px-4 py-2 text-sm font-bold text-slate-950 hover:bg-emerald-300 disabled:opacity-60"
                >
                    Iniciar sesion
                </button>
            </form>

            <p class="mt-4 text-sm text-slate-400">
                Aun no tienes cuenta?
                <Link
                    href="/register"
                    class="font-semibold text-emerald-300 underline underline-offset-4"
                    >Registrate</Link
                >
            </p>
        </section>
    </AppShell>
</template>
