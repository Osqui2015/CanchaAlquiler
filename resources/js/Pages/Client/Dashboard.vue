<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import AppShell from "../../Components/AppShell.vue";
import ClientSidebar from "../../Components/ClientSidebar.vue";

type Payment = {
    id: number;
    provider: string;
    status: string;
    amount: string;
    provider_payment_id: string;
};

type Reservation = {
    id: number;
    code: string;
    status:
        | "pendiente_pago"
        | "confirmada"
        | "cancelada"
        | "expirada"
        | "no_show";
    start_at: string;
    end_at: string;
    total_amount: string;
    deposit_amount: string;
    court: {
        id: number;
        name: string;
        sport: {
            id: number;
            name: string;
        };
    };
    complex: {
        id: number;
        name: string;
        city?: {
            name: string;
            province?: { name: string };
        };
    };
    payments: Payment[];
};

type PlayerStat = {
    id: number;
    sport_id: number;
    match_type: "solo" | "pair" | "team";
    wins: number;
    losses: number;
    draws: number;
    points: number;
    rank: number;
    sport: { id: number; name: string; slug: string };
};

type VenueStat = {
    id: number;
    complex_id: number;
    matches_played: number;
    wins: number;
    complex: { id: number; name: string };
};

const props = defineProps<{
    reservations: Reservation[];
    stats: PlayerStat[];
    venueStats: VenueStat[];
    checkout?: {
        provider_payment_id: string;
        amount: number;
        currency: string;
        checkout_url: string;
    } | null;
}>();

const cancelForm = useForm({ reason: "" });
const checkoutForm = useForm({ provider: "mercadopago" });
const approveForm = useForm({});

function cancelReservation(id: number): void {
    if (!window.confirm("Deseas cancelar esta reserva?")) return;

    cancelForm.post(`/panel/cliente/reservas/${id}/cancelar`);
}

function startCheckout(id: number): void {
    checkoutForm.post(`/panel/cliente/reservas/${id}/checkout`);
}

function approveDemo(id: number): void {
    approveForm.post(`/panel/cliente/reservas/${id}/checkout/aprobar-demo`);
}

function statusClass(status: Reservation["status"]): string {
    return {
        pendiente_pago: "bg-amber-400/20 text-amber-200 border-amber-300/30",
        confirmada:
            "bg-emerald-200 dark:bg-emerald-400/20 text-emerald-200 border-emerald-500/30 dark:border-emerald-300/30",
        cancelada: "bg-rose-400/20 text-rose-200 border-rose-300/30",
        expirada:
            "bg-slate-400/20 text-slate-600 dark:text-slate-300 border-slate-300/30",
        no_show: "bg-orange-400/20 text-orange-200 border-orange-300/30",
    }[status];
}

function statusText(status: Reservation["status"]): string {
    return {
        pendiente_pago: "Pendiente de pago",
        confirmada: "Confirmada",
        cancelada: "Cancelada",
        expirada: "Expirada",
        no_show: "No Show",
    }[status];
}
</script>

<template>
    <AppShell>
        <div class="flex flex-col md:flex-row gap-6 items-start">
            <div class="w-full md:w-64 flex-shrink-0 sticky top-24">
                <ClientSidebar class="rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden" />
            </div>
            <div class="flex-1 min-w-0 w-full space-y-6">
                <section class="card shadow-sm">
            <h1
                class="text-2xl font-black text-emerald-600 dark:text-emerald-300"
            >
                Mi panel de reservas
            </h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                Aqui puedes ver historial, estado y completar la seña para
                confirmar turnos.
            </p>

            <div
                v-if="props.checkout"
                class="mt-4 rounded-xl border border-sky-300/30 bg-sky-400/10 p-4 text-sm text-sky-100"
            >
                Checkout generado:
                <strong>{{ props.checkout.provider_payment_id }}</strong> ·
                {{ props.checkout.currency }} {{ props.checkout.amount }} · URL
                demo: {{ props.checkout.checkout_url }}
            </div>
        </section>

        <!-- Stats and Ranking Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="lg:col-span-2 card bg-white dark:bg-slate-900 shadow-sm border border-slate-200 dark:border-slate-800">
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Tu Ranking y Estadísticas
                </h2>
                <div v-if="props.stats.length === 0" class="text-center py-8">
                    <p class="text-slate-500 italic">Aún no tienes partidos registrados. ¡Ven a jugar para subir en el ranking!</p>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-xs uppercase text-slate-500 border-b border-slate-100 dark:border-slate-800">
                                <th class="pb-3 px-2">Deporte</th>
                                <th class="pb-3 px-2">Modalidad</th>
                                <th class="pb-3 px-2 text-center">G / E / P</th>
                                <th class="pb-3 px-2 text-right">Puntos</th>
                                <th class="pb-3 px-2 text-right"># Rank</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                            <tr v-for="stat in props.stats" :key="stat.id" class="text-sm">
                                <td class="py-4 px-2 font-medium">{{ stat.sport.name }}</td>
                                <td class="py-4 px-2 capitalize text-xs text-slate-500 text-gray-400">{{ stat.match_type }}</td>
                                <td class="py-4 px-2 text-center font-mono">
                                    <span class="text-emerald-500">{{ stat.wins }}</span> / 
                                    <span class="text-slate-400">{{ stat.draws }}</span> / 
                                    <span class="text-rose-400">{{ stat.losses }}</span>
                                </td>
                                <td class="py-4 px-2 text-right font-bold text-indigo-600 dark:text-indigo-400">{{ stat.points }}</td>
                                <td class="py-4 px-2 text-right">
                                    <span class="inline-flex items-center justify-center bg-amber-100 text-amber-700 font-black rounded-full w-8 h-8 text-xs border border-amber-200">
                                        {{ stat.rank || '-' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card bg-indigo-50 dark:bg-indigo-900/10 border-indigo-100 dark:border-indigo-800 shadow-sm">
                <h2 class="font-bold text-indigo-800 dark:text-indigo-200 mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Tus Sedes Favoritas
                </h2>
                <div v-if="props.venueStats.length === 0" class="text-xs text-indigo-600/60 dark:text-indigo-400/60 space-y-4">
                    <p>Juega en un complejo para verlo aquí.</p>
                </div>
                <ul class="space-y-4">
                    <li v-for="venue in props.venueStats" :key="venue.id" class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-indigo-100 dark:border-indigo-800 flex items-center justify-between shadow-sm">
                        <div class="min-w-0">
                            <p class="font-bold text-sm text-gray-800 dark:text-gray-100 truncate">{{ venue.complex.name }}</p>
                            <p class="text-xs text-gray-500">{{ venue.matches_played }} partidos jugados</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ venue.wins }} Victorias</p>
                        </div>
                    </li>
                </ul>
            </section>
        </div>

        <section class="mt-6 space-y-4">
            <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200 px-1">Próximas Reservas</h2>
            <article
                v-for="reservation in props.reservations"
                :key="reservation.id"
                class="card card--muted"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-emerald-200">
                            {{ reservation.code }}
                        </h2>
                        <p class="text-sm text-slate-600 dark:text-slate-300">
                            {{ reservation.complex.name }} ·
                            {{ reservation.court.name }} ({{
                                reservation.court.sport.name
                            }})
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ reservation.start_at }} →
                            {{ reservation.end_at }}
                        </p>
                    </div>
                    <span
                        class="rounded-md border px-3 py-1 text-xs font-semibold"
                        :class="statusClass(reservation.status)"
                    >
                        {{ statusText(reservation.status) }}
                    </span>
                </div>

                <div class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                    Total: {{ reservation.total_amount }} · Sena:
                    {{ reservation.deposit_amount }}
                </div>

                <div
                    v-if="reservation.payments && reservation.payments.length"
                    class="mt-2 text-xs text-slate-500 dark:text-slate-400"
                >
                    Ultimo pago:
                    {{
                        reservation.payments[reservation.payments.length - 1]
                            .provider_payment_id
                    }}
                    ({{
                        reservation.payments[reservation.payments.length - 1]
                            .status
                    }})
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <button
                        v-if="reservation.status === 'pendiente_pago'"
                        type="button"
                        class="rounded-lg bg-sky-400 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-sky-300"
                        @click="startCheckout(reservation.id)"
                    >
                        Iniciar checkout
                    </button>

                    <button
                        v-if="reservation.status === 'pendiente_pago'"
                        type="button"
                        class="rounded-lg border border-emerald-400 px-4 py-2 text-xs font-bold text-emerald-200 hover:bg-emerald-400/10"
                        @click="approveDemo(reservation.id)"
                    >
                        Aprobar seña (demo)
                    </button>

                    <button
                        v-if="
                            reservation.status === 'pendiente_pago' ||
                            reservation.status === 'confirmada'
                        "
                        type="button"
                        class="rounded-lg border border-rose-400 px-4 py-2 text-xs font-bold text-rose-200 hover:bg-rose-400/10"
                        @click="cancelReservation(reservation.id)"
                    >
                        Cancelar reserva
                    </button>
                </div>
            </article>

            <div
                v-if="props.reservations.length === 0"
                class="rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 p-6 text-sm text-slate-600 dark:text-slate-300"
            >
                Todavia no tienes reservas. Vuelve al inicio para buscar turnos
                disponibles.
            </div>
        </section>
            </div>
        </div>
    </AppShell>
</template>
