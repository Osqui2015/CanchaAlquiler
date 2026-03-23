<script setup lang="ts">
import { Link, useForm } from "@inertiajs/vue3";
import AppShell from "../../Components/AppShell.vue";

const form = useForm({
    name: "",
    email: "",
    phone: "",
    password: "",
    password_confirmation: "",
});

function submit(): void {
    form.post("/register");
}
</script>

<template>
    <AppShell>
        <section class="card mx-auto w-full max-w-lg shadow-sm">
            <h1
                class="text-2xl font-black text-emerald-600 dark:text-emerald-300 transition-colors duration-300"
            >
                Crear cuenta
            </h1>
            <p
                class="mt-2 text-sm text-slate-500 dark:text-slate-400 transition-colors duration-300"
            >
                Registrate para poder reservar turnos online.
            </p>

            <form class="mt-6 grid gap-4" @submit.prevent="submit">
                <div>
                    <label
                        class="mb-1 block text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400 transition-colors duration-300"
                        >Nombre completo</label
                    >
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="form-field w-full"
                    />
                    <p
                        v-if="form.errors.name"
                        class="mt-1 text-xs text-rose-300"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
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
                            >Telefono</label
                        >
                        <input
                            v-model="form.phone"
                            type="text"
                            class="form-field w-full"
                        />
                        <p
                            v-if="form.errors.phone"
                            class="mt-1 text-xs text-rose-300"
                        >
                            {{ form.errors.phone }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
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
                    <div>
                        <label
                            class="mb-1 block text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400 transition-colors duration-300"
                            >Confirmar</label
                        >
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            class="form-field w-full"
                        />
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="btn-primary w-full"
                >
                    Crear cuenta
                </button>
            </form>

            <p
                class="mt-4 text-sm text-slate-500 dark:text-slate-400 transition-colors duration-300"
            >
                Ya tienes cuenta?
                <Link
                    href="/login"
                    class="font-semibold text-emerald-600 dark:text-emerald-300 transition-colors duration-300 underline underline-offset-4"
                    >Iniciar sesion</Link
                >
            </p>
        </section>
    </AppShell>
</template>
