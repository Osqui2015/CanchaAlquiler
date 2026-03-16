<script setup lang="ts">
import { reactive } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import AppShell from "../../Components/AppShell.vue";

type Catalogs = {
    sports: Array<{ id: number; name: string }>;
    cities: Array<{
        id: number;
        name: string;
        province: { id: number; name: string };
    }>;
    services: Array<{ id: number; name: string; slug: string }>;
};

type DayHours = {
    day_of_week: number;
    is_open: boolean;
    open_time: string | null;
    close_time: string | null;
};

type TournamentData = {
    id: number;
    sport_id: number | null;
    sport: { id: number; name: string } | null;
    name: string;
    category: string | null;
    format: string | null;
    start_date: string | null;
    end_date: string | null;
    status: "inscripciones_abiertas" | "cupos_limitados" | "cerrado";
    max_teams: number;
    entry_fee: number;
    prize: string | null;
    notes: string | null;
    teams: Array<{
        id: number;
        name: string;
        matches: number;
        wins: number;
        draws: number;
        losses: number;
        goal_diff: number;
        points: number;
        position: number | null;
    }>;
};

type TeamBoardPostData = {
    id: number;
    sport_id: number | null;
    sport: { id: number; name: string } | null;
    kind: "falta_jugador" | "busco_rival" | "falta_equipo";
    title: string;
    level: string | null;
    needed_players: number | null;
    play_day: string | null;
    play_time: string | null;
    contact: string;
    notes: string | null;
    status: "activo" | "cerrado";
};

type ComplexData = {
    id: number;
    name: string;
    address_line: string;
    city: { id: number; name: string; province: { id: number; name: string } };
    courts: Array<{
        id: number;
        name: string;
        sport_id: number;
        sport: { id: number; name: string };
        surface_type: string;
        players_capacity: number;
        slot_duration_minutes: number;
        base_price: string;
        status: "activa" | "inactiva" | "mantenimiento";
    }>;
    opening_hours: Array<{
        id: number;
        day_of_week: number;
        is_open: boolean;
        open_time: string | null;
        close_time: string | null;
    }>;
    policy: {
        deposit_percent: number;
        cancel_limit_minutes: number;
        refund_percent_before_limit: number;
        no_show_penalty_percent: number;
    } | null;
    stats: {
        income_month: number;
        reservations_confirmed_month: number;
    };
    daily_reservations: Array<{
        id: number;
        code: string;
        status: string;
        start_at: string;
        end_at: string;
        client: { name: string; email: string; phone: string | null };
        court: { name: string };
    }>;
    tournaments: TournamentData[];
    team_board_posts: TeamBoardPostData[];
};

const props = defineProps<{
    selectedDate: string;
    catalogs: Catalogs;
    complexes: ComplexData[];
}>();

const dayLabels: Record<number, string> = {
    1: "Lunes",
    2: "Martes",
    3: "Miercoles",
    4: "Jueves",
    5: "Viernes",
    6: "Sabado",
    7: "Domingo",
};

const dateFilter = useForm({ date: props.selectedDate });

const createComplexForm = useForm({
    city_id: "",
    name: "",
    address_line: "",
    description: "",
    phone_contact: "",
    service_ids: [] as number[],
});

const courtFormsByComplex = reactive<
    Record<
        number,
        {
            sport_id: string;
            name: string;
            surface_type: string;
            players_capacity: string;
            slot_duration_minutes: string;
            base_price: string;
        }
    >
>({});

const policyFormsByComplex = reactive<
    Record<
        number,
        {
            deposit_percent: number;
            cancel_limit_minutes: number;
            refund_percent_before_limit: number;
            no_show_penalty_percent: number;
        }
    >
>({});

const openingHoursByComplex = reactive<Record<number, DayHours[]>>({});

const tournamentFormsByComplex = reactive<
    Record<
        number,
        {
            sport_id: string;
            name: string;
            category: string;
            format: string;
            start_date: string;
            end_date: string;
            status: "inscripciones_abiertas" | "cupos_limitados" | "cerrado";
            max_teams: string;
            entry_fee: string;
            prize: string;
            notes: string;
        }
    >
>({});

const teamBoardFormsByComplex = reactive<
    Record<
        number,
        {
            sport_id: string;
            kind: "falta_jugador" | "busco_rival" | "falta_equipo";
            title: string;
            level: string;
            needed_players: string;
            play_day: string;
            play_time: string;
            contact: string;
            notes: string;
            status: "activo" | "cerrado";
        }
    >
>({});

const teamFormsByTournament = reactive<
    Record<
        number,
        {
            name: string;
            matches: string;
            wins: string;
            draws: string;
            losses: string;
            goal_diff: string;
            points: string;
            position: string;
        }
    >
>({});

function initTeamFormForTournament(tournamentId: number): void {
    if (teamFormsByTournament[tournamentId]) {
        return;
    }

    teamFormsByTournament[tournamentId] = {
        name: "",
        matches: "0",
        wins: "0",
        draws: "0",
        losses: "0",
        goal_diff: "0",
        points: "",
        position: "",
    };
}

for (const complex of props.complexes) {
    courtFormsByComplex[complex.id] = {
        sport_id: "",
        name: "",
        surface_type: "cesped_sintetico",
        players_capacity: "10",
        slot_duration_minutes: "60",
        base_price: "25000",
    };

    policyFormsByComplex[complex.id] = {
        deposit_percent: complex.policy?.deposit_percent ?? 30,
        cancel_limit_minutes: complex.policy?.cancel_limit_minutes ?? 180,
        refund_percent_before_limit:
            complex.policy?.refund_percent_before_limit ?? 100,
        no_show_penalty_percent: complex.policy?.no_show_penalty_percent ?? 100,
    };

    const mapByDay = new Map<number, DayHours>();
    for (const row of complex.opening_hours) {
        mapByDay.set(row.day_of_week, {
            day_of_week: row.day_of_week,
            is_open: row.is_open,
            open_time: row.open_time,
            close_time: row.close_time,
        });
    }

    openingHoursByComplex[complex.id] = [1, 2, 3, 4, 5, 6, 7].map((day) => {
        return (
            mapByDay.get(day) ?? {
                day_of_week: day,
                is_open: false,
                open_time: "08:00",
                close_time: "23:00",
            }
        );
    });

    tournamentFormsByComplex[complex.id] = {
        sport_id: "",
        name: "",
        category: "Libre",
        format: "Fase de grupos + eliminacion",
        start_date: props.selectedDate,
        end_date: props.selectedDate,
        status: "inscripciones_abiertas",
        max_teams: "16",
        entry_fee: "0",
        prize: "",
        notes: "",
    };

    teamBoardFormsByComplex[complex.id] = {
        sport_id: "",
        kind: "falta_jugador",
        title: "",
        level: "Intermedio",
        needed_players: "1",
        play_day: "",
        play_time: "",
        contact: "",
        notes: "",
        status: "activo",
    };

    for (const tournament of complex.tournaments) {
        initTeamFormForTournament(tournament.id);
    }
}

function toggleService(serviceId: number): void {
    const exists = createComplexForm.service_ids.includes(serviceId);

    if (exists) {
        createComplexForm.service_ids = createComplexForm.service_ids.filter(
            (id) => id !== serviceId,
        );
        return;
    }

    createComplexForm.service_ids.push(serviceId);
}

function submitDateFilter(): void {
    router.get("/panel/admin-cancha", dateFilter.data(), {
        preserveState: true,
        replace: true,
    });
}

function createComplex(): void {
    createComplexForm.post("/panel/admin-cancha/complejos");
}

function createCourt(complexId: number): void {
    router.post(
        `/panel/admin-cancha/complejos/${complexId}/canchas`,
        courtFormsByComplex[complexId],
    );
}

function createTournament(complexId: number): void {
    const form = tournamentFormsByComplex[complexId];

    router.post(`/panel/admin-cancha/complejos/${complexId}/torneos`, {
        ...form,
        sport_id: form.sport_id === "" ? null : Number(form.sport_id),
        max_teams: Number(form.max_teams),
        entry_fee: Number(form.entry_fee),
    });
}

function createTournamentTeam(complexId: number, tournamentId: number): void {
    initTeamFormForTournament(tournamentId);

    const form = teamFormsByTournament[tournamentId];

    router.post(
        `/panel/admin-cancha/complejos/${complexId}/torneos/${tournamentId}/equipos`,
        {
            name: form.name,
            matches: Number(form.matches),
            wins: Number(form.wins),
            draws: Number(form.draws),
            losses: Number(form.losses),
            goal_diff: Number(form.goal_diff),
            points: form.points === "" ? null : Number(form.points),
            position: form.position === "" ? null : Number(form.position),
        },
    );
}

function createTeamBoardPost(complexId: number): void {
    const form = teamBoardFormsByComplex[complexId];

    router.post(`/panel/admin-cancha/complejos/${complexId}/convocatorias`, {
        ...form,
        sport_id: form.sport_id === "" ? null : Number(form.sport_id),
        needed_players:
            form.needed_players === "" ? null : Number(form.needed_players),
        play_time: form.play_time === "" ? null : form.play_time,
    });
}

function savePolicy(complexId: number): void {
    router.put(
        `/panel/admin-cancha/complejos/${complexId}/politica`,
        policyFormsByComplex[complexId],
    );
}

function saveOpeningHours(complexId: number): void {
    router.put(`/panel/admin-cancha/complejos/${complexId}/horarios`, {
        days: openingHoursByComplex[complexId],
    });
}

function updateCourtStatus(courtId: number, status: string): void {
    router.put(`/panel/admin-cancha/canchas/${courtId}`, { status });
}

function onCourtStatusChange(courtId: number, event: Event): void {
    const target = event.target as HTMLSelectElement | null;

    if (!target) return;

    updateCourtStatus(courtId, target.value);
}

function formatMoney(value: number): string {
    return new Intl.NumberFormat("es-AR", {
        style: "currency",
        currency: "ARS",
        maximumFractionDigits: 0,
    }).format(value);
}

function tournamentStatusLabel(status: TournamentData["status"]): string {
    if (status === "cupos_limitados") {
        return "Cupos limitados";
    }

    if (status === "cerrado") {
        return "Cerrado";
    }

    return "Inscripciones abiertas";
}

function teamBoardKindLabel(kind: TeamBoardPostData["kind"]): string {
    if (kind === "falta_jugador") {
        return "Falta jugador";
    }

    if (kind === "falta_equipo") {
        return "Falta equipo";
    }

    return "Busco rival";
}
</script>

<template>
    <AppShell>
        <section
            class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6"
        >
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-emerald-300">
                        Panel AdminCancha
                    </h1>
                    <p class="text-sm text-slate-300">
                        Gestiona complejos, canchas, horarios y disponibilidad.
                    </p>
                </div>
                <form
                    class="flex items-center gap-2"
                    @submit.prevent="submitDateFilter"
                >
                    <input
                        v-model="dateFilter.date"
                        type="date"
                        class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                    />
                    <button
                        class="rounded-lg bg-emerald-400 px-4 py-2 text-sm font-bold text-slate-950 hover:bg-emerald-300"
                    >
                        Ver grilla
                    </button>
                </form>
            </div>
        </section>

        <section
            class="mt-6 rounded-2xl border border-slate-800 bg-slate-900/60 p-6"
        >
            <h2 class="text-lg font-bold text-emerald-200">Crear complejo</h2>
            <form
                class="mt-4 grid gap-3 md:grid-cols-2"
                @submit.prevent="createComplex"
            >
                <select
                    v-model="createComplexForm.city_id"
                    required
                    class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
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
                    v-model="createComplexForm.name"
                    type="text"
                    placeholder="Nombre del complejo"
                    class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                    required
                />
                <input
                    v-model="createComplexForm.address_line"
                    type="text"
                    placeholder="Direccion"
                    class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm md:col-span-2"
                    required
                />
                <input
                    v-model="createComplexForm.phone_contact"
                    type="text"
                    placeholder="Telefono"
                    class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                />
                <input
                    v-model="createComplexForm.description"
                    type="text"
                    placeholder="Descripcion"
                    class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                />
                <div class="md:col-span-2">
                    <p
                        class="mb-2 text-xs uppercase tracking-wide text-slate-400"
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
                                createComplexForm.service_ids.includes(
                                    service.id,
                                )
                                    ? 'border-emerald-300 bg-emerald-300/10 text-emerald-200'
                                    : 'border-slate-700 text-slate-300'
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

        <section class="mt-6 space-y-6">
            <article
                v-for="complex in props.complexes"
                :key="complex.id"
                class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-bold text-emerald-300">
                            {{ complex.name }}
                        </h2>
                        <p class="text-sm text-slate-300">
                            {{ complex.address_line }} · {{ complex.city.name }}
                        </p>
                    </div>
                    <div class="text-xs text-slate-300">
                        <p>
                            Ingresos mes:
                            <strong>{{
                                formatMoney(complex.stats.income_month)
                            }}</strong>
                        </p>
                        <p>
                            Reservas confirmadas:
                            <strong>{{
                                complex.stats.reservations_confirmed_month
                            }}</strong>
                        </p>
                    </div>
                </div>

                <div class="mt-5 grid gap-6 lg:grid-cols-2">
                    <div
                        class="rounded-xl border border-slate-800 bg-slate-950/70 p-4"
                    >
                        <h3 class="font-semibold text-emerald-200">
                            Agregar cancha
                        </h3>
                        <div class="mt-3 grid gap-2">
                            <input
                                v-model="courtFormsByComplex[complex.id].name"
                                type="text"
                                placeholder="Nombre"
                                class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                            />
                            <select
                                v-model="
                                    courtFormsByComplex[complex.id].sport_id
                                "
                                class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                            >
                                <option value="">Deporte</option>
                                <option
                                    v-for="sport in props.catalogs.sports"
                                    :key="sport.id"
                                    :value="String(sport.id)"
                                >
                                    {{ sport.name }}
                                </option>
                            </select>
                            <select
                                v-model="
                                    courtFormsByComplex[complex.id].surface_type
                                "
                                class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                            >
                                <option value="cesped_sintetico">
                                    Cesped sintetico
                                </option>
                                <option value="cemento">Cemento</option>
                                <option value="polvo_ladrillo">
                                    Polvo ladrillo
                                </option>
                                <option value="otro">Otro</option>
                            </select>
                            <div class="grid grid-cols-3 gap-2">
                                <input
                                    v-model="
                                        courtFormsByComplex[complex.id]
                                            .players_capacity
                                    "
                                    type="number"
                                    min="2"
                                    class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                                    placeholder="Jugadores"
                                />
                                <input
                                    v-model="
                                        courtFormsByComplex[complex.id]
                                            .slot_duration_minutes
                                    "
                                    type="number"
                                    min="30"
                                    class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                                    placeholder="Min turno"
                                />
                                <input
                                    v-model="
                                        courtFormsByComplex[complex.id]
                                            .base_price
                                    "
                                    type="number"
                                    min="0"
                                    class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                                    placeholder="Precio"
                                />
                            </div>
                            <button
                                type="button"
                                class="rounded-md bg-emerald-400 px-3 py-2 text-sm font-bold text-slate-950"
                                @click="createCourt(complex.id)"
                            >
                                Crear cancha
                            </button>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-slate-800 bg-slate-950/70 p-4"
                    >
                        <h3 class="font-semibold text-emerald-200">
                            Politica de reserva
                        </h3>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <input
                                v-model.number="
                                    policyFormsByComplex[complex.id]
                                        .deposit_percent
                                "
                                type="number"
                                min="0"
                                max="100"
                                class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                                placeholder="% sena"
                            />
                            <input
                                v-model.number="
                                    policyFormsByComplex[complex.id]
                                        .cancel_limit_minutes
                                "
                                type="number"
                                min="0"
                                class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                                placeholder="Min cancelacion"
                            />
                            <input
                                v-model.number="
                                    policyFormsByComplex[complex.id]
                                        .refund_percent_before_limit
                                "
                                type="number"
                                min="0"
                                max="100"
                                class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                                placeholder="% devolucion"
                            />
                            <input
                                v-model.number="
                                    policyFormsByComplex[complex.id]
                                        .no_show_penalty_percent
                                "
                                type="number"
                                min="0"
                                max="100"
                                class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                                placeholder="% no-show"
                            />
                        </div>
                        <button
                            type="button"
                            class="mt-3 rounded-md bg-sky-400 px-3 py-2 text-sm font-bold text-slate-950"
                            @click="savePolicy(complex.id)"
                        >
                            Guardar politica
                        </button>
                    </div>
                </div>

                <div
                    class="mt-6 rounded-xl border border-slate-800 bg-slate-950/70 p-4"
                >
                    <h3 class="font-semibold text-emerald-200">
                        Horarios habiles por dia
                    </h3>
                    <div class="mt-3 grid gap-2">
                        <div
                            v-for="day in openingHoursByComplex[complex.id]"
                            :key="day.day_of_week"
                            class="grid grid-cols-[110px_90px_1fr_1fr] items-center gap-2"
                        >
                            <span class="text-sm text-slate-300">{{
                                dayLabels[day.day_of_week]
                            }}</span>
                            <label
                                class="flex items-center gap-1 text-xs text-slate-300"
                            >
                                <input v-model="day.is_open" type="checkbox" />
                                Abierto
                            </label>
                            <input
                                v-model="day.open_time"
                                type="time"
                                :disabled="!day.is_open"
                                class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                            />
                            <input
                                v-model="day.close_time"
                                type="time"
                                :disabled="!day.is_open"
                                class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                            />
                        </div>
                    </div>
                    <button
                        type="button"
                        class="mt-3 rounded-md bg-amber-300 px-3 py-2 text-sm font-bold text-slate-950"
                        @click="saveOpeningHours(complex.id)"
                    >
                        Guardar horarios
                    </button>
                </div>

                <div
                    class="mt-6 rounded-xl border border-slate-800 bg-slate-950/70 p-4"
                >
                    <h3 class="font-semibold text-emerald-200">
                        Canchas y estado
                    </h3>
                    <div class="mt-3 grid gap-2">
                        <div
                            v-for="court in complex.courts"
                            :key="court.id"
                            class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-slate-800 bg-slate-900 px-3 py-2 text-sm"
                        >
                            <span
                                >{{ court.name }} · {{ court.sport.name }} ·
                                {{ court.slot_duration_minutes }} min</span
                            >
                            <select
                                :value="court.status"
                                class="rounded-md border border-slate-700 bg-slate-950 px-2 py-1 text-xs"
                                @change="onCourtStatusChange(court.id, $event)"
                            >
                                <option value="activa">Activa</option>
                                <option value="inactiva">Inactiva</option>
                                <option value="mantenimiento">
                                    Mantenimiento
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-6 rounded-xl border border-slate-800 bg-slate-950/70 p-4"
                >
                    <h3 class="font-semibold text-emerald-200">
                        Torneos y ranking
                    </h3>

                    <div class="mt-3 grid gap-2 lg:grid-cols-3">
                        <select
                            v-model="
                                tournamentFormsByComplex[complex.id].sport_id
                            "
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        >
                            <option value="">Multideporte</option>
                            <option
                                v-for="sport in props.catalogs.sports"
                                :key="sport.id"
                                :value="String(sport.id)"
                            >
                                {{ sport.name }}
                            </option>
                        </select>
                        <input
                            v-model="tournamentFormsByComplex[complex.id].name"
                            type="text"
                            placeholder="Nombre del torneo"
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        />
                        <input
                            v-model="
                                tournamentFormsByComplex[complex.id].category
                            "
                            type="text"
                            placeholder="Categoria"
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        />
                        <input
                            v-model="
                                tournamentFormsByComplex[complex.id].format
                            "
                            type="text"
                            placeholder="Formato"
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        />
                        <input
                            v-model="
                                tournamentFormsByComplex[complex.id].start_date
                            "
                            type="date"
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        />
                        <input
                            v-model="
                                tournamentFormsByComplex[complex.id].end_date
                            "
                            type="date"
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        />
                        <select
                            v-model="
                                tournamentFormsByComplex[complex.id].status
                            "
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        >
                            <option value="inscripciones_abiertas">
                                Inscripciones abiertas
                            </option>
                            <option value="cupos_limitados">
                                Cupos limitados
                            </option>
                            <option value="cerrado">Cerrado</option>
                        </select>
                        <input
                            v-model="
                                tournamentFormsByComplex[complex.id].max_teams
                            "
                            type="number"
                            min="2"
                            max="64"
                            placeholder="Max equipos"
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        />
                        <input
                            v-model="
                                tournamentFormsByComplex[complex.id].entry_fee
                            "
                            type="number"
                            min="0"
                            placeholder="Inscripcion"
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        />
                        <input
                            v-model="tournamentFormsByComplex[complex.id].prize"
                            type="text"
                            placeholder="Premio"
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        />
                    </div>

                    <textarea
                        v-model="tournamentFormsByComplex[complex.id].notes"
                        rows="2"
                        class="mt-2 w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        placeholder="Notas del torneo"
                    />

                    <button
                        type="button"
                        class="mt-3 rounded-md bg-emerald-400 px-3 py-2 text-sm font-bold text-slate-950"
                        @click="createTournament(complex.id)"
                    >
                        Crear torneo
                    </button>

                    <div class="mt-4 space-y-3">
                        <article
                            v-for="tournament in complex.tournaments"
                            :key="tournament.id"
                            class="rounded-md border border-slate-800 bg-slate-900 p-3"
                        >
                            <div
                                class="flex flex-wrap items-start justify-between gap-2"
                            >
                                <div>
                                    <p class="font-semibold text-emerald-200">
                                        {{ tournament.name }}
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        {{
                                            tournament.sport?.name ||
                                            "Multideporte"
                                        }}
                                        ·
                                        {{
                                            tournament.category ||
                                            "Sin categoria"
                                        }}
                                    </p>
                                </div>
                                <span
                                    class="rounded-md border border-emerald-300/30 bg-emerald-400/10 px-2 py-1 text-xs text-emerald-200"
                                >
                                    {{
                                        tournamentStatusLabel(tournament.status)
                                    }}
                                </span>
                            </div>

                            <p class="mt-1 text-xs text-slate-400">
                                {{ tournament.start_date }} a
                                {{ tournament.end_date }} · Equipos
                                {{ tournament.teams.length }} /
                                {{ tournament.max_teams }} · Inscripcion
                                {{ formatMoney(tournament.entry_fee) }}
                            </p>

                            <div class="mt-3 grid gap-2 md:grid-cols-4">
                                <input
                                    v-model="
                                        teamFormsByTournament[tournament.id]
                                            .name
                                    "
                                    type="text"
                                    placeholder="Equipo"
                                    class="rounded-md border border-slate-700 bg-slate-950 px-2 py-1 text-xs"
                                />
                                <input
                                    v-model="
                                        teamFormsByTournament[tournament.id]
                                            .matches
                                    "
                                    type="number"
                                    min="0"
                                    placeholder="PJ"
                                    class="rounded-md border border-slate-700 bg-slate-950 px-2 py-1 text-xs"
                                />
                                <input
                                    v-model="
                                        teamFormsByTournament[tournament.id]
                                            .wins
                                    "
                                    type="number"
                                    min="0"
                                    placeholder="PG"
                                    class="rounded-md border border-slate-700 bg-slate-950 px-2 py-1 text-xs"
                                />
                                <input
                                    v-model="
                                        teamFormsByTournament[tournament.id]
                                            .draws
                                    "
                                    type="number"
                                    min="0"
                                    placeholder="PE"
                                    class="rounded-md border border-slate-700 bg-slate-950 px-2 py-1 text-xs"
                                />
                                <input
                                    v-model="
                                        teamFormsByTournament[tournament.id]
                                            .losses
                                    "
                                    type="number"
                                    min="0"
                                    placeholder="PP"
                                    class="rounded-md border border-slate-700 bg-slate-950 px-2 py-1 text-xs"
                                />
                                <input
                                    v-model="
                                        teamFormsByTournament[tournament.id]
                                            .goal_diff
                                    "
                                    type="number"
                                    placeholder="DG"
                                    class="rounded-md border border-slate-700 bg-slate-950 px-2 py-1 text-xs"
                                />
                                <input
                                    v-model="
                                        teamFormsByTournament[tournament.id]
                                            .points
                                    "
                                    type="number"
                                    min="0"
                                    placeholder="Pts (opc)"
                                    class="rounded-md border border-slate-700 bg-slate-950 px-2 py-1 text-xs"
                                />
                                <div class="flex gap-2">
                                    <input
                                        v-model="
                                            teamFormsByTournament[tournament.id]
                                                .position
                                        "
                                        type="number"
                                        min="1"
                                        placeholder="Pos (opc)"
                                        class="w-full rounded-md border border-slate-700 bg-slate-950 px-2 py-1 text-xs"
                                    />
                                    <button
                                        type="button"
                                        class="rounded-md bg-sky-400 px-2 py-1 text-xs font-bold text-slate-950"
                                        @click="
                                            createTournamentTeam(
                                                complex.id,
                                                tournament.id,
                                            )
                                        "
                                    >
                                        Guardar
                                    </button>
                                </div>
                            </div>

                            <div
                                v-if="tournament.teams.length"
                                class="mt-3 overflow-x-auto"
                            >
                                <table class="min-w-full text-xs">
                                    <thead class="text-slate-400">
                                        <tr>
                                            <th class="px-2 py-1 text-left">
                                                Pos
                                            </th>
                                            <th class="px-2 py-1 text-left">
                                                Equipo
                                            </th>
                                            <th class="px-2 py-1 text-left">
                                                PJ
                                            </th>
                                            <th class="px-2 py-1 text-left">
                                                PG
                                            </th>
                                            <th class="px-2 py-1 text-left">
                                                PE
                                            </th>
                                            <th class="px-2 py-1 text-left">
                                                PP
                                            </th>
                                            <th class="px-2 py-1 text-left">
                                                DG
                                            </th>
                                            <th class="px-2 py-1 text-left">
                                                Pts
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="team in tournament.teams"
                                            :key="team.id"
                                            class="border-t border-slate-800"
                                        >
                                            <td class="px-2 py-1">
                                                {{ team.position || "-" }}
                                            </td>
                                            <td class="px-2 py-1">
                                                {{ team.name }}
                                            </td>
                                            <td class="px-2 py-1">
                                                {{ team.matches }}
                                            </td>
                                            <td class="px-2 py-1">
                                                {{ team.wins }}
                                            </td>
                                            <td class="px-2 py-1">
                                                {{ team.draws }}
                                            </td>
                                            <td class="px-2 py-1">
                                                {{ team.losses }}
                                            </td>
                                            <td class="px-2 py-1">
                                                {{ team.goal_diff }}
                                            </td>
                                            <td
                                                class="px-2 py-1 font-semibold text-emerald-300"
                                            >
                                                {{ team.points }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </article>

                        <p
                            v-if="complex.tournaments.length === 0"
                            class="text-xs text-slate-400"
                        >
                            Aun no hay torneos cargados.
                        </p>
                    </div>
                </div>

                <div
                    class="mt-6 rounded-xl border border-slate-800 bg-slate-950/70 p-4"
                >
                    <h3 class="font-semibold text-emerald-200">
                        Convocatorias de comunidad
                    </h3>

                    <div class="mt-3 grid gap-2 md:grid-cols-3">
                        <select
                            v-model="
                                teamBoardFormsByComplex[complex.id].sport_id
                            "
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        >
                            <option value="">Todos los deportes</option>
                            <option
                                v-for="sport in props.catalogs.sports"
                                :key="sport.id"
                                :value="String(sport.id)"
                            >
                                {{ sport.name }}
                            </option>
                        </select>
                        <select
                            v-model="teamBoardFormsByComplex[complex.id].kind"
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        >
                            <option value="falta_jugador">Falta jugador</option>
                            <option value="busco_rival">Busco rival</option>
                            <option value="falta_equipo">Falta equipo</option>
                        </select>
                        <input
                            v-model="teamBoardFormsByComplex[complex.id].title"
                            type="text"
                            placeholder="Titulo"
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        />
                        <input
                            v-model="teamBoardFormsByComplex[complex.id].level"
                            type="text"
                            placeholder="Nivel"
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        />
                        <input
                            v-model="
                                teamBoardFormsByComplex[complex.id]
                                    .needed_players
                            "
                            type="number"
                            min="1"
                            placeholder="Jugadores faltantes"
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        />
                        <input
                            v-model="
                                teamBoardFormsByComplex[complex.id].play_day
                            "
                            type="text"
                            placeholder="Dia"
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        />
                        <input
                            v-model="
                                teamBoardFormsByComplex[complex.id].play_time
                            "
                            type="time"
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        />
                        <input
                            v-model="
                                teamBoardFormsByComplex[complex.id].contact
                            "
                            type="text"
                            placeholder="Contacto"
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        />
                        <select
                            v-model="teamBoardFormsByComplex[complex.id].status"
                            class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        >
                            <option value="activo">Activo</option>
                            <option value="cerrado">Cerrado</option>
                        </select>
                    </div>

                    <textarea
                        v-model="teamBoardFormsByComplex[complex.id].notes"
                        rows="2"
                        class="mt-2 w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                        placeholder="Detalles de la convocatoria"
                    />

                    <button
                        type="button"
                        class="mt-3 rounded-md bg-emerald-400 px-3 py-2 text-sm font-bold text-slate-950"
                        @click="createTeamBoardPost(complex.id)"
                    >
                        Publicar convocatoria
                    </button>

                    <div class="mt-4 space-y-2">
                        <div
                            v-for="post in complex.team_board_posts"
                            :key="post.id"
                            class="rounded-md border border-slate-800 bg-slate-900 px-3 py-2 text-xs"
                        >
                            <p class="font-semibold text-slate-100">
                                {{ post.title }}
                            </p>
                            <p class="text-slate-400">
                                {{ teamBoardKindLabel(post.kind) }} ·
                                {{ post.sport?.name || "Todos los deportes" }}
                                · {{ post.level || "Sin nivel" }}
                            </p>
                            <p class="text-slate-400">
                                {{ post.play_day || "Dia a coordinar" }} ·
                                {{ post.play_time || "Hora a coordinar" }} ·
                                {{ post.contact }}
                            </p>
                        </div>

                        <p
                            v-if="complex.team_board_posts.length === 0"
                            class="text-xs text-slate-400"
                        >
                            Todavia no hay convocatorias publicadas.
                        </p>
                    </div>
                </div>

                <div
                    class="mt-6 rounded-xl border border-slate-800 bg-slate-950/70 p-4"
                >
                    <h3 class="font-semibold text-emerald-200">
                        Reservas del dia seleccionado
                    </h3>
                    <div
                        v-if="complex.daily_reservations.length"
                        class="mt-3 space-y-2"
                    >
                        <div
                            v-for="row in complex.daily_reservations"
                            :key="row.id"
                            class="rounded-md border border-slate-800 bg-slate-900 px-3 py-2 text-sm"
                        >
                            <p>
                                <strong>{{ row.start_at }}</strong> →
                                {{ row.end_at }} · {{ row.court.name }} ·
                                {{ row.client.name }}
                            </p>
                            <p class="text-xs text-slate-400">
                                {{ row.client.email }} · {{ row.status }}
                            </p>
                        </div>
                    </div>
                    <p v-else class="mt-2 text-sm text-slate-400">
                        No hay reservas para esta fecha.
                    </p>
                </div>
            </article>
        </section>
    </AppShell>
</template>
