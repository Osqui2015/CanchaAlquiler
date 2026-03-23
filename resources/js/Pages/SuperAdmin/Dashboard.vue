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
    catalogs: {
        cities: Array<{ id: number; name: string; province: { name: string } }>;
        services: Array<{
            id: number;
            name: string;
            slug: string;
            icon: string;
        }>;
    };
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

const complexForm = useForm({
    city_id: "",
    name: "",
    address_line: "",
    description: "",
    phone_contact: "",
    service_ids: [] as number[],
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

function createComplex(): void {
    complexForm.post("/panel/super-admin/complejos", {
        onSuccess: () => {
            complexForm.reset();
        },
    });
}

function toggleService(serviceId: number): void {
    const exists = complexForm.service_ids.includes(serviceId);
    if (exists) {
        complexForm.service_ids = complexForm.service_ids.filter(
            (id) => id !== serviceId,
        );
    } else {
        complexForm.service_ids.push(serviceId);
    }
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

function updateAdminStatus(adminId: number, status: string): void {
    router.put(
        `/panel/super-admin/admin-cancha/${adminId}`,
        { status },
        { preserveScroll: true },
    );
}

function onAdminStatusChange(adminId: number, event: Event): void {
    const target = event.target as HTMLSelectElement | null;
    if (!target) return;
    updateAdminStatus(adminId, target.value);
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
        <section class="card shadow-sm">
            <h1
                class="text-2xl font-black text-emerald-600 dark:text-emerald-300 transition-colors duration-300"
            >
                Panel SuperAdmin
            </h1>
            <p
                class="mt-2 text-sm text-slate-600 dark:text-slate-300 transition-colors duration-300"
            >
                Control total de duenos, clientes y rendimiento global.
            </p>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="card card--muted">
                    <p
                        class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400 transition-colors duration-300"
                    >
                        Ingresos del mes
                    </p>
                    <p class="mt-2 text-xl font-bold">
                        {{ formatMoney(props.dashboard.total_revenue) }}
                    </p>
                </div>
                <div class="card card--muted">
                    <p
                        class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400 transition-colors duration-300"
                    >
                        Clientes totales
                    </p>
                    <p class="mt-2 text-xl font-bold">
                        {{ props.dashboard.total_clients }}
                    </p>
                </div>
                <div class="card card--muted">
                    <p
                        class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400 transition-colors duration-300"
                    >
                        Admins activos
                    </p>
                    <p class="mt-2 text-xl font-bold">
                        {{ props.dashboard.active_admin_cancha }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-slate-200 dark:border-slate-800 transition-colors duration-300 bg-slate-50/70 dark:bg-slate-950/70 transition-colors duration-300 p-4"
                >
                    <p
                        class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400 transition-colors duration-300"
                    >
                        Complejos activos
                    </p>
                    <p class="mt-2 text-xl font-bold">
                        {{ props.dashboard.active_complexes }}
                    </p>
                </div>
            </div>
        </section>

        <section class="card shadow-sm mt-6">
            <h2 class="text-lg font-bold text-emerald-300">Crear complejo</h2>
            <form
                class="mt-4 grid gap-3 md:grid-cols-2"
                @submit.prevent="createComplex"
            >
                <select
                    v-model="complexForm.city_id"
                    required
                    class="form-field"
                >
                    <option value="">Ciudad</option>
                    <option
                        v-for="city in props.catalogs.cities"
                        :key="city.id"
                        :value="String(city.id)"
                    >
                        {{ city.name }} ({{ city.province.name }})
                    </option>
                </select>
                <input
                    v-model="complexForm.name"
                    type="text"
                    placeholder="Nombre del complejo"
                    class="form-field w-full"
                    required
                />
                <input
                    v-model="complexForm.address_line"
                    type="text"
                    placeholder="Direccion"
                    class="form-field w-full md:col-span-2"
                    required
                />
                <input
                    v-model="complexForm.phone_contact"
                    type="text"
                    placeholder="Telefono"
                    class="form-field w-full"
                />
                <input
                    v-model="complexForm.description"
                    type="text"
                    placeholder="Descripcion"
                    class="form-field w-full"
                />
                <div class="md:col-span-2">
                    <p
                        class="mb-2 text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400"
                    >
                        Servicios
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="service in props.catalogs.services"
                            :key="service.id"
                            type="button"
                            class="rounded-md border px-3 py-1 text-xs"
                            :class="
                                complexForm.service_ids.includes(service.id)
                                    ? 'border-emerald-300 bg-emerald-300/10 text-emerald-200'
                                    : 'border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300'
                            "
                            @click="toggleService(service.id)"
                        >
                            {{ service.name }}
                        </button>
                    </div>
                </div>
                <button
                    class="rounded-lg bg-emerald-400 px-4 py-2 text-sm font-bold text-slate-950 hover:bg-emerald-300 md:col-span-2"
                >
                    Guardar complejo
                </button>
            </form>
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-2">
            <article class="card shadow-sm">
                <h2 class="font-bold text-emerald-200">Crear AdminCancha</h2>
                <form class="mt-3 grid gap-2" @submit.prevent="createAdmin">
                    <input
                        v-model="adminForm.name"
                        placeholder="Nombre"
                        class="rounded-md border border-slate-300 dark:border-slate-700 transition-colors duration-300 bg-slate-50 dark:bg-slate-950 transition-colors duration-300 px-3 py-2 text-sm"
                        required
                    />
                    <input
                        v-model="adminForm.email"
                        placeholder="Email"
                        type="email"
                        class="rounded-md border border-slate-300 dark:border-slate-700 transition-colors duration-300 bg-slate-50 dark:bg-slate-950 transition-colors duration-300 px-3 py-2 text-sm"
                        required
                    />
                    <input
                        v-model="adminForm.password"
                        placeholder="Contrasena"
                        type="password"
                        class="rounded-md border border-slate-300 dark:border-slate-700 transition-colors duration-300 bg-slate-50 dark:bg-slate-950 transition-colors duration-300 px-3 py-2 text-sm"
                        required
                    />
                    <input
                        v-model="adminForm.phone"
                        placeholder="Telefono"
                        class="rounded-md border border-slate-300 dark:border-slate-700 transition-colors duration-300 bg-slate-50 dark:bg-slate-950 transition-colors duration-300 px-3 py-2 text-sm"
                    />
                    <button
                        class="rounded-md bg-emerald-400 px-4 py-2 text-sm font-bold text-slate-950"
                    >
                        Crear AdminCancha
                    </button>
                </form>
            </article>

            <article class="card shadow-sm">
                <h2 class="font-bold text-emerald-200">Crear Cliente</h2>
                <form class="mt-3 grid gap-2" @submit.prevent="createClient">
                    <input
                        v-model="clientForm.name"
                        placeholder="Nombre"
                        class="rounded-md border border-slate-300 dark:border-slate-700 transition-colors duration-300 bg-slate-50 dark:bg-slate-950 transition-colors duration-300 px-3 py-2 text-sm"
                        required
                    />
                    <input
                        v-model="clientForm.email"
                        placeholder="Email"
                        type="email"
                        class="rounded-md border border-slate-300 dark:border-slate-700 transition-colors duration-300 bg-slate-50 dark:bg-slate-950 transition-colors duration-300 px-3 py-2 text-sm"
                        required
                    />
                    <input
                        v-model="clientForm.password"
                        placeholder="Contrasena"
                        type="password"
                        class="rounded-md border border-slate-300 dark:border-slate-700 transition-colors duration-300 bg-slate-50 dark:bg-slate-950 transition-colors duration-300 px-3 py-2 text-sm"
                        required
                    />
                    <input
                        v-model="clientForm.phone"
                        placeholder="Telefono"
                        class="rounded-md border border-slate-300 dark:border-slate-700 transition-colors duration-300 bg-slate-50 dark:bg-slate-950 transition-colors duration-300 px-3 py-2 text-sm"
                    />
                    <button
                        class="rounded-md bg-sky-400 px-4 py-2 text-sm font-bold text-slate-950"
                    >
                        Crear Cliente
                    </button>
                </form>
            </article>
        </section>

        <section class="card shadow-sm mt-6">
            <h2 class="font-bold text-emerald-200">
                Asignaciones de complejos a AdminCancha
            </h2>
            <div class="mt-3 space-y-3">
                <div
                    v-for="admin in props.admins"
                    :key="admin.id"
                    class="rounded-xl border border-slate-200 dark:border-slate-800 transition-colors duration-300 bg-slate-50/70 dark:bg-slate-950/70 transition-colors duration-300 p-4"
                >
                    <div class="flex items-center justify-between">
                        <p class="font-semibold">
                            {{ admin.name }} · {{ admin.email }}
                        </p>
                        <select
                            :value="admin.status"
                            class="rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1 text-xs"
                            @change="onAdminStatusChange(admin.id, $event)"
                        >
                            <option value="activo">Activo</option>
                            <option value="suspendido">Suspendido</option>
                        </select>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <select
                            v-model="assignmentByAdmin[admin.id].complex_id"
                            class="rounded-md border border-slate-300 dark:border-slate-700 transition-colors duration-300 bg-white dark:bg-slate-900 transition-colors duration-300 px-3 py-2 text-sm"
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
                            class="rounded-md border border-slate-300 dark:border-slate-700 transition-colors duration-300 bg-white dark:bg-slate-900 transition-colors duration-300 px-3 py-2 text-sm"
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

        <section class="card shadow-sm mt-6">
            <h2 class="font-bold text-emerald-200">Gestion de clientes</h2>
            <div class="mt-3 grid gap-2">
                <div
                    v-for="client in props.clients"
                    :key="client.id"
                    class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-slate-200 dark:border-slate-800 transition-colors duration-300 bg-slate-50/70 dark:bg-slate-950/70 transition-colors duration-300 px-3 py-2 text-sm"
                >
                    <span>{{ client.name }} · {{ client.email }}</span>
                    <select
                        :value="client.status"
                        class="rounded-md border border-slate-300 dark:border-slate-700 transition-colors duration-300 bg-white dark:bg-slate-900 transition-colors duration-300 px-2 py-1 text-xs"
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
