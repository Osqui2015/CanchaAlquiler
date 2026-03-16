<script setup lang="ts">
import { reactive } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import AppShell from "../../Components/AppShell.vue";

type DashboardData = {
    month: string;
    total_revenue: number;
    total_clients: number;
    active_admin_cancha: number;
    active_complexes: number;
    owner_performance: Array<{
        id: number;
        name: string;
        email: string;
        total_revenue: number;
    }>;
};

type AdminUser = {
    id: number;
    name: string;
    email: string;
    status: "activo" | "suspendido";
    complex_assignments?: Array<{
        complex?: { id: number; name: string; slug: string };
    }>;
};

type ClientUser = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    status: "activo" | "suspendido";
};

const props = defineProps<{
    dashboard: DashboardData;
    admins: AdminUser[];
    clients: ClientUser[];
    complexes: Array<{ id: number; name: string; slug: string }>;
}>();

const adminForm = useForm({
    name: "",
    email: "",
    password: "",
    phone: "",
    status: "activo",
});

const clientForm = useForm({
    name: "",
    email: "",
    password: "",
    phone: "",
    status: "activo",
});

const assignmentByAdmin = reactive<
    Record<
        number,
        {
            complex_id: string;
            assignment_type: "owner" | "manager";
            is_primary: boolean;
        }
    >
>({});

for (const admin of props.admins) {
    assignmentByAdmin[admin.id] = {
        complex_id: "",
        assignment_type: "owner",
        is_primary: false,
    };
}

function createAdmin(): void {
    adminForm.post("/panel/super-admin/admin-cancha");
}

function createClient(): void {
    clientForm.post("/panel/super-admin/clientes");
}

function assignComplex(adminId: number): void {
    router.post(
        `/panel/super-admin/admin-cancha/${adminId}/asignar-complejo`,
        assignmentByAdmin[adminId],
    );
}

function updateClientStatus(clientId: number, status: string): void {
    router.put(`/panel/super-admin/clientes/${clientId}`, { status });
}

function onClientStatusChange(clientId: number, event: Event): void {
    const target = event.target as HTMLSelectElement | null;

    if (!target) return;

    updateClientStatus(clientId, target.value);
}

function formatMoney(value: number): string {
    return new Intl.NumberFormat("es-AR", {
        style: "currency",
        currency: "ARS",
        maximumFractionDigits: 0,
    }).format(value);
}
</script>

<template>
    <AppShell>
        <section
            class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6"
        >
            <h1 class="text-2xl font-black text-emerald-300">
                Panel SuperAdmin
            </h1>
            <p class="mt-2 text-sm text-slate-300">
                Control total de duenos, clientes y rendimiento global.
            </p>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    class="rounded-xl border border-slate-800 bg-slate-950/70 p-4"
                >
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Ingresos del mes
                    </p>
                    <p class="mt-2 text-xl font-bold">
                        {{ formatMoney(props.dashboard.total_revenue) }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-slate-800 bg-slate-950/70 p-4"
                >
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Clientes totales
                    </p>
                    <p class="mt-2 text-xl font-bold">
                        {{ props.dashboard.total_clients }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-slate-800 bg-slate-950/70 p-4"
                >
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Admins activos
                    </p>
                    <p class="mt-2 text-xl font-bold">
                        {{ props.dashboard.active_admin_cancha }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-slate-800 bg-slate-950/70 p-4"
                >
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Complejos activos
                    </p>
                    <p class="mt-2 text-xl font-bold">
                        {{ props.dashboard.active_complexes }}
                    </p>
                </div>
            </div>
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-2">
            <article
                class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6"
            >
                <h2 class="font-bold text-emerald-200">Crear AdminCancha</h2>
                <form class="mt-3 grid gap-2" @submit.prevent="createAdmin">
                    <input
                        v-model="adminForm.name"
                        placeholder="Nombre"
                        class="rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                        required
                    />
                    <input
                        v-model="adminForm.email"
                        placeholder="Email"
                        type="email"
                        class="rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                        required
                    />
                    <input
                        v-model="adminForm.password"
                        placeholder="Contrasena"
                        type="password"
                        class="rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                        required
                    />
                    <input
                        v-model="adminForm.phone"
                        placeholder="Telefono"
                        class="rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                    />
                    <button
                        class="rounded-md bg-emerald-400 px-4 py-2 text-sm font-bold text-slate-950"
                    >
                        Crear AdminCancha
                    </button>
                </form>
            </article>

            <article
                class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6"
            >
                <h2 class="font-bold text-emerald-200">Crear Cliente</h2>
                <form class="mt-3 grid gap-2" @submit.prevent="createClient">
                    <input
                        v-model="clientForm.name"
                        placeholder="Nombre"
                        class="rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                        required
                    />
                    <input
                        v-model="clientForm.email"
                        placeholder="Email"
                        type="email"
                        class="rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                        required
                    />
                    <input
                        v-model="clientForm.password"
                        placeholder="Contrasena"
                        type="password"
                        class="rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                        required
                    />
                    <input
                        v-model="clientForm.phone"
                        placeholder="Telefono"
                        class="rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                    />
                    <button
                        class="rounded-md bg-sky-400 px-4 py-2 text-sm font-bold text-slate-950"
                    >
                        Crear Cliente
                    </button>
                </form>
            </article>
        </section>

        <section
            class="mt-6 rounded-2xl border border-slate-800 bg-slate-900/60 p-6"
        >
            <h2 class="font-bold text-emerald-200">
                Asignaciones de complejos a AdminCancha
            </h2>
            <div class="mt-3 space-y-3">
                <div
                    v-for="admin in props.admins"
                    :key="admin.id"
                    class="rounded-xl border border-slate-800 bg-slate-950/70 p-4"
                >
                    <p class="font-semibold">
                        {{ admin.name }} · {{ admin.email }}
                    </p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <select
                            v-model="assignmentByAdmin[admin.id].complex_id"
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        >
                            <option value="">Seleccionar complejo</option>
                            <option
                                v-for="complex in props.complexes"
                                :key="complex.id"
                                :value="String(complex.id)"
                            >
                                {{ complex.name }}
                            </option>
                        </select>
                        <select
                            v-model="
                                assignmentByAdmin[admin.id].assignment_type
                            "
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        >
                            <option value="owner">Owner</option>
                            <option value="manager">Manager</option>
                        </select>
                        <button
                            type="button"
                            class="rounded-md bg-amber-300 px-3 py-2 text-xs font-bold text-slate-950"
                            @click="assignComplex(admin.id)"
                        >
                            Asignar
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <section
            class="mt-6 rounded-2xl border border-slate-800 bg-slate-900/60 p-6"
        >
            <h2 class="font-bold text-emerald-200">Gestion de clientes</h2>
            <div class="mt-3 grid gap-2">
                <div
                    v-for="client in props.clients"
                    :key="client.id"
                    class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-slate-800 bg-slate-950/70 px-3 py-2 text-sm"
                >
                    <span>{{ client.name }} · {{ client.email }}</span>
                    <select
                        :value="client.status"
                        class="rounded-md border border-slate-700 bg-slate-900 px-2 py-1 text-xs"
                        @change="onClientStatusChange(client.id, $event)"
                    >
                        <option value="activo">Activo</option>
                        <option value="suspendido">Suspendido</option>
                    </select>
                </div>
            </div>
        </section>
    </AppShell>
</template>
