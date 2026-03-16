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
        <section
            class="mx-auto w-full max-w-lg rounded-2xl border border-slate-800 bg-slate-900/60 p-6"
        >
            <h1 class="text-2xl font-black text-emerald-300">Crear cuenta</h1>
            <p class="mt-2 text-sm text-slate-400">
                Registrate para poder reservar turnos online.
            </p>

            <form class="mt-6 grid gap-4" @submit.prevent="submit">
                <div>
                    <label
                        class="mb-1 block text-xs uppercase tracking-wide text-slate-400"
                        >Nombre completo</label
                    >
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm outline-none focus:border-emerald-400"
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
                            >Telefono</label
                        >
                        <input
                            v-model="form.phone"
                            type="text"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm outline-none focus:border-emerald-400"
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
                    <div>
                        <label
                            class="mb-1 block text-xs uppercase tracking-wide text-slate-400"
                            >Confirmar</label
                        >
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm outline-none focus:border-emerald-400"
                        />
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-emerald-400 px-4 py-2 text-sm font-bold text-slate-950 hover:bg-emerald-300 disabled:opacity-60"
                >
                    Crear cuenta
                </button>
            </form>

            <p class="mt-4 text-sm text-slate-400">
                Ya tienes cuenta?
                <Link
                    href="/login"
                    class="font-semibold text-emerald-300 underline underline-offset-4"
                    >Iniciar sesion</Link
                >
            </p>
        </section>
    </AppShell>
</template>
