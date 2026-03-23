<script setup lang="ts">
import { reactive, ref, onMounted, computed, nextTick } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import AppShell from "../../Components/AppShell.vue";
import axios from "axios";
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    ArcElement,
    Filler,
} from "chart.js";
import { Bar, Line, Doughnut } from "vue-chartjs";
import {
    TrendingUp,
    Users,
    Calendar,
    DollarSign,
    Clock,
    CheckCircle,
    Target,
    ArrowUpRight,
    Activity,
    Smartphone,
    CreditCard,
    AlertCircle,
    ChevronRight,
    Search,
} from "lucide-vue-next";

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    ArcElement,
    Filler,
);

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
        most_rented_day: string;
        most_rented_time: string;
    };
    monthly_reserved_dates: string[];
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
    recurring_reservations: Array<{
        id: number;
        court_id: number;
        court: { name: string };
        day_of_week: number;
        start_time: string;
        end_time: string;
        client_name: string;
        client_phone: string | null;
        client_user_id: number | null;
        is_active: boolean;
        notes: string | null;
    }>;
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

const activeTab = ref("reservas"); // 'reservas', 'canchas', 'clientes', 'horarios', 'torneos', 'comunidad', 'reportes'

const dateFilter = useForm({ date: props.selectedDate });

const editComplexForm = useForm({
    name: "",
    address_line: "",
    description: "",
    phone_contact: "",
    instagram_url: "",
    facebook_url: "",
    service_ids: [] as number[],
});
const showEditComplexModal = ref(false);
const editComplexId = ref<number | null>(null);
const clients = ref<any[]>([]);

function openEditComplexModal(complex: ComplexData) {
    editComplexId.value = complex.id;
    editComplexForm.name = complex.name;
    editComplexForm.address_line = complex.address_line;
    editComplexForm.description = complex.description || "";
    editComplexForm.phone_contact = complex.phone_contact || "";
    editComplexForm.instagram_url = (complex as any).instagram_url || "";
    editComplexForm.facebook_url = (complex as any).facebook_url || "";
    editComplexForm.service_ids = complex.services.map((s: any) => s.id);
    showEditComplexModal.value = true;
}

function saveEditComplex() {
    if (!editComplexId.value) return;
    editComplexForm.put(
        `/panel/admin-cancha/complejos/${editComplexId.value}`,
        {
            preserveScroll: true,
            onSuccess: () => {
                showEditComplexModal.value = false;
            },
        },
    );
}

const courtFormsByComplex = reactive<
    Record<
        number,
        {
            surface_type: string;
            players_capacity: string;
            slot_duration_minutes: string;
            base_price: string;
            variant: string;
            price_30_min: string;
            price_60_min: string;
            price_90_min: string;
            price_120_min: string;
        }
    >
>({});

const sportVariants: Record<
    string,
    Array<{ label: string; value: string; capacity: string }>
> = {
    futbol: [
        { label: "Fútbol 5", value: "5", capacity: "10" },
        { label: "Fútbol 7", value: "7", capacity: "14" },
        { label: "Fútbol 11", value: "11", capacity: "22" },
    ],
    basquet: [
        { label: "Básquet 3x3", value: "3", capacity: "6" },
        { label: "Básquet 10 (Full)", value: "10", capacity: "10" },
    ],
    basket: [
        // Fallback for alternative slug
        { label: "Básquet 3x3", value: "3", capacity: "6" },
        { label: "Básquet 10 (Full)", value: "10", capacity: "10" },
    ],
};

const getSportSlug = (sportId: string) => {
    const sport = props.catalogs.sports.find((s) => String(s.id) === sportId);
    return sport?.slug?.toLowerCase() || "";
};

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
        base_price: "10000",
        variant: "",
        price_30_min: "",
        price_60_min: "",
        price_90_min: "",
        price_120_min: "",
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

function toggleEditService(serviceId: number): void {
    const exists = editComplexForm.service_ids.includes(serviceId);
    if (exists) {
        editComplexForm.service_ids = editComplexForm.service_ids.filter(
            (id) => id !== serviceId,
        );
        return;
    }
    editComplexForm.service_ids.push(serviceId);
}

function submitDateFilter(): void {
    router.get(
        "/panel/admin-cancha",
        { date: dateFilter.date },
        {
            preserveState: true,
            replace: true,
        },
    );
}

function onSportChange(complexId: number) {
    const form = courtFormsByComplex[complexId];
    form.variant = "";
    const slug = getSportSlug(form.sport_id);
    if (slug === "padel") {
        form.players_capacity = "4";
    } else if (slug === "tennis" || slug === "tenis") {
        form.players_capacity = "2";
    } else if (slug === "futbol") {
        form.players_capacity = "10"; // Default
    }
}

function onVariantChange(complexId: number) {
    const form = courtFormsByComplex[complexId];
    const slug = getSportSlug(form.sport_id);
    const variants = sportVariants[slug] || [];
    const selected = variants.find((v) => v.value === form.variant);
    if (selected) {
        form.players_capacity = selected.capacity;
        if (
            !form.name ||
            form.name.includes("Cancha Fútbol") ||
            form.name.includes("Básquet")
        ) {
            form.name = `Cancha ${selected.label}`;
        }
    }
}

function createCourt(complexId: number): void {
    const form = courtFormsByComplex[complexId];
    router.post(
        `/panel/admin-cancha/complejos/${complexId}/canchas`,
        {
            ...form,
            sport_id: Number(form.sport_id),
        },
        {
            onSuccess: () => {
                form.name = "";
                form.sport_id = "";
                form.variant = "";
            },
        },
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

function onCourtFieldChange(
    courtId: number,
    field: string,
    event: Event,
): void {
    const target = event.target as HTMLInputElement | HTMLSelectElement | null;
    if (!target) return;
    router.put(
        `/panel/admin-cancha/canchas/${courtId}`,
        { [field]: target.value },
        { preserveScroll: true },
    );
}

function getSelectedCourtPrice(duration: number) {
    if (!modalData.value || !modalData.value.availability) return 0;
    const court = modalData.value.availability.courts.find(
        (c) => String(c.id) === String(fastReservationForm.court_id),
    );
    if (!court) return 0;

    if (duration === 30) return court.price_30_min || court.base_price / 2;
    if (duration === 60) return court.price_60_min || court.base_price;
    if (duration === 90) return court.price_90_min || court.base_price * 1.5;
    if (duration === 120) return court.price_120_min || court.base_price * 2;

    return court.base_price;
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

const showModal = ref(false);
const showReservationDetailsModal = ref(false);
const selectedReservation = ref<any>(null);
const modalDate = ref("");
const modalComplexId = ref<number | null>(null);
const modalData = ref<any>(null);
const loadingModal = ref(false);

function openReservationDetails(res: any) {
    selectedReservation.value = res;
    showReservationDetailsModal.value = true;
}

function editReservation(res: any) {
    if (res.is_recurring) {
        const rr = props.complexes
            .flatMap((c) => c.recurring_reservations)
            .find((r) => r.id === parseInt(res.id.replace("recurring-", "")));
        if (rr) {
            recurringForm.id = rr.id;
            recurringForm.court_id = String(rr.court_id);
            recurringForm.day_of_week = rr.day_of_week;
            recurringForm.start_time = rr.start_time.substring(0, 5);
            recurringForm.end_time = rr.end_time.substring(0, 5);
            recurringForm.client_name = rr.client_name;
            recurringForm.client_phone = rr.client_phone || "";
            recurringForm.client_user_id = rr.client_user_id;
            recurringForm.is_paid = rr.is_paid;
            recurringForm.notes = rr.notes || "";
            showRecurringModal.value = true;
        }
    } else {
        fastReservationForm.id = res.id;
        fastReservationForm.court_id = String(res.court_id);
        fastReservationForm.date = props.selectedDate;
        fastReservationForm.start_time = res.start_time;
        fastReservationForm.end_time = res.end_time;
        fastReservationForm.client_name = res.client_name;
        fastReservationForm.client_phone = res.client_phone || "";
        fastReservationForm.is_paid = res.is_paid;

        // Calculate duration
        const [sh, sm] = res.start_time.split(":").map(Number);
        const [eh, em] = res.end_time.split(":").map(Number);
        const diff = eh * 60 + em - (sh * 60 + sm);
        fastReservationForm.duration = diff > 0 ? diff : 60;

        fastReservationCourtName.value = res.court?.name || "Cancha";
        modalComplexId.value = props.complexes[0].id; // Assumption: first complex or track it better
        showModal.value = true;
        showFastReservationForm.value = true;
    }
}

const fastReservationForm = useForm({
    court_id: "",
    date: "",
    start_time: "",
    end_time: "",
    duration: 90, // Default duration
    client_name: "",
    client_phone: "",
    client_user_id: null as number | null,
    is_paid: false,
    id: null as number | null,
});
const showFastReservationForm = ref(false);
const showFastSuggestions = ref(false);
const fastReservationCourtName = ref("");

function generateCalendarDays(year: number, month: number) {
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const days = [];
    const startingDayOfWeek =
        firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1; // 0=Mon, 6=Sun
    for (let i = 0; i < startingDayOfWeek; i++) {
        days.push(null);
    }
    for (let i = 1; i <= lastDay.getDate(); i++) {
        const d = String(i).padStart(2, "0");
        const m = String(month + 1).padStart(2, "0");
        days.push(`${year}-${m}-${d}`);
    }
    return days;
}

const currentYear = new Date(props.selectedDate + "T00:00:00").getFullYear();
const currentMonth = new Date(props.selectedDate + "T00:00:00").getMonth();
const calendarDays = generateCalendarDays(currentYear, currentMonth);
const monthName = new Date(props.selectedDate + "T00:00:00").toLocaleString(
    "es-ES",
    { month: "long", year: "numeric" },
);

async function openCalendarModal(complexId: number, date: string) {
    modalDate.value = date;
    modalComplexId.value = complexId;
    showModal.value = true;
    loadingModal.value = true;
    modalData.value = null;
    showFastReservationForm.value = false;

    try {
        const response = await axios.get(
            `/panel/admin-cancha/complejos/${complexId}/calendario/${date}`,
        );
        modalData.value = response.data;
    } catch (e) {
        console.error(e);
        alert("Error cargando los detalles del dia.");
    } finally {
        loadingModal.value = false;
    }
}

function startFastReservation(
    complexId: number,
    courtId: number,
    courtName: string,
    startTime: string,
    endTime: string,
) {
    modalComplexId.value = complexId;
    modalDate.value = props.selectedDate; // ensure date is set
    fastReservationCourtName.value = courtName;
    fastReservationForm.court_id = String(courtId);
    fastReservationForm.date = props.selectedDate;
    fastReservationForm.start_time = startTime;
    fastReservationForm.end_time = endTime;
    fastReservationForm.duration = 90; // Default when starting
    updateEndTimeByDuration(); // Ensure end_time matches duration from start
    fastReservationForm.client_name = "";
    fastReservationForm.client_phone = "";
    fastReservationForm.client_user_id = null;
    fastReservationForm.is_paid = false;
    fastReservationForm.id = null;
    showFastSuggestions.value = false;
    showModal.value = true;
    showFastReservationForm.value = true;
}

const filteredClients = computed(() => {
    const query = fastReservationForm.client_name.toLowerCase();
    if (!query || query.length < 2 || !showFastSuggestions.value) return [];
    return clients.value
        .filter((c) => c.name.toLowerCase().includes(query))
        .slice(0, 5);
});

function selectClient(client: any) {
    fastReservationForm.client_name = client.name;
    fastReservationForm.client_phone = client.phone || "";
    fastReservationForm.client_user_id = client.id;
    showFastSuggestions.value = false;
}

function updateEndTimeByDuration() {
    if (!fastReservationForm.start_time) return;
    const [h, m] = fastReservationForm.start_time.split(":").map(Number);
    const date = new Date();
    date.setHours(h, m + Number(fastReservationForm.duration), 0, 0);
    const hh = String(date.getHours()).padStart(2, "0");
    const mm = String(date.getMinutes()).padStart(2, "0");
    fastReservationForm.end_time = `${hh}:${mm}`;
}

function submitFastReservation() {
    const url = fastReservationForm.id
        ? `/panel/admin-cancha/complejos/${modalComplexId.value}/reservas/${fastReservationForm.id}`
        : `/panel/admin-cancha/complejos/${modalComplexId.value}/reservas-rapidas`;

    const method = fastReservationForm.id ? "put" : "post";

    fastReservationForm[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            showFastReservationForm.value = false;
            showReservationDetailsModal.value = false;
            openCalendarModal(modalComplexId.value!, modalDate.value);
        },
    });
}

function cancelReservation(complexId: number, reservationId: number) {
    if (!confirm("¿Seguro de cancelar esta reserva?")) return;
    router.post(
        `/panel/admin-cancha/complejos/${complexId}/reservas/${reservationId}/cancelar`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                if (showModal.value)
                    openCalendarModal(complexId, modalDate.value);
            },
        },
    );
}

const reportFilter = reactive({
    start_date: new Date(new Date().setDate(new Date().getDate() - 7))
        .toISOString()
        .split("T")[0],
    end_date: new Date().toISOString().split("T")[0],
    court_id: "" as string | number,
});

const reportData = ref<any>(null);
const loadingReport = ref(false);

async function loadReport(complexId: number) {
    loadingReport.value = true;
    try {
        const response = await axios.get(
            `/panel/admin-cancha/complejos/${complexId}/reportes`,
            {
                params: reportFilter,
            },
        );
        reportData.value = response.data;
    } catch (error) {
        console.error("Error loading reports:", error);
    } finally {
        loadingReport.value = false;
    }
}

// Chart Data Computeds
const revenueChartData = computed(() => {
    if (!reportData.value?.daily_revenue) return { labels: [], datasets: [] };
    return {
        labels: reportData.value.daily_revenue.map((d: any) =>
            d.date.split("-").slice(2).join("/"),
        ),
        datasets: [
            {
                label: "Ingresos ($)",
                data: reportData.value.daily_revenue.map((d: any) => d.amount),
                borderColor: "#34d399",
                backgroundColor: "rgba(52, 211, 153, 0.1)",
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
            },
        ],
    };
});

const peakHoursChartData = computed(() => {
    if (!reportData.value?.hourly_distribution)
        return { labels: [], datasets: [] };
    return {
        labels: reportData.value.hourly_distribution.map((d: any) => d.hour),
        datasets: [
            {
                label: "Reservas",
                data: reportData.value.hourly_distribution.map(
                    (d: any) => d.count,
                ),
                backgroundColor: "#60a5fa",
                borderRadius: 6,
            },
        ],
    };
});

const clientRetentionData = computed(() => {
    if (!reportData.value?.client_retention)
        return { labels: [], datasets: [] };
    return {
        labels: ["Nuevos", "Recurrentes"],
        datasets: [
            {
                data: [
                    reportData.value.client_retention.new,
                    reportData.value.client_retention.returning,
                ],
                backgroundColor: ["#fbbf24", "#34d399"],
                borderWidth: 0,
            },
        ],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: "rgba(15, 23, 42, 0.9)",
            titleFont: { size: 10, weight: "bold" },
            bodyFont: { size: 12 },
            padding: 10,
            cornerRadius: 8,
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: { color: "rgba(255,255,255,0.05)" },
            ticks: { color: "#94a3b8", font: { size: 10 } },
        },
        x: {
            grid: { display: false },
            ticks: { color: "#94a3b8", font: { size: 10 } },
        },
    },
};

const donutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: "bottom" as const,
            labels: {
                color: "#94a3b8",
                font: { size: 10, weight: "bold" },
                padding: 15,
                usePointStyle: true,
            },
        },
        tooltip: {
            backgroundColor: "rgba(15, 23, 42, 0.9)",
            padding: 10,
            cornerRadius: 8,
        },
    },
    cutout: "75%",
};

const loadClients = async () => {
    try {
        const res = await axios.get("/panel/admin-cancha/clientes");
        clients.value = res.data;
    } catch (e) {
        console.error(e);
    }
};

const selectedCourtId = ref<number | string>("all");
const showMobileMenu = ref(false);

function switchTab(tab: string) {
    activeTab.value = tab;
    showMobileMenu.value = false;
}

onMounted(() => {
    loadClients();
});

const clientForm = useForm({
    id: null as number | null,
    name: "",
    email: "",
    phone: "",
    password: "",
    status: "activo",
});

const recurringForm = useForm({
    court_id: "",
    day_of_week: 1,
    start_time: "",
    end_time: "",
    client_name: "",
    client_phone: "",
    client_user_id: null as number | null,
    is_paid: false,
    notes: "",
    id: null as number | null,
});

const showRecurringModal = ref(false);
const showRecurringSuggestions = ref(false);

function openRecurringModal() {
    recurringForm.reset();
    showRecurringSuggestions.value = false;
    showRecurringModal.value = true;
}

function submitRecurringReservation(complexId: number) {
    const url = recurringForm.id
        ? `/panel/admin-cancha/complejos/${complexId}/turnos-fijos/${recurringForm.id}`
        : `/panel/admin-cancha/complejos/${complexId}/turnos-fijos`;

    const method = recurringForm.id ? "put" : "post";

    recurringForm[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            showRecurringModal.value = false;
            showReservationDetailsModal.value = false;
        },
    });
}

function deleteRecurring(complexId: number, recurringId: number) {
    if (!confirm("¿Seguro de eliminar este turno fijo?")) return;
    router.delete(
        `/panel/admin-cancha/complejos/${complexId}/turnos-fijos/${recurringId}`,
        {
            preserveScroll: true,
        },
    );
}

const filteredClientsRecurring = computed(() => {
    const query = recurringForm.client_name?.toLowerCase() || "";
    if (!query || query.length < 2 || !showRecurringSuggestions.value)
        return [];
    return clients.value
        .filter((c) => c.name.toLowerCase().includes(query))
        .slice(0, 5);
});

function selectClientRecurring(client: any) {
    recurringForm.client_name = client.name;
    recurringForm.client_phone = client.phone || "";
    recurringForm.client_user_id = client.id;
    showRecurringSuggestions.value = false;
}

const showClientFormModal = ref(false);

function openNewClientModal() {
    clientForm.reset();
    clientForm.id = null;
    showClientFormModal.value = true;
}

function editClient(c: any) {
    clientForm.id = c.id;
    clientForm.name = c.name;
    clientForm.email = c.email;
    clientForm.phone = c.phone || "";
    clientForm.status = c.status;
    showClientFormModal.value = true;
}

function saveClient() {
    if (clientForm.id) {
        clientForm.put(`/panel/admin-cancha/clientes/${clientForm.id}`, {
            onSuccess: () => {
                showClientFormModal.value = false;
                loadClients();
            },
        });
    } else {
        clientForm.post(`/panel/admin-cancha/clientes`, {
            onSuccess: () => {
                showClientFormModal.value = false;
                loadClients();
            },
        });
    }
}

function disableClient(c: any) {
    if (!confirm(`¿Suspender cliente ${c.name}?`)) return;
    router.put(
        `/panel/admin-cancha/clientes/${c.id}`,
        {
            name: c.name,
            email: c.email,
            phone: c.phone,
            status: "suspendido",
        },
        {
            onSuccess: () => loadClients(),
        },
    );
}

function enableClient(c: any) {
    if (!confirm(`¿Habilitar cliente ${c.name}?`)) return;
    router.put(
        `/panel/admin-cancha/clientes/${c.id}`,
        {
            name: c.name,
            email: c.email,
            phone: c.phone,
            status: "activo",
        },
        {
            onSuccess: () => loadClients(),
        },
    );
}
</script>

<template>
    <AppShell>
        <div class="flex flex-col lg:flex-row gap-6 p-6 relative">
            <!-- Botón Hamburguesa Flotante (solo móvil) -->
            <button
                @click="showMobileMenu = !showMobileMenu"
                class="lg:hidden fixed bottom-6 right-6 z-50 flex items-center justify-center w-14 h-14 rounded-full btn-primary text-slate-950 shadow-lg border-2 border-white dark:border-slate-800 transition-all active:scale-95 group"
                :class="
                    showMobileMenu
                        ? 'bg-rose-500 shadow-rose-500/20 rotate-90'
                        : 'animate-bounce hover:animate-none'
                "
            >
                <svg
                    v-if="!showMobileMenu"
                    class="w-7 h-7"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2.5"
                        d="M4 6h16M4 12h16m-7 6h7"
                    />
                </svg>
                <svg
                    v-else
                    class="w-7 h-7"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2.5"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>

            <!-- Menú Lateral -->
            <nav
                class="lg:w-64 flex-shrink-0 space-y-2 transition-all duration-500 ease-in-out lg:block"
                :class="[
                    showMobileMenu
                        ? 'block opacity-100 translate-y-0 scale-100 max-h-[600px] mb-8'
                        : 'hidden lg:block opacity-0 lg:opacity-100 -translate-y-4 lg:translate-y-0 scale-95 lg:scale-100 max-h-0 lg:max-h-none',
                ]"
            >
                <button
                    v-for="(label, tab) in {
                        reservas: '📅 Reservas del Día',
                        canchas: '🎾 Modificar Canchas',
                        clientes: '👥 Gestión de Clientes',
                        horarios: '🕒 Horarios y Políticas',
                        turnos_fijos: '🗓️ Turnos Fijos',
                        torneos: '🏆 Torneos y Ranking',
                        comunidad: '📣 Convocatorias',
                        reportes: '📊 Reportes y Estadísticas',
                    }"
                    :key="tab"
                    @click="switchTab(tab)"
                    class="w-full text-left px-4 py-3 rounded-xl font-bold transition-all flex items-center gap-3 active:scale-95 lg:active:scale-100"
                    :class="
                        activeTab === tab
                            ? 'bg-emerald-400 text-slate-950 shadow-lg shadow-emerald-400/20'
                            : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'
                    "
                >
                    {{ label }}
                </button>

                <div
                    class="pt-6 mt-6 border-t border-slate-200 dark:border-slate-800"
                >
                    <button
                        @click="
                            openEditComplexModal(props.complexes[0]);
                            showMobileMenu = false;
                        "
                        class="w-full text-left px-4 py-2 text-xs font-black uppercase tracking-widest text-emerald-500 hover:text-emerald-400 italic underline"
                    >
                        ⚙️ Configurar Complejo
                    </button>
                </div>
            </nav>

            <!-- Contenido Principal -->
            <main class="flex-1 space-y-6">
                <!-- VISTA: RESERVAS (DASHBOARD PRINCIPAL) -->
                <div v-if="activeTab === 'reservas'" class="space-y-6">
                    <section class="card shadow-sm">
                        <div
                            class="flex flex-wrap items-center justify-between gap-4"
                        >
                            <div>
                                <h1
                                    class="text-2xl font-black text-emerald-600 dark:text-emerald-400"
                                >
                                    Panel de Control
                                </h1>
                                <p
                                    class="text-sm text-slate-500 dark:text-slate-400"
                                >
                                    Visualización rápida de ocupación y
                                    reservas.
                                </p>
                            </div>
                            <form
                                class="flex items-center gap-2"
                                @submit.prevent="submitDateFilter"
                            >
                                <input
                                    v-model="dateFilter.date"
                                    type="date"
                                    class="form-field"
                                />
                                <button class="btn-primary">Ir al día</button>
                            </form>
                        </div>
                    </section>
                    <div
                        v-for="complex in props.complexes"
                        :key="complex.id"
                        class="grid gap-6 lg:grid-cols-12"
                    >
                        <!-- Calendario -->
                        <div class="lg:col-span-5 card shadow-sm">
                            <h3
                                class="font-black text-emerald-600 dark:text-emerald-400 mb-4 uppercase tracking-tighter italic"
                            >
                                Calendario Mensual ({{ monthName }})
                            </h3>
                            <div
                                class="grid grid-cols-7 gap-1 text-center text-[10px] font-black text-slate-400 dark:text-slate-500 mb-2"
                            >
                                <div>LUN</div>
                                <div>MAR</div>
                                <div>MIE</div>
                                <div>JUE</div>
                                <div>VIE</div>
                                <div>SAB</div>
                                <div>DOM</div>
                            </div>
                            <div class="grid grid-cols-7 gap-1">
                                <div
                                    v-for="(date, i) in calendarDays"
                                    :key="i"
                                    class="aspect-square flex items-center justify-center rounded-lg text-sm border transition-all relative"
                                    :class="[
                                        !date
                                            ? 'border-transparent'
                                            : 'border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 cursor-pointer hover:border-emerald-500',
                                        date && date === props.selectedDate
                                            ? 'ring-2 ring-emerald-400 bg-emerald-500/10 dark:bg-emerald-400/10'
                                            : '',
                                    ]"
                                    @click="
                                        date
                                            ? ((dateFilter.date = date),
                                              submitDateFilter())
                                            : null
                                    "
                                >
                                    <span
                                        v-if="date"
                                        :class="
                                            date === props.selectedDate
                                                ? 'font-black text-emerald-600 dark:text-emerald-400'
                                                : 'text-slate-500 dark:text-slate-400'
                                        "
                                        >{{
                                            parseInt(date.split("-")[2])
                                        }}</span
                                    >
                                    <div
                                        v-if="
                                            date &&
                                            complex.monthly_reserved_dates.includes(
                                                date,
                                            )
                                        "
                                        class="absolute bottom-1 w-1 h-1 rounded-full bg-emerald-500 dark:bg-emerald-400"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Grilla del Día -->
                        <div class="lg:col-span-7 card card--muted shadow-sm">
                            <div
                                class="flex flex-wrap items-center justify-between mb-4 border-b border-slate-100 dark:border-slate-800/50 pb-2 gap-4"
                            >
                                <div class="flex items-center gap-3">
                                    <h3
                                        class="font-black text-slate-900 dark:text-white italic"
                                    >
                                        Reservas del {{ props.selectedDate }}
                                    </h3>

                                    <select
                                        v-model="selectedCourtId"
                                        class="text-[10px] font-black uppercase text-emerald-600 dark:text-emerald-400 bg-white dark:bg-slate-900 border border-emerald-100 dark:border-emerald-800/40 rounded-lg px-2 py-1 focus:ring-2 focus:ring-emerald-500/20 tracking-tighter outline-none cursor-pointer hover:border-emerald-400 shadow-sm transition-all italic"
                                    >
                                        <option value="all">
                                            Todas las canchas
                                        </option>
                                        <option
                                            v-for="c in complex.courts"
                                            :key="c.id"
                                            :value="c.id"
                                        >
                                            Solo {{ c.name }}
                                        </option>
                                    </select>
                                </div>
                                <button
                                    @click="
                                        openCalendarModal(
                                            complex.id,
                                            props.selectedDate,
                                        )
                                    "
                                    class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline"
                                >
                                    Ver detalle de horarios
                                </button>
                            </div>

                            <div class="space-y-4">
                                <div
                                    v-for="court in complex.courts.filter(
                                        (c) =>
                                            selectedCourtId === 'all' ||
                                            c.id === Number(selectedCourtId),
                                    )"
                                    :key="court.id"
                                    class="p-4 rounded-xl border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900/60 shadow-sm transition-all hover:shadow-md"
                                >
                                    <div
                                        class="flex items-center justify-between mb-3"
                                    >
                                        <span
                                            class="text-xs font-black uppercase text-emerald-600 dark:text-emerald-500 tracking-widest"
                                            >{{ court.name }}</span
                                        >
                                        <span
                                            class="text-[10px] text-slate-400 dark:text-slate-500 font-bold italic"
                                            >{{
                                                formatMoney(
                                                    Number(
                                                        court.price_60_min ||
                                                            court.base_price,
                                                    ),
                                                )
                                            }}/60min</span
                                        >
                                    </div>
                                    <div class="space-y-1">
                                        <!-- Reservas Existentes -->
                                        <div
                                            v-for="res in complex.daily_reservations.filter(
                                                (r) =>
                                                    r.court.name === court.name,
                                            )"
                                            :key="res.id"
                                            @click="openReservationDetails(res)"
                                            class="flex items-center justify-between p-2 rounded-lg text-xs border cursor-pointer transition-colors"
                                            :class="
                                                res.is_recurring
                                                    ? 'bg-sky-50 dark:bg-sky-900/20 border-sky-100 dark:border-sky-800/30 hover:bg-sky-100 dark:hover:bg-sky-800/40'
                                                    : 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-100 dark:border-emerald-800/30 hover:bg-emerald-100 dark:hover:bg-emerald-800/40'
                                            "
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="font-black"
                                                    :class="
                                                        res.is_recurring
                                                            ? 'text-sky-900 dark:text-sky-300'
                                                            : 'text-emerald-900 dark:text-emerald-300'
                                                    "
                                                    >{{
                                                        (
                                                            res.start_at || ""
                                                        ).substring(11, 16)
                                                    }}</span
                                                >
                                                <span
                                                    class="text-[8px] font-bold uppercase tracking-tighter"
                                                    :class="
                                                        res.is_recurring
                                                            ? 'text-sky-500'
                                                            : 'text-slate-400'
                                                    "
                                                    >{{
                                                        res.is_recurring
                                                            ? "Fijo"
                                                            : "Ocupado"
                                                    }}</span
                                                >
                                                <span
                                                    class="font-bold text-slate-700 dark:text-slate-300"
                                                    >|
                                                    {{
                                                        res.client_name ||
                                                        res.client?.name
                                                    }}</span
                                                >
                                            </div>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="px-2 py-0.5 rounded text-[8px] uppercase font-black"
                                                    :class="
                                                        res.is_recurring
                                                            ? 'bg-sky-400/20 text-sky-700 dark:text-sky-400'
                                                            : 'bg-emerald-400/20 text-emerald-700 dark:text-emerald-400'
                                                    "
                                                    >{{
                                                        res.is_recurring
                                                            ? "Confirmado"
                                                            : res.status
                                                    }}</span
                                                >
                                                <button
                                                    v-if="!res.is_recurring"
                                                    @click.stop="
                                                        cancelReservation(
                                                            complex.id,
                                                            res.id,
                                                        )
                                                    "
                                                    class="text-rose-500 hover:scale-125 transition-transform"
                                                    title="Cancelar"
                                                >
                                                    &times;
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Horarios Disponibles (Inline) -->
                                        <div
                                            v-for="slot in complex.availability?.courts?.find(
                                                (c) => c.id === court.id,
                                            )?.available_slots ?? []"
                                            :key="slot.start_time"
                                            class="flex items-center justify-between p-2 rounded-lg bg-white dark:bg-slate-800/20 text-xs border border-slate-100 dark:border-slate-800 border-dashed hover:border-sky-400 hover:bg-sky-50 dark:hover:bg-sky-900/10 transition-all cursor-pointer group"
                                            @click="
                                                startFastReservation(
                                                    complex.id,
                                                    court.id,
                                                    court.name,
                                                    slot.start_time,
                                                    slot.end_time,
                                                )
                                            "
                                        >
                                            <div
                                                class="flex items-center gap-2 text-slate-500 dark:text-slate-400"
                                            >
                                                <span
                                                    class="font-black group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors"
                                                    >{{ slot.start_time }}</span
                                                >
                                                <span
                                                    class="text-[8px] font-black uppercase tracking-widest text-sky-500/60 dark:text-sky-400/40"
                                                    >Disponible</span
                                                >
                                            </div>
                                            <div
                                                class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity"
                                            >
                                                <span
                                                    class="text-[9px] font-black text-sky-600 dark:text-sky-400 uppercase italic"
                                                    >Reservar rápido +</span
                                                >
                                            </div>
                                        </div>

                                        <p
                                            v-if="
                                                complex.daily_reservations.filter(
                                                    (r) =>
                                                        r.court.name ===
                                                        court.name,
                                                ).length === 0 &&
                                                complex.availability?.courts?.find(
                                                    (c) => c.id === court.id,
                                                )?.available_slots?.length === 0
                                            "
                                            class="text-[10px] italic text-slate-400 text-center py-2"
                                        >
                                            No hay disponibilidad ni reservas
                                            para hoy.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VISTA: CLIENTES -->
                <div v-if="activeTab === 'clientes'" class="space-y-6">
                    <section class="card shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h2
                                class="text-xl font-black text-emerald-600 dark:text-emerald-400 font-black uppercase italic tracking-tighter"
                            >
                                Gestión de Clientes
                            </h2>
                            <button
                                @click="openNewClientModal"
                                class="btn-primary"
                            >
                                + ALTA DE CLIENTE
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead
                                    class="text-xs font-black uppercase text-slate-500 border-b border-slate-100 dark:border-slate-800"
                                >
                                    <tr>
                                        <th class="p-4">Nombre</th>
                                        <th class="p-4">Email</th>
                                        <th class="p-4">Teléfono</th>
                                        <th class="p-4 text-center">Estado</th>
                                        <th class="p-4 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-slate-100 dark:divide-slate-800"
                                >
                                    <tr
                                        v-for="client in clients"
                                        :key="client.id"
                                        class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors"
                                    >
                                        <td
                                            class="p-4 font-bold text-slate-900 dark:text-white"
                                        >
                                            {{ client.name }}
                                        </td>
                                        <td
                                            class="p-4 text-slate-600 dark:text-slate-400"
                                        >
                                            {{ client.email }}
                                        </td>
                                        <td
                                            class="p-4 text-slate-500 dark:text-slate-400"
                                        >
                                            {{ client.phone || "-" }}
                                        </td>
                                        <td class="p-4 text-center">
                                            <span
                                                :class="
                                                    client.status === 'activo'
                                                        ? 'text-emerald-400 bg-emerald-400/10'
                                                        : 'text-rose-400 bg-rose-400/10'
                                                "
                                                class="px-2 py-1 rounded-full text-[10px] font-black uppercase"
                                                >{{ client.status }}</span
                                            >
                                        </td>
                                        <td class="p-4 text-right space-x-3">
                                            <button
                                                @click="editClient(client)"
                                                class="text-sky-400 hover:underline font-bold"
                                            >
                                                EDITAR
                                            </button>
                                            <button
                                                v-if="
                                                    client.status === 'activo'
                                                "
                                                @click="disableClient(client)"
                                                class="text-rose-500 hover:underline font-bold italic"
                                            >
                                                SUSPENDER
                                            </button>
                                            <button
                                                v-else
                                                @click="enableClient(client)"
                                                class="text-emerald-400 hover:underline font-bold italic"
                                            >
                                                HABILITAR
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <!-- VISTA: TURNOS FIJOS -->
                <div v-if="activeTab === 'turnos_fijos'" class="space-y-6">
                    <div
                        v-for="complex in props.complexes"
                        :key="complex.id"
                        class="space-y-6"
                    >
                        <section class="card shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h2
                                        class="text-xl font-black text-emerald-600 dark:text-emerald-400 font-black uppercase italic tracking-tighter"
                                    >
                                        Turnos Fijos: {{ complex.name }}
                                    </h2>
                                    <p
                                        class="text-xs text-slate-500 font-bold uppercase italic"
                                    >
                                        Gestión de reservas permanentes
                                        semanales
                                    </p>
                                </div>
                                <button
                                    @click="openRecurringModal"
                                    class="rounded-lg bg-emerald-400 px-4 py-2 text-sm font-black text-slate-950 shadow-lg shadow-emerald-400/20 underline italic hover:bg-emerald-300"
                                >
                                    + NUEVO TURNO FIJO
                                </button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead
                                        class="text-xs font-black uppercase text-slate-500 border-b border-slate-100 dark:border-slate-800"
                                    >
                                        <tr>
                                            <th class="p-4 italic">Día</th>
                                            <th class="p-4 italic">Cancha</th>
                                            <th class="p-4 italic">Horario</th>
                                            <th class="p-4 italic">Cliente</th>
                                            <th class="p-4 italic text-right">
                                                Acción
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="divide-y divide-slate-100 dark:divide-slate-800"
                                    >
                                        <tr
                                            v-for="rr in complex.recurring_reservations"
                                            :key="rr.id"
                                            class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors"
                                        >
                                            <td
                                                class="p-4 font-black text-emerald-600 uppercase"
                                            >
                                                {{ dayLabels[rr.day_of_week] }}
                                            </td>
                                            <td
                                                class="p-4 font-bold text-slate-700 dark:text-slate-300"
                                            >
                                                {{ rr.court?.name }}
                                            </td>
                                            <td class="p-4 font-black">
                                                <span
                                                    class="text-slate-900 dark:text-white"
                                                    >{{
                                                        rr.start_time.substring(
                                                            0,
                                                            5,
                                                        )
                                                    }}</span
                                                >
                                                <span
                                                    class="text-slate-400 px-1"
                                                    >-</span
                                                >
                                                <span
                                                    class="text-slate-900 dark:text-white"
                                                    >{{
                                                        rr.end_time.substring(
                                                            0,
                                                            5,
                                                        )
                                                    }}</span
                                                >
                                            </td>
                                            <td class="p-4">
                                                <div class="flex flex-col">
                                                    <span
                                                        class="font-bold text-slate-900 dark:text-white"
                                                        >{{
                                                            rr.client_name
                                                        }}</span
                                                    >
                                                    <span
                                                        v-if="rr.client_phone"
                                                        class="text-[10px] text-slate-500 font-bold"
                                                        >{{
                                                            rr.client_phone
                                                        }}</span
                                                    >
                                                </div>
                                            </td>
                                            <td class="p-4 text-right">
                                                <button
                                                    @click="
                                                        deleteRecurring(
                                                            complex.id,
                                                            rr.id,
                                                        )
                                                    "
                                                    class="text-rose-500 font-black hover:underline underline-offset-4 decoration-rose-500/30"
                                                >
                                                    ELIMINAR
                                                </button>
                                            </td>
                                        </tr>
                                        <tr
                                            v-if="
                                                !complex.recurring_reservations
                                                    .length
                                            "
                                        >
                                            <td
                                                colspan="5"
                                                class="p-8 text-center text-slate-400 italic font-bold"
                                            >
                                                No hay turnos fijos registrados
                                                para este complejo.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                </div>

                <!-- VISTA: CANCHAS -->
                <div v-if="activeTab === 'canchas'" class="space-y-6">
                    <section
                        v-for="complex in props.complexes"
                        :key="complex.id"
                        class="card shadow-sm"
                    >
                        <div class="flex items-center justify-between mb-6">
                            <h2
                                class="text-xl font-black text-emerald-600 dark:text-emerald-400 font-black uppercase italic tracking-tighter"
                            >
                                Gestión de Canchas: {{ complex.name }}
                            </h2>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div
                                v-for="court in complex.courts"
                                :key="court.id"
                                class="p-4 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 transition-all hover:border-emerald-500/50 shadow-sm hover:shadow-md"
                            >
                                <div
                                    class="flex justify-between items-start mb-4"
                                >
                                    <h4
                                        class="font-bold text-slate-900 dark:text-white"
                                    >
                                        {{ court.name }}
                                    </h4>
                                    <span
                                        class="text-[9px] font-black uppercase px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold italic"
                                        >{{ court.sport.name }}</span
                                    >
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <label
                                            class="block text-[9px] font-black uppercase text-slate-400 dark:text-slate-500 mb-2 tracking-tighter italic"
                                            >Precios según duración</label
                                        >
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-1">
                                                <label
                                                    class="block text-[8px] font-bold text-slate-400"
                                                    >30 MIN ($)</label
                                                >
                                                <input
                                                    type="number"
                                                    :value="court.price_30_min"
                                                    @change="
                                                        onCourtFieldChange(
                                                            court.id,
                                                            'price_30_min',
                                                            $event,
                                                        )
                                                    "
                                                    class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs p-2 text-emerald-600 font-bold focus:ring-1 focus:ring-emerald-500 shadow-inner"
                                                />
                                            </div>
                                            <div class="space-y-1">
                                                <label
                                                    class="block text-[8px] font-bold text-slate-400"
                                                    >60 MIN ($)</label
                                                >
                                                <input
                                                    type="number"
                                                    :value="court.price_60_min"
                                                    @change="
                                                        onCourtFieldChange(
                                                            court.id,
                                                            'price_60_min',
                                                            $event,
                                                        )
                                                    "
                                                    class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs p-2 text-emerald-600 font-bold focus:ring-1 focus:ring-emerald-500 shadow-inner"
                                                />
                                            </div>
                                            <div class="space-y-1">
                                                <label
                                                    class="block text-[8px] font-bold text-slate-400"
                                                    >90 MIN ($)</label
                                                >
                                                <input
                                                    type="number"
                                                    :value="court.price_90_min"
                                                    @change="
                                                        onCourtFieldChange(
                                                            court.id,
                                                            'price_90_min',
                                                            $event,
                                                        )
                                                    "
                                                    class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs p-2 text-emerald-600 font-bold focus:ring-1 focus:ring-emerald-500 shadow-inner"
                                                />
                                            </div>
                                            <div class="space-y-1">
                                                <label
                                                    class="block text-[8px] font-bold text-slate-400"
                                                    >120 MIN ($)</label
                                                >
                                                <input
                                                    type="number"
                                                    :value="court.price_120_min"
                                                    @change="
                                                        onCourtFieldChange(
                                                            court.id,
                                                            'price_120_min',
                                                            $event,
                                                        )
                                                    "
                                                    class="w-full rounded-lg border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-xs p-2 text-emerald-600 font-bold focus:ring-1 focus:ring-emerald-500 shadow-inner"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[9px] font-black uppercase text-slate-400 dark:text-slate-500 mb-1 tracking-tighter"
                                            >Estado de Disponibilidad</label
                                        >
                                        <select
                                            :value="court.status"
                                            @change="
                                                onCourtFieldChange(
                                                    court.id,
                                                    'status',
                                                    $event,
                                                )
                                            "
                                            class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs p-2 font-bold focus:ring-1 focus:ring-emerald-500 shadow-inner"
                                            :class="
                                                court.status === 'activa'
                                                    ? 'text-emerald-600 dark:text-emerald-400'
                                                    : 'text-rose-500 dark:text-rose-400'
                                            "
                                        >
                                            <option value="activa">
                                                ACTIVA / DISPONIBLE
                                            </option>
                                            <option value="inactiva">
                                                CERRADA / INACTIVA
                                            </option>
                                            <option value="mantenimiento">
                                                MANTENIMIENTO
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Card para Agregar Nueva Cancha -->
                            <div
                                class="p-4 rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-800 flex flex-col justify-center items-center text-center space-y-3 bg-slate-50 dark:bg-slate-900/20 hover:bg-slate-100 dark:hover:bg-slate-900/40 transition-colors group"
                            >
                                <h4
                                    class="font-black text-slate-400 dark:text-slate-500 uppercase text-[10px] tracking-widest italic group-hover:text-emerald-500 transition-colors"
                                >
                                    Nueva Pista
                                </h4>
                                <input
                                    v-model="
                                        courtFormsByComplex[complex.id].name
                                    "
                                    placeholder="Ej: Pista de Vidrio"
                                    class="form-field w-full text-xs"
                                />
                                <select
                                    v-model="
                                        courtFormsByComplex[complex.id].sport_id
                                    "
                                    @change="onSportChange(complex.id)"
                                    class="form-field w-full text-xs"
                                >
                                    <option value="">
                                        Seleccionar Deporte
                                    </option>
                                    <option
                                        v-for="sport in props.catalogs.sports"
                                        :key="sport.id"
                                        :value="String(sport.id)"
                                    >
                                        {{ sport.name }}
                                    </option>
                                </select>

                                <!-- Variant Selector (Conditional) -->
                                <select
                                    v-if="
                                        sportVariants[
                                            getSportSlug(
                                                courtFormsByComplex[complex.id]
                                                    .sport_id,
                                            )
                                        ]
                                    "
                                    v-model="
                                        courtFormsByComplex[complex.id].variant
                                    "
                                    @change="onVariantChange(complex.id)"
                                    class="w-full rounded-lg border-emerald-400/50 bg-emerald-400/5 text-emerald-600 dark:text-emerald-400 text-[10px] p-2 font-black uppercase italic animate-in slide-in-from-top-2 duration-300"
                                >
                                    <option value="">
                                        Seleccionar Variante
                                    </option>
                                    <option
                                        v-for="v in sportVariants[
                                            getSportSlug(
                                                courtFormsByComplex[complex.id]
                                                    .sport_id,
                                            )
                                        ]"
                                        :key="v.value"
                                        :value="v.value"
                                    >
                                        {{ v.label }}
                                    </option>
                                </select>

                                <button
                                    @click="createCourt(complex.id)"
                                    class="btn-primary w-full text-[10px]"
                                >
                                    DAR DE ALTA CANCHA
                                </button>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- VISTA: HORARIOS Y POLÍTICAS -->
                <div v-if="activeTab === 'horarios'" class="space-y-6">
                    <section
                        v-for="complex in props.complexes"
                        :key="complex.id"
                        class="grid gap-6 lg:grid-cols-2"
                    >
                        <div class="card shadow-sm">
                            <h2
                                class="text-xl font-black text-emerald-600 dark:text-emerald-400 mb-6 uppercase tracking-tighter italic"
                            >
                                🕒 Horarios de Atención
                            </h2>
                            <div class="space-y-2">
                                <div
                                    v-for="day in openingHoursByComplex[
                                        complex.id
                                    ]"
                                    :key="day.day_of_week"
                                    class="flex items-center gap-4 bg-slate-950/40 p-2 rounded-xl border border-slate-800/50"
                                >
                                    <div
                                        class="w-20 font-black text-[10px] text-slate-400 uppercase italic"
                                    >
                                        {{ dayLabels[day.day_of_week] }}
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            v-model="day.is_open"
                                            class="rounded border-slate-700 bg-slate-800 text-emerald-400 w-4 h-4"
                                        />
                                        <span
                                            class="text-[9px] font-black text-slate-600 uppercase"
                                            >{{
                                                day.is_open
                                                    ? "Abierto"
                                                    : "Cerrado"
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex items-center gap-2 flex-1"
                                        v-if="day.is_open"
                                    >
                                        <input
                                            v-model="day.open_time"
                                            type="time"
                                            class="form-field flex-1 text-[10px] p-1.5"
                                        />
                                        <span
                                            class="text-slate-600 text-[10px] font-bold"
                                            >a</span
                                        >
                                        <input
                                            v-model="day.close_time"
                                            type="time"
                                            class="form-field flex-1 text-[10px] p-1.5"
                                        />
                                    </div>
                                    <div
                                        v-else
                                        class="flex-1 text-rose-500/50 font-black italic text-[10px] uppercase text-right pr-2"
                                    >
                                        No disponible
                                    </div>
                                </div>
                            </div>
                            <button
                                @click="saveOpeningHours(complex.id)"
                                class="btn-primary w-full mt-4"
                            >
                                Aplicar Horarios Semanales
                            </button>
                        </div>

                        <div class="card shadow-sm h-fit">
                            <h2
                                class="text-xl font-black text-emerald-600 dark:text-emerald-400 mb-6 uppercase tracking-tighter italic"
                            >
                                📜 Reglas de Reserva
                            </h2>
                            <div class="space-y-5">
                                <div
                                    class="p-4 rounded-xl bg-slate-950/40 border border-slate-800"
                                >
                                    <label
                                        class="block text-[9px] font-black uppercase text-slate-500 mb-2 tracking-widest italic"
                                        >Porcentaje de Seña Requerida</label
                                    >
                                    <div class="relative">
                                        <input
                                            v-model.number="
                                                policyFormsByComplex[complex.id]
                                                    .deposit_percent
                                            "
                                            type="number"
                                            class="form-field w-full"
                                        />
                                        <span
                                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 font-black"
                                            >%</span
                                        >
                                    </div>
                                    <p
                                        class="text-[9px] text-slate-600 mt-1 italic italic"
                                    >
                                        Monto que el cliente debe pagar por
                                        adelantado para confirmar.
                                    </p>
                                </div>
                                <div
                                    class="p-4 rounded-xl bg-slate-950/40 border border-slate-800"
                                >
                                    <label
                                        class="block text-[9px] font-black uppercase text-slate-500 mb-2 tracking-widest italic"
                                        >Antelación para Cancelar
                                        (minutos)</label
                                    >
                                    <input
                                        v-model.number="
                                            policyFormsByComplex[complex.id]
                                                .cancel_limit_minutes
                                        "
                                        type="number"
                                        class="form-field w-full"
                                    />
                                    <p
                                        class="text-[9px] text-slate-600 mt-1 italic italic"
                                    >
                                        Ej: 1440 min = 24 horas antes.
                                    </p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div
                                        class="p-3 rounded-xl bg-slate-950/20 border border-slate-800/50"
                                    >
                                        <label
                                            class="block text-[8px] font-black uppercase text-slate-500 mb-1"
                                            >Reembolso (%)</label
                                        >
                                        <input
                                            v-model.number="
                                                policyFormsByComplex[complex.id]
                                                    .refund_percent_before_limit
                                            "
                                            type="number"
                                            class="form-field w-full"
                                        />
                                    </div>
                                    <div
                                        class="p-3 rounded-xl bg-slate-950/20 border border-slate-800/50"
                                    >
                                        <label
                                            class="block text-[8px] font-black uppercase text-slate-500 mb-1"
                                            >Multa No-Show (%)</label
                                        >
                                        <input
                                            v-model.number="
                                                policyFormsByComplex[complex.id]
                                                    .no_show_penalty_percent
                                            "
                                            type="number"
                                            class="form-field w-full"
                                        />
                                    </div>
                                </div>
                            </div>
                            <button
                                @click="savePolicy(complex.id)"
                                class="btn-primary w-full mt-6"
                            >
                                ACTUALIZAR POLÍTICAS
                            </button>
                        </div>
                    </section>
                </div>

                <!-- VISTA: TORNEOS -->
                <div v-if="activeTab === 'torneos'" class="space-y-6">
                    <section
                        v-for="complex in props.complexes"
                        :key="complex.id"
                        class="card shadow-sm"
                    >
                        <div class="flex items-center justify-between mb-8">
                            <h2
                                class="text-xl font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-tighter italic"
                            >
                                🏆 Gestión de Torneos Profesionales
                            </h2>
                        </div>

                        <div class="grid gap-6 lg:grid-cols-12">
                            <!-- Crear Torneo -->
                            <div
                                class="lg:col-span-5 p-6 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 shadow-sm"
                            >
                                <h3
                                    class="text-[10px] font-black text-slate-600 dark:text-white italic mb-6 tracking-[0.2em] uppercase"
                                >
                                    ORGANIZAR NUEVA COMPETENCIA
                                </h3>
                                <div class="grid gap-4">
                                    <div class="space-y-1">
                                        <label
                                            class="text-[9px] font-black text-slate-500 uppercase italic"
                                            >Nombre del Evento</label
                                        >
                                        <input
                                            v-model="
                                                tournamentFormsByComplex[
                                                    complex.id
                                                ].name
                                            "
                                            placeholder="Ej: Open Verano 2024"
                                            class="w-full rounded-lg border-slate-700 bg-slate-900 p-2.5 text-sm text-white focus:ring-1 focus:ring-emerald-500"
                                        />
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1">
                                            <label
                                                class="text-[9px] font-black text-slate-500 uppercase italic"
                                                >Disciplina</label
                                            >
                                            <select
                                                v-model="
                                                    tournamentFormsByComplex[
                                                        complex.id
                                                    ].sport_id
                                                "
                                                class="w-full rounded-lg border-slate-700 bg-slate-900 p-2 text-xs text-white"
                                            >
                                                <option value="">
                                                    Deporte
                                                </option>
                                                <option
                                                    v-for="sport in props
                                                        .catalogs.sports"
                                                    :key="sport.id"
                                                    :value="String(sport.id)"
                                                >
                                                    {{ sport.name }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="space-y-1">
                                            <label
                                                class="text-[9px] font-black text-slate-500 uppercase italic"
                                                >Categoría</label
                                            >
                                            <input
                                                v-model="
                                                    tournamentFormsByComplex[
                                                        complex.id
                                                    ].category
                                                "
                                                placeholder="Ej: 4ta Masculino"
                                                class="w-full rounded-lg border-slate-700 bg-slate-900 p-2 text-xs text-white"
                                            />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1">
                                            <label
                                                class="text-[9px] font-black text-slate-500 uppercase italic"
                                                >Fecha de Inicio</label
                                            >
                                            <input
                                                v-model="
                                                    tournamentFormsByComplex[
                                                        complex.id
                                                    ].start_date
                                                "
                                                type="date"
                                                class="w-full rounded-lg border-slate-700 bg-slate-900 p-2 text-xs text-white"
                                            />
                                        </div>
                                        <div class="space-y-1">
                                            <label
                                                class="text-[9px] font-black text-slate-500 uppercase italic"
                                                >Fecha de Cierre</label
                                            >
                                            <input
                                                v-model="
                                                    tournamentFormsByComplex[
                                                        complex.id
                                                    ].end_date
                                                "
                                                type="date"
                                                class="w-full rounded-lg border-slate-700 bg-slate-900 p-2 text-xs text-white"
                                            />
                                        </div>
                                    </div>
                                    <button
                                        @click="createTournament(complex.id)"
                                        class="bg-emerald-400 text-slate-950 py-3 rounded-xl font-black uppercase text-[10px] shadow-lg shadow-emerald-400/10 hover:bg-emerald-300 tracking-widest mt-2"
                                    >
                                        LANZAR TORNEO AL PÚBLICO
                                    </button>
                                </div>
                            </div>

                            <!-- Listado de Torneos -->
                            <div class="lg:col-span-7 space-y-4">
                                <h3
                                    class="text-[10px] font-black text-slate-500 italic mb-2 tracking-[0.2em] uppercase"
                                >
                                    EVENTOS ACTIVOS / PRÓXIMOS
                                </h3>
                                <div class="grid gap-4">
                                    <div
                                        v-for="tournament in complex.tournaments"
                                        :key="tournament.id"
                                        class="p-4 rounded-xl border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900/80 transition-all hover:bg-slate-50 dark:hover:bg-slate-900 shadow-sm hover:shadow-md overflow-hidden group"
                                    >
                                        <div
                                            class="flex justify-between items-start mb-4"
                                        >
                                            <div class="flex-1">
                                                <h4
                                                    class="font-black text-slate-900 dark:text-white uppercase italic text-sm group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors"
                                                >
                                                    {{ tournament.name }}
                                                </h4>
                                                <div
                                                    class="flex items-center gap-3 mt-1"
                                                >
                                                    <span
                                                        class="text-[9px] font-black text-emerald-600 dark:text-emerald-500 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 rounded italic"
                                                        >{{
                                                            tournament.sport
                                                                ? tournament
                                                                      .sport
                                                                      .name
                                                                : "Multideporte"
                                                        }}</span
                                                    >
                                                    <span
                                                        class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase"
                                                        >CAT:
                                                        {{
                                                            tournament.category
                                                        }}</span
                                                    >
                                                </div>
                                            </div>
                                            <span
                                                class="text-[8px] font-black uppercase px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700"
                                                >{{
                                                    tournamentStatusLabel(
                                                        tournament.status,
                                                    )
                                                }}</span
                                            >
                                        </div>

                                        <div
                                            class="grid grid-cols-2 gap-4 pb-4 border-b border-slate-100 dark:border-slate-800/50 mb-4"
                                        >
                                            <div class="text-[10px]">
                                                <span
                                                    class="text-slate-400 dark:text-slate-500 block uppercase font-black text-[8px] italic"
                                                    >Inscripción</span
                                                ><span
                                                    class="text-slate-900 dark:text-white font-bold"
                                                    >{{
                                                        formatMoney(
                                                            tournament.entry_fee,
                                                        )
                                                    }}</span
                                                >
                                            </div>
                                            <div class="text-[10px]">
                                                <span
                                                    class="text-slate-400 dark:text-slate-500 block uppercase font-black text-[8px] italic"
                                                    >Equipos</span
                                                ><span
                                                    class="text-slate-900 dark:text-white font-bold"
                                                    >{{
                                                        tournament.teams.length
                                                    }}
                                                    /
                                                    {{
                                                        tournament.max_teams
                                                    }}</span
                                                >
                                            </div>
                                        </div>

                                        <div
                                            class="flex justify-between items-center"
                                        >
                                            <p
                                                class="text-[9px] font-bold text-slate-400 dark:text-slate-600 italic"
                                            >
                                                Del
                                                {{ tournament.start_date }} al
                                                {{ tournament.end_date }}
                                            </p>
                                            <button
                                                class="text-emerald-600 dark:text-emerald-400 font-black text-[9px] uppercase hover:underline tracking-tighter"
                                            >
                                                Gestionar Llaves y Ranking
                                                &rarr;
                                            </button>
                                        </div>
                                    </div>
                                    <div
                                        v-if="complex.tournaments.length === 0"
                                        class="p-8 rounded-2xl border-2 border-dashed border-slate-800 text-center"
                                    >
                                        <p
                                            class="text-xs font-bold text-slate-600 italic uppercase italic"
                                        >
                                            Sin torneos programados.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- VISTA: CONVOCATORIAS -->
                <div v-if="activeTab === 'comunidad'" class="space-y-6">
                    <section
                        v-for="complex in props.complexes"
                        :key="complex.id"
                        class="card shadow-sm"
                    >
                        <h2
                            class="text-xl font-black text-emerald-600 dark:text-emerald-400 uppercase italic mb-8 tracking-tighter"
                        >
                            📣 Cartelera de la Comunidad (Team Board)
                        </h2>
                        <div class="grid gap-8 lg:grid-cols-12">
                            <!-- Crear Convocatoria -->
                            <div
                                class="lg:col-span-12 xl:col-span-5 p-6 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 shadow-sm"
                            >
                                <h3
                                    class="text-[10px] font-black text-slate-600 dark:text-white italic mb-6 uppercase tracking-widest text-sky-600 dark:text-sky-400"
                                >
                                    BUSCAR JUGADORES / RIVALES
                                </h3>
                                <div class="grid gap-4">
                                    <div class="space-y-1">
                                        <label
                                            class="text-[9px] font-black text-slate-500 uppercase"
                                            >Tipo de Aviso</label
                                        >
                                        <select
                                            v-model="
                                                teamBoardFormsByComplex[
                                                    complex.id
                                                ].kind
                                            "
                                            class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5 text-xs text-slate-900 dark:text-white"
                                        >
                                            <option value="falta_jugador">
                                                Falta Jugador (Buscamos uno/a)
                                            </option>
                                            <option value="busco_rival">
                                                Busco Rival (Tenemos equipo)
                                            </option>
                                            <option value="aviso_general">
                                                Aviso General / Torneo
                                            </option>
                                        </select>
                                    </div>
                                    <div class="space-y-1">
                                        <label
                                            class="text-[9px] font-black text-slate-500 uppercase"
                                            >Título del Aviso</label
                                        >
                                        <input
                                            v-model="
                                                teamBoardFormsByComplex[
                                                    complex.id
                                                ].title
                                            "
                                            placeholder="Ej: Se busca delantero para hoy 21hs"
                                            class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5 text-xs text-slate-900 dark:text-white"
                                        />
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1">
                                            <label
                                                class="text-[9px] font-black text-slate-500 uppercase"
                                                >Deporte</label
                                            >
                                            <select
                                                v-model="
                                                    teamBoardFormsByComplex[
                                                        complex.id
                                                    ].sport_id
                                                "
                                                class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2 text-xs text-slate-900 dark:text-white"
                                            >
                                                <option value="">
                                                    Deporte
                                                </option>
                                                <option
                                                    v-for="sport in props
                                                        .catalogs.sports"
                                                    :key="sport.id"
                                                    :value="String(sport.id)"
                                                >
                                                    {{ sport.name }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="space-y-1">
                                            <label
                                                class="text-[9px] font-black text-slate-500 uppercase"
                                                >Día del Partido</label
                                            >
                                            <input
                                                v-model="
                                                    teamBoardFormsByComplex[
                                                        complex.id
                                                    ].play_day
                                                "
                                                type="date"
                                                class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2 text-xs text-slate-900 dark:text-white"
                                            />
                                        </div>
                                    </div>
                                    <button
                                        @click="createTeamBoardPost(complex.id)"
                                        class="bg-sky-400 text-slate-950 py-3 rounded-xl font-black uppercase text-[10px] shadow-lg shadow-sky-400/20 hover:bg-sky-300 tracking-widest mt-2 italic"
                                    >
                                        PUBLICAR ANUNCIO COMUNITARIO
                                    </button>
                                </div>
                            </div>

                            <!-- Listado -->
                            <div class="lg:col-span-12 xl:col-span-7 space-y-4">
                                <h3
                                    class="text-[10px] font-black text-slate-500 italic mb-2 tracking-widest uppercase text-sky-600 dark:text-sky-400"
                                >
                                    AVISOS VIGENTES
                                </h3>
                                <div class="grid gap-3">
                                    <div
                                        v-for="post in complex.team_board_posts"
                                        :key="post.id"
                                        class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 hover:bg-slate-50 dark:hover:bg-slate-900 transition-all shadow-sm hover:shadow-md cursor-pointer group"
                                    >
                                        <div
                                            class="flex justify-between items-center mb-3"
                                        >
                                            <span
                                                class="text-[8px] font-black uppercase px-2 py-1 rounded italic"
                                                :class="
                                                    post.kind ===
                                                    'falta_jugador'
                                                        ? 'bg-amber-100 dark:bg-amber-400 text-amber-600 dark:text-slate-950'
                                                        : post.kind ===
                                                            'busco_rival'
                                                          ? 'bg-rose-100 dark:bg-rose-500 text-rose-600 dark:text-white'
                                                          : 'bg-sky-100 dark:bg-sky-400 text-sky-600 dark:text-slate-950'
                                                "
                                                >{{
                                                    teamBoardKindLabel(
                                                        post.kind,
                                                    )
                                                }}</span
                                            >
                                            <span
                                                class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase italic"
                                                >{{ post.play_day }}</span
                                            >
                                        </div>
                                        <h4
                                            class="font-black text-slate-900 dark:text-white text-sm mb-1 uppercase tracking-tight group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors"
                                        >
                                            {{ post.title }}
                                        </h4>
                                        <div
                                            class="flex items-center gap-4 mt-2"
                                        >
                                            <p
                                                class="text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase italic"
                                            >
                                                {{
                                                    post.sport
                                                        ? post.sport.name
                                                        : ""
                                                }}
                                            </p>
                                            <span
                                                class="text-slate-300 dark:text-slate-700 font-black"
                                                >·</span
                                            >
                                            <p
                                                class="text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase italic"
                                            >
                                                Nivel:
                                                <span
                                                    class="text-slate-900 dark:text-white"
                                                    >{{
                                                        post.level || "Promedio"
                                                    }}</span
                                                >
                                            </p>
                                        </div>
                                    </div>
                                    <div
                                        v-if="
                                            complex.team_board_posts.length ===
                                            0
                                        "
                                        class="p-8 rounded-2xl border-2 border-dashed border-slate-100 dark:border-slate-800 text-center"
                                    >
                                        <p
                                            class="text-xs font-bold text-slate-400 dark:text-slate-600 italic uppercase"
                                        >
                                            No hay convocatorias abiertas en
                                            este momento.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div v-if="activeTab === 'reportes'" class="space-y-6">
                    <div
                        v-for="complex in props.complexes"
                        :key="complex.id"
                        class="card shadow-sm lg:p-10"
                    >
                        <div
                            class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-10"
                        >
                            <div>
                                <h2
                                    class="text-2xl font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-tighter italic"
                                >
                                    📊 Centro de Inteligencia
                                </h2>
                                <p
                                    class="text-sm text-slate-500 font-bold dark:text-slate-400"
                                >
                                    Analítica avanzada de rendimiento y
                                    ocupación para {{ complex.name }}
                                </p>
                            </div>

                            <div
                                class="flex flex-wrap items-end gap-3 bg-slate-50 dark:bg-slate-900/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-800"
                            >
                                <div class="space-y-1">
                                    <label
                                        class="text-[9px] font-black text-slate-400 uppercase italic"
                                        >Desde</label
                                    >
                                    <input
                                        v-model="reportFilter.start_date"
                                        type="date"
                                        class="block w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-xs px-3 py-2 text-slate-900 dark:text-white focus:ring-emerald-500"
                                    />
                                </div>
                                <div class="space-y-1">
                                    <label
                                        class="text-[9px] font-black text-slate-400 uppercase italic"
                                        >Hasta</label
                                    >
                                    <input
                                        v-model="reportFilter.end_date"
                                        type="date"
                                        class="block w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-xs px-3 py-2 text-slate-900 dark:text-white focus:ring-emerald-500"
                                    />
                                </div>
                                <button
                                    @click="loadReport(complex.id)"
                                    :disabled="loadingReport"
                                    class="h-9 px-6 rounded-xl bg-emerald-400 text-slate-950 text-xs font-black uppercase tracking-widest hover:bg-emerald-300 transition-all disabled:opacity-50 inline-flex items-center gap-2"
                                >
                                    <Activity class="w-3 h-3" />
                                    {{
                                        loadingReport
                                            ? "Analizando..."
                                            : "Actualizar"
                                    }}
                                </button>
                            </div>
                        </div>

                        <div v-if="!reportData" class="py-20 text-center">
                            <div
                                class="inline-flex p-6 rounded-full bg-emerald-50 dark:bg-emerald-500/5 text-emerald-500 mb-4"
                            >
                                <TrendingUp class="w-12 h-12" />
                            </div>
                            <h3
                                class="text-lg font-black text-slate-900 dark:text-white uppercase italic"
                            >
                                Datos listos para procesar
                            </h3>
                            <p
                                class="text-sm text-slate-500 font-bold max-w-xs mx-auto"
                            >
                                Haz clic en "Actualizar" para generar las
                                métricas del periodo seleccionado.
                            </p>
                        </div>

                        <div
                            v-else
                            class="space-y-10 animate-in fade-in duration-700"
                        >
                            <!-- KPI Cards -->
                            <div
                                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6"
                            >
                                <div
                                    class="group relative p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-xl hover:shadow-emerald-500/5 transition-all"
                                >
                                    <div
                                        class="flex items-center justify-between mb-4"
                                    >
                                        <div
                                            class="p-3 bg-emerald-500/10 rounded-2xl text-emerald-500"
                                        >
                                            <DollarSign class="w-6 h-6" />
                                        </div>
                                        <ArrowUpRight
                                            class="w-4 h-4 text-emerald-500"
                                        />
                                    </div>
                                    <p
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1"
                                    >
                                        Ingresos Totales
                                    </p>
                                    <h4
                                        class="text-3xl font-black text-slate-900 dark:text-white tabular-nums"
                                    >
                                        {{
                                            formatMoney(
                                                reportData.total_revenue,
                                            )
                                        }}
                                    </h4>
                                </div>

                                <div
                                    class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-xl hover:shadow-sky-500/5 transition-all"
                                >
                                    <div
                                        class="flex items-center justify-between mb-4"
                                    >
                                        <div
                                            class="p-3 bg-sky-500/10 rounded-2xl text-sky-500"
                                        >
                                            <Calendar class="w-6 h-6" />
                                        </div>
                                        <span
                                            class="text-[10px] font-black text-sky-500 bg-sky-500/5 px-2 py-1 rounded-full uppercase"
                                            >Confirmadas</span
                                        >
                                    </div>
                                    <p
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1"
                                    >
                                        Total Reservas
                                    </p>
                                    <h4
                                        class="text-3xl font-black text-slate-900 dark:text-white tabular-nums"
                                    >
                                        {{ reportData.total_reservations }}
                                    </h4>
                                </div>

                                <div
                                    class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-xl hover:shadow-purple-500/5 transition-all"
                                >
                                    <div
                                        class="flex items-center justify-between mb-4"
                                    >
                                        <div
                                            class="p-3 bg-purple-500/10 rounded-2xl text-purple-500"
                                        >
                                            <Target class="w-6 h-6" />
                                        </div>
                                        <span
                                            class="text-[9px] font-black text-purple-400 uppercase italic"
                                            >Favorita</span
                                        >
                                    </div>
                                    <p
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1"
                                    >
                                        Cancha más pedida
                                    </p>
                                    <h4
                                        class="text-xl font-black text-slate-900 dark:text-white truncate uppercase italic"
                                    >
                                        {{
                                            reportData.top_court?.name || "---"
                                        }}
                                    </h4>
                                    <p
                                        v-if="reportData.top_court"
                                        class="mt-1 text-[10px] font-bold text-slate-500"
                                    >
                                        {{
                                            reportData.top_court
                                                .reservations_count
                                        }}
                                        alquileres logrados
                                    </p>
                                </div>

                                <div
                                    class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-xl hover:shadow-amber-500/5 transition-all"
                                >
                                    <div
                                        class="flex items-center justify-between mb-4"
                                    >
                                        <div
                                            class="p-3 bg-amber-500/10 rounded-2xl text-amber-500"
                                        >
                                            <Users class="w-6 h-6" />
                                        </div>
                                        <button
                                            class="text-[8px] font-black text-amber-500 underline uppercase tracking-tighter"
                                        >
                                            Ver Perfil
                                        </button>
                                    </div>
                                    <p
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1"
                                    >
                                        Cliente VIP
                                    </p>
                                    <h4
                                        class="text-xl font-black text-slate-900 dark:text-white truncate uppercase italic"
                                    >
                                        {{
                                            reportData.top_client?.name ||
                                            "Walk-in"
                                        }}
                                    </h4>
                                    <p
                                        v-if="reportData.top_client"
                                        class="mt-1 text-[10px] font-bold text-slate-500"
                                    >
                                        {{
                                            reportData.top_client
                                                .reservations_count
                                        }}
                                        visitas registradas
                                    </p>
                                </div>
                            </div>

                            <!-- Charts Grid -->
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                <!-- Revenue Chart -->
                                <div
                                    class="lg:col-span-2 p-8 rounded-[2rem] bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/50 shadow-sm"
                                >
                                    <div
                                        class="flex items-center justify-between mb-8"
                                    >
                                        <h3
                                            class="text-sm font-black text-slate-950 dark:text-white uppercase tracking-widest italic flex items-center gap-2"
                                        >
                                            <TrendingUp
                                                class="w-4 h-4 text-emerald-500"
                                            />
                                            Tendencia de Ingresos
                                        </h3>
                                        <div
                                            class="flex items-center gap-2 text-[10px] font-bold text-slate-500"
                                        >
                                            <div
                                                class="w-2 h-2 rounded-full bg-emerald-400"
                                            ></div>
                                            Ventas Diarias
                                        </div>
                                    </div>
                                    <div class="h-72">
                                        <Line
                                            :data="revenueChartData"
                                            :options="chartOptions"
                                        />
                                    </div>
                                </div>

                                <!-- Retention Doughnut -->
                                <div
                                    class="p-8 rounded-[2rem] bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/50 shadow-sm flex flex-col items-center"
                                >
                                    <h3
                                        class="text-sm font-black text-slate-950 dark:text-white uppercase tracking-widest italic mb-6 w-full text-left flex items-center gap-2"
                                    >
                                        <Users
                                            class="w-4 h-4 text-emerald-500"
                                        />
                                        Fidelización
                                    </h3>
                                    <div class="h-56 w-full relative">
                                        <Doughnut
                                            :data="clientRetentionData"
                                            :options="donutOptions"
                                        />
                                        <div
                                            class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none"
                                        >
                                            <span
                                                class="text-2xl font-black text-slate-900 dark:text-white"
                                                >{{
                                                    (
                                                        (reportData
                                                            .client_retention
                                                            .returning /
                                                            (reportData
                                                                .client_retention
                                                                .returning +
                                                                reportData
                                                                    .client_retention
                                                                    .new)) *
                                                            100 || 0
                                                    ).toFixed(0)
                                                }}%</span
                                            >
                                            <span
                                                class="text-[8px] font-black text-slate-400 uppercase tracking-tighter"
                                                >Retención</span
                                            >
                                        </div>
                                    </div>
                                    <div
                                        class="mt-8 grid grid-cols-2 gap-4 w-full text-center"
                                    >
                                        <div
                                            class="p-3 bg-slate-50 dark:bg-slate-950/40 rounded-2xl"
                                        >
                                            <p
                                                class="text-[8px] font-black text-slate-400 uppercase"
                                            >
                                                Nuevos
                                            </p>
                                            <p
                                                class="text-lg font-black text-slate-900 dark:text-white"
                                            >
                                                {{
                                                    reportData.client_retention
                                                        .new
                                                }}
                                            </p>
                                        </div>
                                        <div
                                            class="p-3 bg-emerald-500/5 rounded-2xl"
                                        >
                                            <p
                                                class="text-[8px] font-black text-emerald-500 uppercase"
                                            >
                                                Fieles
                                            </p>
                                            <p
                                                class="text-lg font-black text-emerald-600 dark:text-emerald-400"
                                            >
                                                {{
                                                    reportData.client_retention
                                                        .returning
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Peak Hours Chart -->
                                <div
                                    class="lg:col-span-3 p-8 rounded-[2rem] bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/50 shadow-sm"
                                >
                                    <h3
                                        class="text-sm font-black text-slate-950 dark:text-white uppercase tracking-widest italic mb-8 flex items-center gap-2"
                                    >
                                        <Clock class="w-4 h-4 text-sky-500" />
                                        Distribución Horaria (Horas Pico)
                                    </h3>
                                    <div class="h-64">
                                        <Bar
                                            :data="peakHoursChartData"
                                            :options="chartOptions"
                                        />
                                    </div>
                                    <div
                                        class="mt-6 flex items-center justify-center gap-6"
                                    >
                                        <div
                                            class="flex items-center gap-2 text-[9px] font-black text-slate-500 uppercase bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-full"
                                        >
                                            <CheckCircle
                                                class="w-3 h-3 text-emerald-500"
                                            />
                                            Hora sugerida para promociones:
                                            <span
                                                class="text-emerald-600 dark:text-emerald-400"
                                                >{{
                                                    reportData.hourly_distribution
                                                        .slice()
                                                        .sort(
                                                            (a: any, b: any) =>
                                                                a.count -
                                                                b.count,
                                                        )[0]?.hour || "---"
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Calendario Diario y Reservas Modal -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div
                class="absolute inset-0 bg-slate-950/40 backdrop-blur-sm"
                @click="showModal = false"
            ></div>
            <div
                class="relative w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-3xl bg-white dark:bg-slate-900 p-8 shadow-2xl border border-slate-200 dark:border-slate-800"
            >
                <div
                    class="flex items-center justify-between mb-4 border-b border-slate-200 dark:border-slate-800 pb-4"
                >
                    <h2 class="text-xl font-bold dark:text-emerald-300">
                        Disponibilidad: {{ modalDate }}
                    </h2>
                    <button
                        @click="showModal = false"
                        class="text-slate-500 hover:text-slate-800 dark:hover:text-white"
                    >
                        ✕
                    </button>
                </div>

                <div
                    v-if="loadingModal"
                    class="py-12 text-center text-slate-500"
                >
                    Cargando disponibilidad...
                </div>

                <div v-else class="space-y-6">
                    <!-- Formulario de Reserva Rápida -->
                    <div
                        v-if="showFastReservationForm"
                        class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-6 mb-6 shadow-inner"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <h4
                                class="font-black text-emerald-600 dark:text-emerald-400 uppercase italic tracking-tighter"
                            >
                                {{
                                    fastReservationForm.id
                                        ? "Modificar"
                                        : "Nueva"
                                }}
                                Reserva: {{ fastReservationCourtName }}
                            </h4>
                            <span
                                class="text-xs font-bold text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded"
                                >{{ fastReservationForm.start_time }} h</span
                            >
                        </div>

                        <div class="grid md:grid-cols-2 gap-4 mb-4">
                            <div class="space-y-1 relative">
                                <label
                                    class="block text-[10px] font-black uppercase text-slate-500 tracking-widest italic"
                                    >Nombre del Cliente</label
                                >
                                <input
                                    v-model="fastReservationForm.client_name"
                                    @focus="showFastSuggestions = true"
                                    @input="
                                        fastReservationForm.client_user_id =
                                            null;
                                        showFastSuggestions = true;
                                    "
                                    type="text"
                                    placeholder="Ej: Juan Pérez"
                                    class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 text-sm focus:ring-2 focus:ring-emerald-500/20 shadow-sm"
                                    required
                                />

                                <!-- Sugerencias Autocomplete -->
                                <div
                                    v-if="filteredClients.length"
                                    class="absolute z-[70] left-0 right-0 mt-1 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xl overflow-hidden"
                                >
                                    <div
                                        v-for="client in filteredClients"
                                        :key="client.id"
                                        @click="selectClient(client)"
                                        class="p-3 text-sm border-b border-slate-100 dark:border-slate-800 last:border-0 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 cursor-pointer transition-colors flex justify-between items-center"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="font-bold text-slate-900 dark:text-white"
                                                >{{ client.name }}</span
                                            >
                                            <span
                                                v-if="client.phone"
                                                class="text-[10px] text-slate-500 font-bold italic tracking-tighter"
                                                >({{ client.phone }})</span
                                            >
                                        </div>
                                        <span
                                            class="text-[8px] font-black text-emerald-600 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-1 rounded uppercase"
                                            >Seleccionar</span
                                        >
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="block text-[10px] font-black uppercase text-slate-500 tracking-widest italic"
                                    >WhatsApp / Teléfono</label
                                >
                                <input
                                    v-model="fastReservationForm.client_phone"
                                    type="text"
                                    placeholder="Ej: 11223344"
                                    class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 text-sm focus:ring-2 focus:ring-emerald-500/20 shadow-sm"
                                />
                            </div>
                        </div>

                        <div class="space-y-2 mb-6">
                            <label
                                class="block text-[10px] font-black uppercase text-emerald-600 dark:text-emerald-400 tracking-widest italic"
                                >Duración del Turno</label
                            >
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="d in [30, 60, 90, 120]"
                                    :key="d"
                                    type="button"
                                    @click="
                                        fastReservationForm.duration = d;
                                        updateEndTimeByDuration();
                                    "
                                    class="flex-1 py-2 px-3 rounded-lg text-xs font-black transition-all border flex flex-col items-center justify-center gap-0.5 min-w-[70px]"
                                    :class="
                                        fastReservationForm.duration == d
                                            ? 'bg-emerald-500 border-emerald-500 text-white shadow-lg shadow-emerald-500/20 scale-105'
                                            : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-500 hover:border-emerald-300'
                                    "
                                >
                                    <span>{{ d }} MIN</span>
                                    <span
                                        v-if="getSelectedCourtPrice(d)"
                                        class="text-[8px] opacity-80"
                                        :class="
                                            fastReservationForm.duration == d
                                                ? 'text-white'
                                                : 'text-emerald-600 dark:text-emerald-400'
                                        "
                                        >{{
                                            formatMoney(
                                                getSelectedCourtPrice(d),
                                            )
                                        }}</span
                                    >
                                </button>
                            </div>
                            <p class="text-[10px] text-slate-400 italic">
                                Finaliza a las:
                                <span
                                    class="font-bold text-slate-900 dark:text-slate-100"
                                    >{{ fastReservationForm.end_time }} h</span
                                >
                            </p>
                        </div>

                        <!-- Checkbox Pagado (Fast) -->
                        <div
                            class="flex items-center gap-3 mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-800/30 transition-all hover:bg-emerald-100 dark:hover:bg-emerald-800/40 cursor-pointer group"
                            @click="
                                fastReservationForm.is_paid =
                                    !fastReservationForm.is_paid
                            "
                        >
                            <div
                                class="w-6 h-6 rounded-lg border-2 flex items-center justify-center transition-all bg-white dark:bg-slate-900"
                                :class="
                                    fastReservationForm.is_paid
                                        ? 'bg-emerald-500 border-emerald-500 shadow-lg shadow-emerald-500/20 scale-110'
                                        : 'border-slate-300 dark:border-slate-700 group-hover:border-emerald-400'
                                "
                            >
                                <svg
                                    v-if="fastReservationForm.is_paid"
                                    class="w-4 h-4 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="3"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </div>
                            <span
                                class="text-xs font-black uppercase text-slate-700 dark:text-slate-300 italic tracking-tighter select-none"
                                >¿Deja pagado el turno? ✅</span
                            >
                        </div>

                        <div class="flex gap-3">
                            <button
                                @click="submitFastReservation"
                                type="button"
                                class="flex-2 rounded-xl bg-emerald-500 px-6 py-3 text-xs font-black text-white hover:bg-emerald-400 uppercase tracking-widest shadow-lg shadow-emerald-500/20 transition-all active:scale-95"
                            >
                                {{
                                    fastReservationForm.id
                                        ? "Actualizar"
                                        : "Confirmar"
                                }}
                                Reserva
                            </button>
                            <button
                                @click="showFastReservationForm = false"
                                type="button"
                                class="flex-1 rounded-xl border border-slate-300 dark:border-slate-700 px-4 py-3 text-xs font-bold text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all uppercase"
                            >
                                Cancelar
                            </button>
                        </div>
                        <div
                            v-if="fastReservationForm.errors.start_time"
                            class="text-rose-500 text-[10px] font-bold mt-2 px-1 uppercase"
                        >
                            {{ fastReservationForm.errors.start_time }}
                        </div>
                        <div
                            v-if="fastReservationForm.errors.client_name"
                            class="text-rose-500 text-[10px] font-bold mt-2 px-1 uppercase"
                        >
                            {{ fastReservationForm.errors.client_name }}
                        </div>
                    </div>

                    <!-- Listado de Disponibilidad si existe modalData -->
                    <div
                        v-if="modalData && modalData.availability"
                        class="space-y-6"
                    >
                        <p
                            v-if="modalData.availability.courts.length === 0"
                            class="py-12 text-center text-slate-500 italic"
                        >
                            No se encontraron canchas activas o disponibles para
                            esta fecha.
                        </p>

                        <div
                            v-for="court in modalData.availability.courts"
                            :key="court.id"
                            class="rounded-xl border border-slate-200 dark:border-slate-800 p-4"
                        >
                            <div
                                class="flex items-center justify-between mb-3 border-b border-slate-100 dark:border-slate-800/50 pb-2"
                            >
                                <h3
                                    class="font-bold text-slate-800 dark:text-slate-100"
                                >
                                    {{ court.name }}
                                </h3>
                                <span class="text-xs text-slate-500"
                                    >{{
                                        formatMoney(court.base_price)
                                    }}
                                    base</span
                                >
                            </div>

                            <div
                                class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2"
                            >
                                <div
                                    v-for="slot in court.available_slots"
                                    :key="slot.start_time"
                                    class="rounded-md border p-2 flex flex-col items-center justify-center text-center transition-colors relative border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-950/20 hover:border-emerald-500 cursor-pointer"
                                    @click="
                                        !showFastReservationForm
                                            ? startFastReservation(
                                                  modalComplexId,
                                                  court.id,
                                                  court.name,
                                                  slot.start_time,
                                                  slot.end_time,
                                              )
                                            : null
                                    "
                                >
                                    <span
                                        class="font-semibold text-sm dark:text-slate-200"
                                        >{{ slot.start_time }}</span
                                    >
                                    <span
                                        class="text-[10px] text-slate-500 dark:text-slate-400"
                                        >{{
                                            formatMoney(court.base_price)
                                        }}</span
                                    >
                                </div>
                            </div>
                            <p
                                v-if="court.available_slots.length === 0"
                                class="text-sm text-slate-500"
                            >
                                Sin slots libres para esta cancha.
                            </p>
                        </div>

                        <!-- Reservas Existentes en el Modal -->
                        <div
                            v-if="modalData.reservations?.length > 0"
                            class="pt-6 border-t border-slate-200 dark:border-slate-800"
                        >
                            <h3
                                class="text-sm font-black uppercase text-slate-400 mb-4 tracking-widest italic"
                            >
                                Turnos ya Ocupados
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div
                                    v-for="res in modalData.reservations"
                                    :key="res.id"
                                    @click="openReservationDetails(res)"
                                    class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/60 transition-colors"
                                >
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="font-black text-slate-900 dark:text-white"
                                            >{{ res.start_time }}</span
                                        >
                                        <div
                                            class="h-4 w-[1px] bg-slate-200 dark:bg-slate-700"
                                        ></div>
                                        <div>
                                            <p
                                                class="text-xs font-bold text-slate-700 dark:text-slate-300"
                                            >
                                                {{ res.client_name }}
                                            </p>
                                            <p
                                                class="text-[9px] text-slate-400 uppercase font-bold"
                                            >
                                                {{
                                                    modalData.availability.courts.find(
                                                        (c) =>
                                                            c.id ===
                                                            res.court_id,
                                                    )?.name
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <button
                                        v-if="!res.is_recurring"
                                        @click.stop="
                                            cancelReservation(
                                                modalComplexId,
                                                res.id,
                                            )
                                        "
                                        class="text-rose-500 hover:scale-125 transition-transform"
                                        title="Cancelar"
                                    >
                                        &times;
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Client Form Modal -->
        <div
            v-if="showClientFormModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div
                class="absolute inset-0 bg-slate-950/40 backdrop-blur-sm"
                @click="showClientFormModal = false"
            ></div>
            <div
                class="relative w-full max-w-md rounded-3xl bg-white dark:bg-slate-900 p-8 shadow-2xl border border-slate-200 dark:border-slate-800"
            >
                <h2
                    class="text-2xl font-black mb-6 text-slate-900 dark:text-emerald-400 uppercase italic tracking-tighter"
                >
                    {{ clientForm.id ? "Editar" : "Nuevo" }} Cliente
                </h2>
                <form @submit.prevent="saveClient" class="space-y-4">
                    <div class="space-y-1">
                        <label
                            class="block text-[10px] font-black text-slate-500 uppercase tracking-widest italic"
                            >Nombre Completo</label
                        >
                        <input
                            v-model="clientForm.name"
                            type="text"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white p-3 text-sm focus:ring-2 focus:ring-emerald-500/20"
                            required
                        />
                        <span
                            v-if="clientForm.errors.name"
                            class="text-[10px] font-bold text-rose-500 mt-1 block px-1 tracking-tight"
                            >{{ clientForm.errors.name }}</span
                        >
                    </div>
                    <div class="space-y-1">
                        <label
                            class="block text-[10px] font-black text-slate-500 uppercase tracking-widest italic"
                            >Correo Electrónico</label
                        >
                        <input
                            v-model="clientForm.email"
                            type="email"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white p-3 text-sm focus:ring-2 focus:ring-emerald-500/20"
                            required
                        />
                        <span
                            v-if="clientForm.errors.email"
                            class="text-[10px] font-bold text-rose-500 mt-1 block px-1 tracking-tight"
                            >{{ clientForm.errors.email }}</span
                        >
                    </div>
                    <div class="space-y-1">
                        <label
                            class="block text-[10px] font-black text-slate-500 uppercase tracking-widest italic"
                            >Teléfono / WhatsApp</label
                        >
                        <input
                            v-model="clientForm.phone"
                            type="text"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white p-3 text-sm focus:ring-2 focus:ring-emerald-500/20"
                        />
                    </div>
                    <div v-if="!clientForm.id" class="space-y-1">
                        <label
                            class="block text-[10px] font-black text-slate-500 uppercase tracking-widest italic"
                            >Contraseña (Mín. 8 caracteres)</label
                        >
                        <input
                            v-model="clientForm.password"
                            type="password"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white p-3 text-sm focus:ring-2 focus:ring-emerald-500/20"
                            minlength="8"
                        />
                        <span
                            v-if="clientForm.errors.password"
                            class="text-[10px] font-bold text-rose-500 mt-1 block px-1 tracking-tight"
                            >{{ clientForm.errors.password }}</span
                        >
                    </div>
                    <div class="space-y-1">
                        <label
                            class="block text-[10px] font-black text-slate-500 uppercase tracking-widest italic"
                            >Estado de Cuenta</label
                        >
                        <select
                            v-model="clientForm.status"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white p-3 text-sm focus:ring-2 focus:ring-emerald-500/20"
                        >
                            <option value="activo">✅ Activo</option>
                            <option value="suspendido">🚫 Suspendido</option>
                        </select>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <button
                            type="button"
                            @click="showClientFormModal = false"
                            class="flex-1 py-3 rounded-xl font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 uppercase text-xs tracking-widest"
                        >
                            Descartar
                        </button>
                        <button
                            type="submit"
                            class="flex-1 py-3 rounded-xl font-black bg-emerald-400 text-slate-950 shadow-lg shadow-emerald-400/20 uppercase text-xs tracking-widest"
                        >
                            Guardar Cliente
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recurring Reservation (Turno Fijo) Modal -->
        <div
            v-if="showRecurringModal"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4 overflow-y-auto"
        >
            <div
                class="fixed inset-0 bg-slate-950/60 backdrop-blur-md"
                @click="showRecurringModal = false"
            ></div>
            <div
                class="relative w-full max-w-lg rounded-[2.5rem] bg-white dark:bg-slate-900 p-8 shadow-2xl border border-white/20 dark:border-slate-800 my-auto"
            >
                <div class="flex items-center justify-between mb-8">
                    <h2
                        class="text-3xl font-black text-emerald-600 dark:text-emerald-400 uppercase italic tracking-tighter"
                    >
                        {{ recurringForm.id ? "Modificar" : "Nuevo" }} Turno
                        Fijo
                    </h2>
                    <button
                        @click="showRecurringModal = false"
                        class="text-slate-400 hover:text-rose-500 transition-colors"
                    >
                        <svg
                            class="h-8 w-8"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <form
                    @submit.prevent="
                        submitRecurringReservation(props.complexes[0].id)
                    "
                    class="space-y-6"
                >
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label
                                class="block text-[10px] font-black text-slate-500 uppercase tracking-widest italic"
                                >Cancha</label
                            >
                            <select
                                v-model="recurringForm.court_id"
                                class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-4 text-sm font-bold focus:ring-4 focus:ring-emerald-500/10"
                                required
                            >
                                <option value="" disabled>
                                    Seleccionar Cancha
                                </option>
                                <option
                                    v-for="court in props.complexes[0].courts"
                                    :key="court.id"
                                    :value="court.id"
                                >
                                    {{ court.name }}
                                </option>
                            </select>
                            <span
                                v-if="recurringForm.errors.court_id"
                                class="text-[10px] font-bold text-rose-500 mt-1 block px-1 tracking-tight"
                                >{{ recurringForm.errors.court_id }}</span
                            >
                        </div>
                        <div class="space-y-1">
                            <label
                                class="block text-[10px] font-black text-slate-500 uppercase tracking-widest italic"
                                >Día de la Semana</label
                            >
                            <select
                                v-model="recurringForm.day_of_week"
                                class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-4 text-sm font-bold focus:ring-4 focus:ring-emerald-500/10"
                                required
                            >
                                <option
                                    v-for="(label, value) in dayLabels"
                                    :key="value"
                                    :value="parseInt(value)"
                                >
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label
                                class="block text-[10px] font-black text-slate-500 uppercase tracking-widest italic"
                                >Horario Inicio</label
                            >
                            <input
                                v-model="recurringForm.start_time"
                                type="time"
                                class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-4 text-sm font-bold focus:ring-4 focus:ring-emerald-500/10"
                                required
                            />
                        </div>
                        <div class="space-y-1">
                            <label
                                class="block text-[10px] font-black text-slate-500 uppercase tracking-widest italic"
                                >Horario Fin</label
                            >
                            <input
                                v-model="recurringForm.end_time"
                                type="time"
                                class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-4 text-sm font-bold focus:ring-4 focus:ring-emerald-500/10"
                                required
                            />
                            <span
                                v-if="recurringForm.errors.end_time"
                                class="text-[10px] font-bold text-rose-500 mt-1 block px-1 tracking-tight"
                                >{{ recurringForm.errors.end_time }}</span
                            >
                        </div>
                    </div>

                    <div
                        class="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800"
                    >
                        <div class="space-y-1 relative">
                            <label
                                class="block text-[10px] font-black text-slate-500 uppercase tracking-widest italic"
                                >Nombre del Cliente</label
                            >
                            <input
                                v-model="recurringForm.client_name"
                                @focus="showRecurringSuggestions = true"
                                @input="
                                    recurringForm.client_user_id = null;
                                    showRecurringSuggestions = true;
                                "
                                type="text"
                                placeholder="Ej: Juan Pérez"
                                class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-4 text-sm font-bold focus:ring-4 focus:ring-emerald-500/10 shadow-sm"
                                required
                            />

                            <div
                                v-if="filteredClientsRecurring.length"
                                class="absolute z-[70] left-0 right-0 mt-1 card overflow-hidden shadow-2xl"
                            >
                                <div
                                    v-for="client in filteredClientsRecurring"
                                    :key="client.id"
                                    @click="selectClientRecurring(client)"
                                    class="p-4 text-sm border-b border-slate-50 dark:border-slate-800 last:border-0 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 cursor-pointer transition-colors flex justify-between items-center"
                                >
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="font-bold text-slate-900 dark:text-white"
                                            >{{ client.name }}</span
                                        >
                                        <span
                                            v-if="client.phone"
                                            class="text-[10px] text-slate-500 font-bold italic tracking-tighter"
                                            >({{ client.phone }})</span
                                        >
                                    </div>
                                    <span
                                        class="text-[8px] font-black text-emerald-600 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-1 rounded uppercase tracking-widest"
                                        >Seleccionar</span
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label
                                class="block text-[10px] font-black text-slate-500 uppercase tracking-widest italic"
                                >Teléfono / WhatsApp</label
                            >
                            <input
                                v-model="recurringForm.client_phone"
                                type="text"
                                placeholder="Ej: 11223344"
                                class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-4 text-sm font-bold focus:ring-4 focus:ring-emerald-500/10"
                            />
                        </div>

                        <!-- Checkbox Pagado (Recurring) -->
                        <div
                            class="flex items-center gap-2 p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 transition-colors hover:border-emerald-500/30"
                        >
                            <input
                                v-model="recurringForm.is_paid"
                                type="checkbox"
                                id="recurring_is_paid"
                                class="w-6 h-6 rounded-lg border-slate-300 text-emerald-500 focus:ring-emerald-500/20"
                            />
                            <label
                                for="recurring_is_paid"
                                class="text-sm font-black uppercase text-slate-600 dark:text-slate-400 cursor-pointer select-none tracking-tighter italic"
                                >¿Se abona por adelantado? ✅</label
                            >
                        </div>

                        <div class="space-y-1">
                            <label
                                class="block text-[10px] font-black text-slate-500 uppercase tracking-widest italic"
                                >Notas Adicionales</label
                            >
                            <textarea
                                v-model="recurringForm.notes"
                                rows="2"
                                class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-4 text-sm font-bold focus:ring-4 focus:ring-emerald-500/10"
                                placeholder="Opcional..."
                            ></textarea>
                        </div>
                    </div>

                    <div
                        v-if="recurringForm.errors.time"
                        class="p-3 rounded-xl bg-rose-50 dark:bg-rose-900/10 border border-rose-200 dark:border-rose-800 text-rose-500 text-xs font-bold text-center italic"
                    >
                        ⚠️ {{ recurringForm.errors.time }}
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button
                            type="button"
                            @click="showRecurringModal = false"
                            class="flex-1 py-4 rounded-2xl font-black bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 uppercase text-xs tracking-widest transition-all hover:bg-slate-200 dark:hover:bg-slate-700"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="recurringForm.processing"
                            class="flex-1 py-4 rounded-2xl font-black bg-emerald-400 text-slate-950 shadow-lg shadow-emerald-400/20 uppercase text-xs tracking-widest transition-all hover:bg-emerald-300 disabled:opacity-50"
                        >
                            {{ recurringForm.id ? "Actualizar" : "Confirmar" }}
                            Fijo
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Editar Complejo -->
        <div
            v-if="showEditComplexModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div
                class="absolute inset-0 bg-slate-950/40 backdrop-blur-sm"
                @click="showEditComplexModal = false"
            ></div>
            <div
                class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-3xl bg-white dark:bg-slate-900 p-8 shadow-2xl border border-slate-200 dark:border-slate-800"
            >
                <div
                    class="flex items-center justify-between mb-8 border-b border-slate-100 dark:border-slate-800 pb-6"
                >
                    <h2
                        class="text-2xl font-black text-slate-900 dark:text-emerald-400 uppercase italic tracking-tighter"
                    >
                        ⚙️ Configuración del Complejo
                    </h2>
                    <button
                        @click="showEditComplexModal = false"
                        class="text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors"
                    >
                        &times;
                    </button>
                </div>

                <form
                    @submit.prevent="saveEditComplex"
                    class="grid grid-cols-1 md:grid-cols-2 gap-6"
                >
                    <div class="md:col-span-2 space-y-1">
                        <label
                            class="block text-[10px] font-black uppercase text-slate-500 tracking-widest italic mb-1"
                            >Nombre Comercial</label
                        >
                        <input
                            v-model="editComplexForm.name"
                            type="text"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-emerald-500/20"
                            required
                        />
                    </div>
                    <div class="md:col-span-2 space-y-1">
                        <label
                            class="block text-[10px] font-black uppercase text-slate-500 tracking-widest italic mb-1"
                            >Dirección Exacta</label
                        >
                        <input
                            v-model="editComplexForm.address_line"
                            type="text"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white px-4 py-3 text-sm focus:ring-2 focus:ring-emerald-500/20"
                            required
                        />
                    </div>
                    <div class="space-y-1">
                        <label
                            class="block text-[10px] font-black uppercase text-slate-500 tracking-widest italic mb-1"
                            >Canal de Contacto</label
                        >
                        <input
                            v-model="editComplexForm.phone_contact"
                            type="text"
                            placeholder="Ej: WhatsApp"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white px-4 py-3 text-sm focus:ring-2 focus:ring-emerald-500/20"
                        />
                    </div>
                    <div class="space-y-1">
                        <label
                            class="block text-[10px] font-black uppercase text-slate-500 tracking-widest italic mb-1"
                            >Breve Descripción</label
                        >
                        <input
                            v-model="editComplexForm.description"
                            type="text"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white px-4 py-3 text-sm focus:ring-2 focus:ring-emerald-500/20"
                        />
                    </div>
                    <div class="space-y-1">
                        <label
                            class="block text-[10px] font-black uppercase text-sky-600 dark:text-sky-400 tracking-widest italic mb-1"
                            >Instagram URL</label
                        >
                        <input
                            v-model="editComplexForm.instagram_url"
                            type="text"
                            placeholder="https://instagram.com/..."
                            class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white px-4 py-3 text-sm focus:ring-2 focus:ring-sky-500/20"
                        />
                    </div>
                    <div class="space-y-1">
                        <label
                            class="block text-[10px] font-black uppercase text-blue-600 dark:text-blue-400 tracking-widest italic mb-1"
                            >Facebook URL</label
                        >
                        <input
                            v-model="editComplexForm.facebook_url"
                            type="text"
                            placeholder="https://facebook.com/..."
                            class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500/20"
                        />
                    </div>

                    <div
                        class="md:col-span-2 pt-4 border-t border-slate-100 dark:border-slate-800"
                    >
                        <label
                            class="block text-[10px] font-black uppercase text-slate-900 dark:text-white tracking-widest mb-4"
                            >Servicios Disponibles</label
                        >
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="service in props.catalogs.services"
                                :key="service.id"
                                type="button"
                                class="rounded-xl border px-4 py-2 text-xs transition-all font-bold"
                                :class="
                                    editComplexForm.service_ids.includes(
                                        service.id,
                                    )
                                        ? 'border-emerald-400 bg-emerald-400 text-slate-950 shadow-lg shadow-emerald-400/20'
                                        : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:border-emerald-300'
                                "
                                @click="toggleEditService(service.id)"
                            >
                                {{ service.name }}
                            </button>
                        </div>
                    </div>

                    <div
                        class="md:col-span-2 flex gap-4 mt-6 pt-6 border-t border-slate-100 dark:border-slate-800"
                    >
                        <button
                            type="button"
                            @click="showEditComplexModal = false"
                            class="flex-1 py-4 rounded-xl font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 uppercase text-xs tracking-widest"
                        >
                            DESCARTAR
                        </button>
                        <button
                            type="submit"
                            :disabled="editComplexForm.processing"
                            class="flex-1 py-4 rounded-xl font-black bg-emerald-400 text-slate-950 shadow-lg shadow-emerald-400/20 uppercase text-xs tracking-widest disabled:opacity-50"
                        >
                            {{
                                editComplexForm.processing
                                    ? "GUARDANDO..."
                                    : "GUARDAR CAMBIOS"
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Detalles de Reserva -->
        <div
            v-if="showReservationDetailsModal"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4"
        >
            <div
                class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"
                @click="showReservationDetailsModal = false"
            ></div>
            <div
                class="relative w-full max-w-md rounded-3xl bg-white dark:bg-slate-900 p-8 shadow-2xl border border-slate-200 dark:border-slate-800"
            >
                <div
                    class="flex items-center justify-between mb-6 border-b border-slate-100 dark:border-slate-800 pb-4"
                >
                    <h2
                        class="text-xl font-black text-emerald-600 dark:text-emerald-400 uppercase italic tracking-tighter"
                    >
                        Detalles de Reserva
                    </h2>
                    <button
                        @click="showReservationDetailsModal = false"
                        class="text-slate-400 hover:text-slate-900 dark:hover:text-white"
                    >
                        &times;
                    </button>
                </div>

                <div v-if="selectedReservation" class="space-y-6">
                    <div
                        v-if="selectedReservation.is_recurring"
                        class="p-4 rounded-2xl bg-sky-50 dark:bg-sky-500/10 border border-sky-100 dark:border-sky-500/20 text-sky-600 dark:text-sky-400 text-[10px] font-black uppercase tracking-widest italic text-center"
                    >
                        🗓️ TURNO FIJO PERMANENTE
                    </div>
                    <div
                        class="flex items-center gap-4 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-500/20"
                    >
                        <div
                            class="w-12 h-12 rounded-full bg-emerald-400 flex items-center justify-center text-slate-950 text-xl font-black italic"
                        >
                            {{
                                (
                                    selectedReservation.client_name ||
                                    selectedReservation.client?.name ||
                                    "?"
                                )
                                    .charAt(0)
                                    .toUpperCase()
                            }}
                        </div>
                        <div>
                            <p
                                class="text-[10px] font-black text-emerald-600 dark:text-emerald-500 uppercase tracking-widest italic"
                            >
                                Cliente
                            </p>
                            <h4
                                class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight"
                            >
                                {{
                                    selectedReservation.client_name ||
                                    selectedReservation.client?.name
                                }}
                            </h4>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div
                            class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800"
                        >
                            <p
                                class="text-[9px] font-black text-slate-500 uppercase mb-1"
                            >
                                Horario
                            </p>
                            <p
                                class="text-sm font-black text-slate-900 dark:text-white italic"
                            >
                                {{
                                    selectedReservation.start_at
                                        ? selectedReservation.start_at.substring(
                                              11,
                                              16,
                                          )
                                        : selectedReservation.start_time
                                }}
                                -
                                {{
                                    selectedReservation.end_at
                                        ? selectedReservation.end_at.substring(
                                              11,
                                              16,
                                          )
                                        : selectedReservation.end_time
                                }}
                            </p>
                        </div>
                        <div
                            class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800"
                        >
                            <p
                                class="text-[9px] font-black text-slate-500 uppercase mb-1"
                            >
                                Estado
                            </p>
                            <span
                                class="px-2 py-0.5 rounded text-[8px] uppercase font-black bg-emerald-400/20 text-emerald-700 dark:text-emerald-400"
                            >
                                {{ selectedReservation.status }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="
                            selectedReservation.client_phone ||
                            selectedReservation.client?.phone
                        "
                        class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800"
                    >
                        <p
                            class="text-[9px] font-black text-slate-500 uppercase mb-1"
                        >
                            Contacto
                        </p>
                        <p class="text-sm font-bold text-sky-500 underline">
                            {{
                                selectedReservation.client_phone ||
                                selectedReservation.client.phone
                            }}
                        </p>
                    </div>

                    <div
                        class="flex flex-wrap gap-3 pt-4 border-t border-slate-100 dark:border-slate-800 lg:flex-nowrap"
                    >
                        <button
                            @click="showReservationDetailsModal = false"
                            class="flex-1 py-3 rounded-xl font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-widest transition-all hover:bg-slate-200"
                        >
                            Cerrar
                        </button>
                        <button
                            @click="editReservation(selectedReservation)"
                            class="flex-1 py-3 rounded-xl font-black bg-sky-400 text-slate-950 shadow-lg shadow-sky-400/20 uppercase text-[10px] tracking-widest transition-all hover:bg-sky-300"
                        >
                            Modificar
                        </button>
                        <button
                            v-if="!selectedReservation.is_recurring"
                            @click="
                                cancelReservation(
                                    props.complexes[0].id,
                                    selectedReservation.id,
                                )
                            "
                            class="flex-1 py-3 rounded-xl font-black bg-rose-500 text-white shadow-lg shadow-rose-500/20 uppercase text-[10px] tracking-widest transition-all hover:bg-rose-400"
                        >
                            Cancelar
                        </button>
                        <button
                            v-else
                            @click="
                                deleteRecurring(
                                    props.complexes[0].id,
                                    parseInt(
                                        selectedReservation.id.replace(
                                            'recurring-',
                                            '',
                                        ),
                                    ),
                                )
                            "
                            class="flex-1 py-3 rounded-xl font-black bg-rose-500 text-white shadow-lg shadow-rose-500/20 uppercase text-[10px] tracking-widest transition-all hover:bg-rose-400"
                        >
                            Eliminar Fijo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppShell>
</template>
