<script setup lang="ts">
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import AppShell from "@/Components/AppShell.vue";
import ClientProfile from "./parts/ClientProfile.vue";
import ClientRanking from "./parts/ClientRanking.vue";
import ClientFavorites from "./parts/ClientFavorites.vue";

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

// ─── Maps de estado como constantes de módulo ────────────────────────────────
const STATUS_CLASS: Record<Reservation["status"], string> = {
    pendiente_pago: "bg-amber-400/20 text-amber-200 border-amber-300/30",
    confirmada:
        "bg-emerald-400/20 text-emerald-800 dark:text-emerald-200 border-emerald-500/30 dark:border-emerald-300/30 transition-colors duration-300",
    cancelada: "bg-rose-400/20 text-rose-200 border-rose-300/30",
    expirada:
        "bg-slate-400/20 text-slate-600 dark:text-slate-300 border-slate-300/30 transition-colors duration-300",
    no_show: "bg-orange-400/20 text-orange-200 border-orange-300/30",
};

const STATUS_TEXT: Record<Reservation["status"], string> = {
    pendiente_pago: "Pendiente de pago",
    confirmada: "Confirmada",
    cancelada: "Cancelada",
    expirada: "Expirada",
    no_show: "No Show",
};

// ─── Rutas del panel cliente (centralizado) ───────────────────────────────────
const clientRoutes = {
    cancel: (id: number) => `/panel/cliente/reservas/${id}/cancelar`,
    checkout: (id: number) => `/panel/cliente/reservas/${id}/checkout`,
    approveDemo: (id: number) =>
        `/panel/cliente/reservas/${id}/checkout/aprobar-demo`,
};

// ─── Props ───────────────────────────────────────────────────────────────────
const props = defineProps<{
    reservations: Reservation[];
    checkout?: {
        provider_payment_id: string;
        amount: number;
        currency: string;
        checkout_url: string;
    } | null;
}>();

// ─── UI: tabs for client panel
import { ref as vueRef } from "vue";
const activeTab = vueRef<"reservas" | "config" | "ranking" | "favoritos">(
    "reservas",
);

// helper to get current auth user id from Inertia shared props
import { usePage } from "@inertiajs/vue3";
const page = usePage<any>();
const currentUserId = page.props.value?.auth?.user?.id ?? null;

// ─── Forms por reserva (evita estado compartido en el loop) ──────────────────
type FormMap = Map<number, ReturnType<typeof useForm>>;

const cancelForms: FormMap = new Map(
    props.reservations.map((r) => [r.id, useForm({ reason: "" })]),
);
const checkoutForms: FormMap = new Map(
    props.reservations.map((r) => [r.id, useForm({ provider: "mercadopago" })]),
);
const approveForms: FormMap = new Map(
    props.reservations.map((r) => [r.id, useForm({})]),
);

function getForm(map: FormMap, id: number) {
    const form = map.get(id);
    if (!form)
        throw new Error(`[Dashboard] No form found for reservation id=${id}`);
    return form;
}

// ─── Estado para confirmación de cancelación ──────────────────────────────────
const reservationToCancel = ref<Reservation | null>(null);

// ─── Helpers de formato ───────────────────────────────────────────────────────
function formatDate(iso: string): string {
    return new Date(iso).toLocaleString("es-AR", {
        dateStyle: "medium",
        timeStyle: "short",
    });
}

function formatMoney(amount: string): string {
    return new Intl.NumberFormat("es-AR", {
        style: "currency",
        currency: "ARS",
    }).format(Number(amount));
}

function lastPayment(reservation: Reservation): Payment | undefined {
    return reservation.payments.at(-1);
}

// ─── Acciones ────────────────────────────────────────────────────────────────
function confirmCancel(reservation: Reservation): void {
    reservationToCancel.value = reservation;
}

function cancelReservation(): void {
    if (reservationToCancel.value === null) return;
    const id = reservationToCancel.value.id;
    getForm(cancelForms, id).post(clientRoutes.cancel(id), {
        onFinish: () => (reservationToCancel.value = null),
    });
}

function startCheckout(id: number): void {
    getForm(checkoutForms, id).post(clientRoutes.checkout(id));
}

function approveDemo(id: number): void {
    getForm(approveForms, id).post(clientRoutes.approveDemo(id));
}

// ─── Helpers de UI ───────────────────────────────────────────────────────────
function statusClass(status: Reservation["status"]): string {
    return STATUS_CLASS[status] ?? "";
}

function statusText(status: Reservation["status"]): string {
    return STATUS_TEXT[status] ?? status;
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
                Aquí podés ver tu historial, el estado de cada turno y completar
                la seña para confirmar reservas.
            </p>
        </section>

        <section class="mt-6 space-y-4">
            <!-- Tabs -->
            <div class="flex items-center gap-2">
                <button
                    :class="[
                        'px-3 py-2 rounded font-bold',
                        activeTab === 'reservas'
                            ? 'bg-emerald-500 text-white'
                            : 'bg-white',
                    ]"
                    @click.prevent="activeTab = 'reservas'"
                >
                    Reservas
                </button>
                <button
                    :class="[
                        'px-3 py-2 rounded font-bold',
                        activeTab === 'config'
                            ? 'bg-emerald-500 text-white'
                            : 'bg-white',
                    ]"
                    @click.prevent="activeTab = 'config'"
                >
                    Configuraciones
                </button>
                <button
                    :class="[
                        'px-3 py-2 rounded font-bold',
                        activeTab === 'ranking'
                            ? 'bg-emerald-500 text-white'
                            : 'bg-white',
                    ]"
                    @click.prevent="activeTab = 'ranking'"
                >
                    Ranking
                </button>
                <button
                    :class="[
                        'px-3 py-2 rounded font-bold',
                        activeTab === 'favoritos'
                            ? 'bg-emerald-500 text-white'
                            : 'bg-white',
                    ]"
                    @click.prevent="activeTab = 'favoritos'"
                >
                    Favoritos
                </button>
            </div>

            <div class="mt-4">
                <ClientProfile v-if="activeTab === 'config'" />
                <ClientRanking v-else-if="activeTab === 'ranking'" />
                <ClientFavorites v-else-if="activeTab === 'favoritos'" />

                <template v-else>
                    <article
                        v-for="reservation in props.reservations"
                        :key="reservation.id"
                        class="card card--muted"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-3"
                        >
                            <div>
                                <h2
                                    class="text-lg font-bold text-emerald-600 dark:text-emerald-200 transition-colors duration-300"
                                >
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
                                    {{ formatDate(reservation.start_at) }} →
                                    {{ formatDate(reservation.end_at) }}
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
                            Total: {{ formatMoney(reservation.total_amount) }} ·
                            Seña:
                            {{ formatMoney(reservation.deposit_amount) }}
                        </div>

                        <div
                            v-if="lastPayment(reservation)"
                            class="mt-2 text-xs text-slate-500 dark:text-slate-400 transition-colors duration-300"
                        >
                            Último pago:
                            {{ lastPayment(reservation)?.provider_payment_id }}
                            ({{ lastPayment(reservation)?.status }})
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <button
                                v-if="reservation.status === 'pendiente_pago'"
                                type="button"
                                :disabled="
                                    getForm(checkoutForms, reservation.id)
                                        .processing
                                "
                                class="rounded-lg bg-sky-400 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-sky-300 disabled:opacity-50 disabled:cursor-not-allowed"
                                @click="startCheckout(reservation.id)"
                            >
                                {{
                                    getForm(checkoutForms, reservation.id)
                                        .processing
                                        ? "Procesando..."
                                        : "Iniciar checkout"
                                }}
                            </button>

                            <button
                                v-if="reservation.status === 'pendiente_pago'"
                                type="button"
                                :disabled="
                                    getForm(approveForms, reservation.id)
                                        .processing
                                "
                                class="rounded-lg border border-emerald-400 px-4 py-2 text-xs font-bold text-emerald-700 dark:text-emerald-200 hover:bg-emerald-400/10 disabled:opacity-50 disabled:cursor-not-allowed"
                                @click="approveDemo(reservation.id)"
                            >
                                {{
                                    getForm(approveForms, reservation.id)
                                        .processing
                                        ? "Aprobando..."
                                        : "Aprobar seña (demo)"
                                }}
                            </button>

                            <button
                                v-if="
                                    reservation.status === 'pendiente_pago' ||
                                    reservation.status === 'confirmada'
                                "
                                type="button"
                                :disabled="
                                    getForm(cancelForms, reservation.id)
                                        .processing
                                "
                                class="rounded-lg border border-rose-400 px-4 py-2 text-xs font-bold text-rose-600 dark:text-rose-200 hover:bg-rose-400/10 disabled:opacity-50 disabled:cursor-not-allowed"
                                @click="confirmCancel(reservation)"
                            >
                                Cancelar reserva
                            </button>
                        </div>
                    </article>

                    <div
                        v-if="props.reservations.length === 0"
                        class="rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 p-6 text-sm text-slate-600 dark:text-slate-300 transition-colors duration-300"
                    >
                        Todavía no tenés reservas. Volvé al inicio para buscar
                        turnos disponibles.
                    </div>

                    <!-- Modal de confirmación de cancelación -->
                    <div
                        v-if="reservationToCancel !== null"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
                    >
                        <div
                            class="mx-4 w-full max-w-sm rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-xl"
                        >
                            <h3 class="text-base font-bold text-slate-100">
                                ¿Cancelar reserva?
                            </h3>
                            <p class="mt-1 font-mono text-xs text-emerald-400">
                                {{ reservationToCancel!.code }}
                            </p>
                            <p class="mt-2 text-sm text-slate-400">
                                Esta acción no se puede deshacer. ¿Estás seguro?
                            </p>
                            <div class="mt-5 flex justify-end gap-3">
                                <button
                                    type="button"
                                    class="rounded-lg border border-slate-600 px-4 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-800"
                                    @click="reservationToCancel = null"
                                >
                                    No, volver
                                </button>
                                <button
                                    type="button"
                                    :disabled="
                                        getForm(
                                            cancelForms,
                                            reservationToCancel!.id,
                                        ).processing
                                    "
                                    class="rounded-lg bg-rose-500 px-4 py-2 text-xs font-bold text-white hover:bg-rose-400 disabled:opacity-50 disabled:cursor-not-allowed"
                                    @click="cancelReservation()"
                                >
                                    {{
                                        getForm(
                                            cancelForms,
                                            reservationToCancel!.id,
                                        ).processing
                                            ? "Cancelando..."
                                            : "Sí, cancelar"
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <!-- Bloque de debug de checkout (solo visible post-checkout) -->
        <section
            v-if="props.checkout"
            class="mt-6 rounded-xl border border-slate-200 dark:border-slate-700/60 bg-slate-100 dark:bg-slate-800/40 p-4 transition-colors duration-300"
        >
            <p
                class="text-xs font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-2"
            >
                Detalle del último checkout
            </p>
            <dl
                class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-600 dark:text-slate-300"
            >
                <dt class="font-medium">ID de pago</dt>
                <dd class="font-mono">
                    {{ props.checkout.provider_payment_id }}
                </dd>
                <dt class="font-medium">Monto</dt>
                <dd>
                    {{ props.checkout.currency }} {{ props.checkout.amount }}
                </dd>
                <dt class="font-medium">URL checkout</dt>
                <dd class="truncate">{{ props.checkout.checkout_url }}</dd>
            </dl>
        </section>
    </AppShell>
</template>
