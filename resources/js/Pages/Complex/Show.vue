<script setup lang="ts">
import { computed, ref } from "vue";
import { Link, router, useForm, usePage } from "@inertiajs/vue3";
import AppShell from "../../Components/AppShell.vue";

type Sport = { id: number; name: string; slug: string };

type Court = {
    id: number;
    name: string;
    surface_type: string;
    players_capacity: number;
    slot_duration_minutes: number;
    base_price: number;
    available_slots: Array<{
        start_time: string;
        end_time: string;
        label: string;
    }>;
    sport: Sport;
};

type AuthUser = {
    id: number;
    role: "cliente" | "admin_cancha" | "super_admin";
};

type Tournament = {
    id: number;
    name: string;
    sport: string | null;
    category: string;
    format: string;
    start_date: string;
    end_date: string;
    status: "inscripciones_abiertas" | "cupos_limitados" | "cerrado";
    teams_registered: number;
    max_teams: number;
    entry_fee: number;
    prize: string;
    notes: string | null;
};

type TeamBoardPost = {
    id: number;
    kind: "falta_jugador" | "busco_rival" | "falta_equipo";
    title: string;
    sport: string;
    level: string | null;
    needed_players: number;
    play_day: string | null;
    play_time: string | null;
    contact: string;
    notes: string | null;
};

type RankingTeam = {
    position: number;
    team: string;
    matches: number;
    wins: number;
    draws: number;
    losses: number;
    goal_diff: number;
    points: number;
};

type TabKey = "disponibilidad" | "torneos" | "equipos" | "ranking";

const props = defineProps<{
    appName: string;
    complex: {
        id: number;
        name: string;
        slug: string;
        address: string;
        city: string | null;
        province: string | null;
        description: string | null;
        phone_contact: string | null;
        latitude: number | null;
        longitude: number | null;
        photo_url: string;
        map_url: string;
        map_embed_url: string | null;
    };
    summary: {
        total_courts: number;
        displayed_courts: number;
        available_courts: number;
        sports: Sport[];
    };
    filters: {
        sport_id: number | null;
        date: string;
        start_time: string;
        end_time: string | null;
    };
    courts: Court[];
    community: {
        tournaments: Tournament[];
        team_board: TeamBoardPost[];
        ranking_tournament: {
            id: number;
            name: string;
            sport: string | null;
        } | null;
        ranking: RankingTeam[];
        padel_category_rankings: Array<{
            tournament_id: number;
            tournament_name: string;
            category: string;
            ranking: RankingTeam[];
        }>;
        padel_individual_tournament: {
            id: number;
            name: string;
            category: string | null;
        } | null;
        padel_individual_ranking: RankingTeam[];
    };
}>();

const page = usePage<{ auth: { user: AuthUser | null } }>();
const authUser = computed(() => page.props.auth?.user ?? null);

const filterForm = useForm({
    sport_id: props.filters.sport_id ? String(props.filters.sport_id) : "",
    date: props.filters.date,
    start_time: props.filters.start_time,
    end_time: props.filters.end_time ?? "",
});

const reserveForm = useForm({
    court_id: "",
    date: props.filters.date,
    start_time: props.filters.start_time,
    end_time: props.filters.end_time ?? "",
});

const hasExactRange = computed(() => Boolean(filterForm.end_time));
const hasPadelCategoryRankings = computed(
    () => props.community.padel_category_rankings.length > 0,
);
const hasPadelIndividualRanking = computed(
    () => props.community.padel_individual_ranking.length > 0,
);
const activeTab = ref<TabKey>("disponibilidad");

const tabs: Array<{ key: TabKey; label: string }> = [
    { key: "disponibilidad", label: "Disponibilidad" },
    { key: "torneos", label: "Torneos" },
    { key: "equipos", label: "Me falta jugador/equipo" },
    { key: "ranking", label: "Ranking" },
];

function submitFilters(): void {
    router.get(`/complejos/${props.complex.slug}`, filterForm.data(), {
        preserveState: true,
        replace: true,
    });
}

function reserve(courtId: number, startTime: string, endTime: string): void {
    reserveForm.court_id = String(courtId);
    reserveForm.date = filterForm.date;
    reserveForm.start_time = startTime;
    reserveForm.end_time = endTime;

    reserveForm.post("/panel/cliente/reservas");
}

function formatPrice(value: number): string {
    return new Intl.NumberFormat("es-AR", {
        style: "currency",
        currency: "ARS",
        maximumFractionDigits: 0,
    }).format(value);
}

function formatDate(value: string): string {
    const [year, month, day] = value.split("-");
    return `${day}/${month}/${year}`;
}

function footballFormatLabel(court: Court): string | null {
    if (court.sport.slug !== "futbol") {
        return null;
    }

    if (court.players_capacity <= 10) {
        return "Futbol 5";
    }

    if (court.players_capacity >= 22) {
        return "Futbol 11";
    }

    return `Futbol ${Math.max(1, Math.round(court.players_capacity / 2))}`;
}

function tournamentStatusLabel(status: Tournament["status"]): string {
    if (status === "cupos_limitados") {
        return "Cupos limitados";
    }

    if (status === "cerrado") {
        return "Cerrado";
    }

    return "Inscripciones abiertas";
}

function teamBoardKindLabel(kind: TeamBoardPost["kind"]): string {
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
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p
                        class="text-xs uppercase tracking-[0.25em] text-emerald-300"
                    >
                        Perfil del complejo
                    </p>
                    <h1 class="mt-2 text-3xl font-black text-emerald-200">
                        {{ props.complex.name }}
                    </h1>
                    <p class="mt-2 text-sm text-slate-300">
                        {{ props.complex.address }} · {{ props.complex.city }} ·
                        {{ props.complex.province }}
                    </p>
                </div>

                <Link
                    href="/"
                    class="rounded-lg border border-slate-700 px-4 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-900"
                >
                    Volver al buscador
                </Link>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <div
                    class="rounded-xl border border-slate-800 bg-slate-950/50 p-4"
                >
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Canchas
                    </p>
                    <p class="mt-1 text-2xl font-black text-emerald-300">
                        {{ props.summary.total_courts }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-slate-800 bg-slate-950/50 p-4"
                >
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        En vista
                    </p>
                    <p class="mt-1 text-2xl font-black text-slate-100">
                        {{ props.summary.displayed_courts }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-slate-800 bg-slate-950/50 p-4"
                >
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Con horarios libres
                    </p>
                    <p class="mt-1 text-2xl font-black text-emerald-300">
                        {{ props.summary.available_courts }}
                    </p>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <span
                    v-for="sport in props.summary.sports"
                    :key="sport.id"
                    class="rounded-md border border-emerald-300/30 bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-200"
                >
                    {{ sport.name }}
                </span>
            </div>
        </section>

        <section class="mt-6 grid gap-4 lg:grid-cols-3">
            <div class="overflow-hidden rounded-xl border border-slate-800">
                <img
                    :src="props.complex.photo_url"
                    :alt="`Foto referencial de ${props.complex.name}`"
                    class="h-56 w-full object-cover"
                />
            </div>

            <div class="rounded-xl border border-slate-800 p-4 lg:col-span-2">
                <p
                    v-if="props.complex.description"
                    class="text-sm text-slate-300"
                >
                    {{ props.complex.description }}
                </p>
                <p class="mt-1 text-xs text-slate-400">
                    Contacto:
                    {{ props.complex.phone_contact || "No informado" }}
                </p>

                <iframe
                    v-if="props.complex.map_embed_url"
                    :src="props.complex.map_embed_url"
                    class="mt-3 h-56 w-full rounded-lg border border-slate-700"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                />

                <a
                    :href="props.complex.map_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-3 inline-block rounded-md border border-slate-600 px-3 py-1 text-xs font-semibold text-slate-200 hover:bg-slate-900"
                >
                    Ver ubicacion en mapa
                </a>
            </div>
        </section>

        <section
            class="mt-6 rounded-2xl border border-slate-800 bg-slate-900/60 p-4"
        >
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="rounded-lg px-4 py-2 text-xs font-semibold transition"
                    :class="
                        activeTab === tab.key
                            ? 'bg-emerald-400 text-slate-950'
                            : 'border border-slate-700 text-slate-300 hover:bg-slate-900'
                    "
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }}
                </button>
            </div>
        </section>

        <section
            v-if="activeTab === 'disponibilidad'"
            class="mt-6 rounded-2xl border border-slate-800 bg-slate-900/60 p-6"
        >
            <h2 class="text-lg font-bold text-emerald-300">
                Ver disponibilidad
            </h2>
            <p class="mt-1 text-sm text-slate-300">
                Selecciona fecha y hora para ver los turnos que estan libres en
                este complejo.
            </p>

            <form
                class="mt-4 grid gap-3 md:grid-cols-2 lg:grid-cols-5"
                @submit.prevent="submitFilters"
            >
                <select
                    v-model="filterForm.sport_id"
                    class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                >
                    <option value="">Todos los deportes</option>
                    <option
                        v-for="sport in props.summary.sports"
                        :key="sport.id"
                        :value="String(sport.id)"
                    >
                        {{ sport.name }}
                    </option>
                </select>

                <input
                    v-model="filterForm.date"
                    type="date"
                    class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                    required
                />

                <input
                    v-model="filterForm.start_time"
                    type="time"
                    step="60"
                    class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                    required
                />

                <input
                    v-model="filterForm.end_time"
                    type="time"
                    step="60"
                    class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                    placeholder="Opcional"
                />

                <button
                    type="submit"
                    class="rounded-lg bg-emerald-400 px-4 py-2 text-sm font-bold text-slate-950 hover:bg-emerald-300"
                >
                    Actualizar disponibilidad
                </button>
            </form>

            <p class="mt-2 text-xs text-slate-400">
                Si dejas hora fin vacia, se listan turnos libres desde la hora
                de inicio en adelante.
            </p>
        </section>

        <section v-if="activeTab === 'disponibilidad'" class="mt-6 space-y-4">
            <div
                v-if="props.courts.length === 0"
                class="rounded-2xl border border-dashed border-slate-700 p-6 text-sm text-slate-300"
            >
                No hay canchas para el deporte seleccionado en este complejo.
            </div>

            <article
                v-for="court in props.courts"
                :key="court.id"
                class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-emerald-200">
                            {{ court.name }}
                        </h3>
                        <p class="text-xs text-slate-400">
                            {{ court.sport.name }} ·
                            {{ court.surface_type.replaceAll("_", " ") }}
                        </p>
                        <p
                            v-if="footballFormatLabel(court)"
                            class="mt-1 text-xs font-semibold text-emerald-300"
                        >
                            {{ footballFormatLabel(court) }}
                        </p>
                    </div>
                    <span class="text-sm font-bold text-emerald-300">
                        {{ formatPrice(court.base_price) }}
                    </span>
                </div>

                <p class="mt-2 text-xs text-slate-400">
                    {{ court.players_capacity }} jugadores · turnos de
                    {{ court.slot_duration_minutes }} min
                </p>

                <div class="mt-3">
                    <p class="text-xs font-semibold text-slate-300">
                        <template v-if="hasExactRange"
                            >Disponibilidad del rango seleccionado</template
                        >
                        <template v-else>
                            Horarios libres desde {{ filterForm.start_time }}
                        </template>
                    </p>

                    <div
                        v-if="court.available_slots.length"
                        class="mt-2 flex flex-wrap gap-2"
                    >
                        <template
                            v-for="slot in court.available_slots"
                            :key="`${court.id}-${slot.start_time}-${slot.end_time}`"
                        >
                            <button
                                v-if="authUser && authUser.role === 'cliente'"
                                type="button"
                                class="rounded-md bg-emerald-400/20 px-3 py-1 text-xs font-semibold text-emerald-200 hover:bg-emerald-400/30"
                                @click="
                                    reserve(
                                        court.id,
                                        slot.start_time,
                                        slot.end_time,
                                    )
                                "
                            >
                                Reservar {{ slot.label }}
                            </button>
                            <span
                                v-else
                                class="rounded-md border border-slate-700 px-3 py-1 text-xs text-slate-300"
                            >
                                {{ slot.label }}
                            </span>
                        </template>
                    </div>

                    <p v-else class="mt-2 text-xs text-amber-300">
                        Sin turnos libres para este filtro.
                    </p>
                </div>

                <Link
                    v-if="!authUser"
                    href="/login"
                    class="mt-3 inline-block rounded-lg border border-slate-600 px-4 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-900"
                >
                    Inicia sesion para reservar
                </Link>
                <span
                    v-else-if="authUser.role !== 'cliente'"
                    class="mt-3 block text-xs text-amber-300"
                >
                    Tu rol no puede reservar turnos de cliente.
                </span>
            </article>
        </section>

        <section v-else-if="activeTab === 'torneos'" class="mt-6 space-y-4">
            <article
                v-for="tournament in props.community.tournaments"
                :key="tournament.id"
                class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-emerald-200">
                            {{ tournament.name }}
                        </h3>
                        <p class="text-xs text-slate-400">
                            {{ tournament.sport || "Multideporte" }} ·
                            {{ tournament.category || "Sin categoria" }} ·
                            {{ tournament.format || "Formato a definir" }}
                        </p>
                    </div>
                    <span
                        class="rounded-md border border-emerald-300/30 bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-200"
                    >
                        {{ tournamentStatusLabel(tournament.status) }}
                    </span>
                </div>

                <div
                    class="mt-3 grid gap-3 text-xs text-slate-300 md:grid-cols-2 lg:grid-cols-4"
                >
                    <p>Inicio: {{ formatDate(tournament.start_date) }}</p>
                    <p>Fin: {{ formatDate(tournament.end_date) }}</p>
                    <p>
                        Equipos: {{ tournament.teams_registered }} /
                        {{ tournament.max_teams }}
                    </p>
                    <p>Inscripcion: {{ formatPrice(tournament.entry_fee) }}</p>
                </div>

                <p class="mt-2 text-xs text-slate-400">
                    Premio: {{ tournament.prize }}
                </p>
                <p v-if="tournament.notes" class="mt-1 text-xs text-slate-400">
                    {{ tournament.notes }}
                </p>
            </article>

            <div
                v-if="props.community.tournaments.length === 0"
                class="rounded-2xl border border-dashed border-slate-700 p-6 text-sm text-slate-300"
            >
                No hay torneos publicados para este deporte en el complejo.
            </div>
        </section>

        <section v-else-if="activeTab === 'equipos'" class="mt-6 space-y-4">
            <article
                v-for="post in props.community.team_board"
                :key="post.id"
                class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-emerald-200">
                            {{ post.title }}
                        </h3>
                        <p class="text-xs text-slate-400">
                            {{ post.sport }} · Nivel
                            {{ post.level || "sin definir" }}
                        </p>
                    </div>
                    <span
                        class="rounded-md border border-sky-300/30 bg-sky-400/10 px-3 py-1 text-xs font-semibold text-sky-200"
                    >
                        {{ teamBoardKindLabel(post.kind) }}
                    </span>
                </div>

                <div
                    class="mt-3 grid gap-3 text-xs text-slate-300 md:grid-cols-2 lg:grid-cols-4"
                >
                    <p>Dia: {{ post.play_day || "A coordinar" }}</p>
                    <p>Hora: {{ post.play_time || "A coordinar" }}</p>
                    <p>Contacto: {{ post.contact }}</p>
                    <p v-if="post.needed_players > 0">
                        Jugadores faltantes: {{ post.needed_players }}
                    </p>
                    <p v-else>Convocatoria abierta</p>
                </div>

                <p v-if="post.notes" class="mt-2 text-xs text-slate-400">
                    {{ post.notes }}
                </p>
            </article>

            <div
                v-if="props.community.team_board.length === 0"
                class="rounded-2xl border border-dashed border-slate-700 p-6 text-sm text-slate-300"
            >
                No hay convocatorias activas para este deporte.
            </div>
        </section>

        <section v-else class="mt-6 space-y-4">
            <template
                v-if="hasPadelCategoryRankings || hasPadelIndividualRanking"
            >
                <div
                    v-if="hasPadelCategoryRankings"
                    class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4"
                >
                    <h3
                        class="text-sm font-bold uppercase tracking-wide text-emerald-300"
                    >
                        Ranking de padel por categoria
                    </h3>

                    <div class="mt-3 space-y-4">
                        <article
                            v-for="categoryRanking in props.community
                                .padel_category_rankings"
                            :key="categoryRanking.tournament_id"
                            class="overflow-hidden rounded-xl border border-slate-800"
                        >
                            <div
                                class="border-b border-slate-800 bg-slate-950/60 px-4 py-3 text-xs text-slate-300"
                            >
                                <strong>{{ categoryRanking.category }}</strong>
                                · {{ categoryRanking.tournament_name }}
                            </div>
                            <table class="min-w-full text-sm">
                                <thead
                                    class="bg-slate-950/40 text-xs uppercase tracking-wide text-slate-400"
                                >
                                    <tr>
                                        <th class="px-4 py-3 text-left">Pos</th>
                                        <th class="px-4 py-3 text-left">
                                            Pareja/Equipo
                                        </th>
                                        <th class="px-4 py-3 text-left">PJ</th>
                                        <th class="px-4 py-3 text-left">PG</th>
                                        <th class="px-4 py-3 text-left">PE</th>
                                        <th class="px-4 py-3 text-left">PP</th>
                                        <th class="px-4 py-3 text-left">DG</th>
                                        <th class="px-4 py-3 text-left">Pts</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="team in categoryRanking.ranking"
                                        :key="`${categoryRanking.tournament_id}-${team.team}`"
                                        class="border-t border-slate-800 text-slate-200"
                                    >
                                        <td class="px-4 py-3 font-semibold">
                                            {{ team.position }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ team.team }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ team.matches }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ team.wins }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ team.draws }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ team.losses }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ team.goal_diff }}
                                        </td>
                                        <td
                                            class="px-4 py-3 font-bold text-emerald-300"
                                        >
                                            {{ team.points }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </article>
                    </div>
                </div>

                <div
                    v-if="hasPadelIndividualRanking"
                    class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4"
                >
                    <h3
                        class="text-sm font-bold uppercase tracking-wide text-emerald-300"
                    >
                        Ranking individual de padel
                    </h3>
                    <p
                        v-if="props.community.padel_individual_tournament"
                        class="mt-1 text-xs text-slate-400"
                    >
                        Torneo:
                        {{ props.community.padel_individual_tournament.name }}
                        <span
                            v-if="
                                props.community.padel_individual_tournament
                                    .category
                            "
                        >
                            ·
                            {{
                                props.community.padel_individual_tournament
                                    .category
                            }}
                        </span>
                    </p>

                    <div
                        class="mt-3 overflow-hidden rounded-xl border border-slate-800"
                    >
                        <table class="min-w-full text-sm">
                            <thead
                                class="bg-slate-950/40 text-xs uppercase tracking-wide text-slate-400"
                            >
                                <tr>
                                    <th class="px-4 py-3 text-left">Pos</th>
                                    <th class="px-4 py-3 text-left">
                                        Jugador/a
                                    </th>
                                    <th class="px-4 py-3 text-left">PJ</th>
                                    <th class="px-4 py-3 text-left">PG</th>
                                    <th class="px-4 py-3 text-left">PE</th>
                                    <th class="px-4 py-3 text-left">PP</th>
                                    <th class="px-4 py-3 text-left">DG</th>
                                    <th class="px-4 py-3 text-left">Pts</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="player in props.community
                                        .padel_individual_ranking"
                                    :key="player.team"
                                    class="border-t border-slate-800 text-slate-200"
                                >
                                    <td class="px-4 py-3 font-semibold">
                                        {{ player.position }}
                                    </td>
                                    <td class="px-4 py-3">{{ player.team }}</td>
                                    <td class="px-4 py-3">
                                        {{ player.matches }}
                                    </td>
                                    <td class="px-4 py-3">{{ player.wins }}</td>
                                    <td class="px-4 py-3">
                                        {{ player.draws }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ player.losses }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ player.goal_diff }}
                                    </td>
                                    <td
                                        class="px-4 py-3 font-bold text-emerald-300"
                                    >
                                        {{ player.points }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            <template v-else>
                <div
                    v-if="props.community.ranking_tournament"
                    class="rounded-xl border border-slate-800 bg-slate-900/60 p-3 text-xs text-slate-300"
                >
                    Ranking del torneo:
                    <strong>{{
                        props.community.ranking_tournament.name
                    }}</strong>
                    <span v-if="props.community.ranking_tournament.sport">
                        · {{ props.community.ranking_tournament.sport }}
                    </span>
                </div>

                <div
                    class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60"
                >
                    <table class="min-w-full text-sm">
                        <thead
                            class="bg-slate-950/60 text-xs uppercase tracking-wide text-slate-400"
                        >
                            <tr>
                                <th class="px-4 py-3 text-left">Pos</th>
                                <th class="px-4 py-3 text-left">Equipo</th>
                                <th class="px-4 py-3 text-left">PJ</th>
                                <th class="px-4 py-3 text-left">PG</th>
                                <th class="px-4 py-3 text-left">PE</th>
                                <th class="px-4 py-3 text-left">PP</th>
                                <th class="px-4 py-3 text-left">DG</th>
                                <th class="px-4 py-3 text-left">Pts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="team in props.community.ranking"
                                :key="team.position"
                                class="border-t border-slate-800 text-slate-200"
                            >
                                <td class="px-4 py-3 font-semibold">
                                    {{ team.position }}
                                </td>
                                <td class="px-4 py-3">{{ team.team }}</td>
                                <td class="px-4 py-3">{{ team.matches }}</td>
                                <td class="px-4 py-3">{{ team.wins }}</td>
                                <td class="px-4 py-3">{{ team.draws }}</td>
                                <td class="px-4 py-3">{{ team.losses }}</td>
                                <td class="px-4 py-3">{{ team.goal_diff }}</td>
                                <td
                                    class="px-4 py-3 font-bold text-emerald-300"
                                >
                                    {{ team.points }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.community.ranking.length === 0"
                    class="rounded-2xl border border-dashed border-slate-700 p-6 text-sm text-slate-300"
                >
                    No hay ranking cargado todavia para el deporte seleccionado.
                </div>
            </template>
        </section>
    </AppShell>
</template>
