<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import AppShell from "../../Components/AppShell.vue";

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

const props = defineProps<{
    reservations: Reservation[];
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
            "bg-emerald-200 dark:bg-emerald-400/20 transition-colors duration-300 text-emerald-200 border-emerald-500/30 dark:border-emerald-300/30 transition-colors duration-300",
        cancelada: "bg-rose-400/20 text-rose-200 border-rose-300/30",
        expirada:
            "bg-slate-400/20 text-slate-600 dark:text-slate-300 transition-colors duration-300 border-slate-300/30",
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
        <section class="card shadow-sm">
            <h1
                class="text-2xl font-black text-emerald-600 dark:text-emerald-300 transition-colors duration-300"
            >
                Mi panel de reservas
            </h1>
            <p
                class="mt-2 text-sm text-slate-600 dark:text-slate-300 transition-colors duration-300"
            >
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

        <section class="mt-6 space-y-4">
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
                        <p
                            class="text-sm text-slate-600 dark:text-slate-300 transition-colors duration-300"
                        >
                            {{ reservation.complex.name }} ·
                            {{ reservation.court.name }} ({{
                                reservation.court.sport.name
                            }})
                        </p>
                        <p
                            class="text-xs text-slate-500 dark:text-slate-400 transition-colors duration-300"
                        >
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

                <div
                    class="mt-3 text-sm text-slate-600 dark:text-slate-300 transition-colors duration-300"
                >
                    Total: {{ reservation.total_amount }} · Sena:
                    {{ reservation.deposit_amount }}
                </div>

                <div
                    v-if="reservation.payments.length"
                    class="mt-2 text-xs text-slate-500 dark:text-slate-400 transition-colors duration-300"
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
                class="rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 transition-colors duration-300 p-6 text-sm text-slate-600 dark:text-slate-300 transition-colors duration-300"
            >
                Todavia no tienes reservas. Vuelve al inicio para buscar turnos
                disponibles.
            </div>
        </section>
    </AppShell>
</template>
