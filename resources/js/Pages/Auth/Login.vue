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
        <section class="card mx-auto w-full max-w-md shadow-sm">
            <h1
                class="text-2xl font-black text-emerald-600 dark:text-emerald-300 transition-colors duration-300"
            >
                Ingresar
            </h1>
            <p
                class="mt-2 text-sm text-slate-500 dark:text-slate-400 transition-colors duration-300"
            >
                Accede para reservar canchas y gestionar tu panel.
            </p>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div>
                    <label
                        class="mb-1 block text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400 transition-colors duration-300"
                        >Email</label
                    >
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        class="form-field w-full"
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
                        class="mb-1 block text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400 transition-colors duration-300"
                        >Contrasena</label
                    >
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        class="form-field w-full"
                    />
                    <p
                        v-if="form.errors.password"
                        class="mt-1 text-xs text-rose-300"
                    >
                        {{ form.errors.password }}
                    </p>
                </div>

                <label
                    class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300 transition-colors duration-300"
                >
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="rounded border-slate-400 dark:border-slate-600"
                    />
                    Recordarme
                </label>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="btn-primary w-full"
                >
                    Iniciar sesion
                </button>
            </form>

            <p
                class="mt-4 text-sm text-slate-500 dark:text-slate-400 transition-colors duration-300"
            >
                Aun no tienes cuenta?
                <Link
                    href="/register"
                    class="font-semibold text-emerald-600 dark:text-emerald-300 transition-colors duration-300 underline underline-offset-4"
                    >Registrate</Link
                >
            </p>
        </section>
    </AppShell>
</template>
