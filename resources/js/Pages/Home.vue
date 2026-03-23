<script setup lang="ts">
import { computed } from "vue";
import { Link, router, useForm, usePage } from "@inertiajs/vue3";
import AppShell from "../Components/AppShell.vue";

type Sport = { id: number; name: string; slug: string };
type City = { id: number; province_id: number; name: string };
type Province = {
    id: number;
    name: string;
    code: string | null;
    cities: City[];
};
type AvailabilityCourt = {
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
type AvailabilityGroup = {
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
    courts: AvailabilityCourt[];
};

type AuthUser = {
    id: number;
    role: "cliente" | "admin_cancha" | "super_admin";
};

const props = defineProps<{
    appName: string;
    catalogs: {
        sports: Sport[];
        provinces: Province[];
    };
    filters: {
        sport_id: number | null;
        province_id: number | null;
        city_id: number | null;
        date: string | null;
        start_time: string | null;
        end_time: string | null;
    };
    availability: AvailabilityGroup[];
}>();

const page = usePage<{ auth: { user: AuthUser | null } }>();
const authUser = computed(() => page.props.auth?.user ?? null);

function getLocalTodayDate(): string {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, "0");
    const day = String(now.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
}

const defaultDate = getLocalTodayDate();
const defaultStartTime = "10:00";

const searchForm = useForm({
    sport_id: props.filters.sport_id ?? "",
    province_id: props.filters.province_id ?? "",
    city_id: props.filters.city_id ?? "",
    date: props.filters.date ?? defaultDate,
    start_time: props.filters.start_time ?? defaultStartTime,
});

const reserveForm = useForm({
    court_id: "",
    date: props.filters.date ?? defaultDate,
    start_time: props.filters.start_time ?? defaultStartTime,
    end_time: props.filters.end_time ?? "11:00",
});

const selectedProvince = computed(() =>
    props.catalogs.provinces.find(
        (province) => String(province.id) === String(searchForm.province_id),
    ),
);

const cityOptions = computed(() => selectedProvince.value?.cities ?? []);

function submitSearch(): void {
    router.get("/", searchForm.data(), {
        preserveState: true,
        replace: true,
    });
}

function reserve(courtId: number, startTime: string, endTime: string): void {
    reserveForm.court_id = String(courtId);
    reserveForm.date = searchForm.date;
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

function footballFormatLabel(court: AvailabilityCourt): string | null {
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

function complexProfileHref(group: AvailabilityGroup): string {
    const params = new URLSearchParams();

    if (searchForm.sport_id) {
        params.set("sport_id", String(searchForm.sport_id));
    }

    if (searchForm.date) {
        params.set("date", String(searchForm.date));
    }

    if (searchForm.start_time) {
        params.set("start_time", String(searchForm.start_time));
    }

    const queryString = params.toString();

    if (!queryString) {
        return `/complejos/${group.complex.slug}`;
    }

    return `/complejos/${group.complex.slug}?${queryString}`;
}
</script>

<template>
    <AppShell>
        <section class="card shadow-sm">
            <p
                class="text-xs uppercase tracking-[0.25em] text-emerald-600 dark:text-emerald-300 transition-colors duration-300"
            >
                Buscador inteligente
            </p>
            <h1 class="mt-2 text-3xl font-black leading-tight sm:text-5xl">
                Reserva canchas de cualquier deporte en minutos.
            </h1>
            <p
                class="mt-4 max-w-3xl text-sm text-slate-600 dark:text-slate-300 transition-colors duration-300 sm:text-base"
            >
                Selecciona deporte, provincia/ciudad, fecha y horario. El
                sistema te mostrara solo disponibilidad real.
            </p>

            <form
                class="mt-6 grid gap-3 md:grid-cols-3 lg:grid-cols-5"
                @submit.prevent="submitSearch"
            >
                <select
                    v-model="searchForm.sport_id"
                    required
                    class="form-field"
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
                    v-model="searchForm.province_id"
                    required
                    class="form-field"
                >
                    <option value="">Provincia</option>
                    <option
                        v-for="province in props.catalogs.provinces"
                        :key="province.id"
                        :value="String(province.id)"
                    >
                        {{ province.name }}
                    </option>
                </select>

                <select
                    v-model="searchForm.city_id"
                    required
                    class="form-field"
                >
                    <option value="">Ciudad</option>
                    <option
                        v-for="city in cityOptions"
                        :key="city.id"
                        :value="String(city.id)"
                    >
                        {{ city.name }}
                    </option>
                </select>

                <input
                    v-model="searchForm.date"
                    type="date"
                    required
                    class="form-field"
                />
                <div class="flex gap-2">
                    <input
                        v-model="searchForm.start_time"
                        type="time"
                        step="60"
                        required
                        class="form-field w-full"
                    />
                    <button type="submit" class="btn-primary">Buscar</button>
                </div>
            </form>

            <p
                class="mt-3 text-xs text-slate-500 dark:text-slate-400 transition-colors duration-300"
            >
                Puedes explorar complejos, mapa y horarios disponibles sin
                iniciar sesion. Solo se solicita cuenta al momento de reservar.
            </p>
        </section>

        <section class="mt-6 space-y-4">
            <div
                v-if="props.availability.length === 0"
                class="rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 transition-colors duration-300 p-6 text-sm text-slate-600 dark:text-slate-300 transition-colors duration-300"
            >
                Aun no hay resultados. Usa los filtros para buscar
                disponibilidad.
            </div>

            <article
                v-for="group in props.availability"
                :key="group.complex.id"
                class="card shadow-sm"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2
                            class="text-xl font-bold text-emerald-600 dark:text-emerald-300 transition-colors duration-300"
                        >
                            <Link
                                :href="complexProfileHref(group)"
                                class="hover:text-emerald-700 dark:hover:text-emerald-200 transition-colors duration-300 hover:underline"
                            >
                                {{ group.complex.name }}
                            </Link>
                        </h2>
                        <p
                            class="text-sm text-slate-600 dark:text-slate-300 transition-colors duration-300"
                        >
                            {{ group.complex.address }}
                        </p>
                        <p
                            class="text-xs uppercase tracking-wide text-slate-500"
                        >
                            {{ group.complex.city }} ·
                            {{ group.complex.province }}
                        </p>
                    </div>
                    <span
                        class="rounded-md border border-slate-300 dark:border-slate-700 transition-colors duration-300 bg-slate-50 dark:bg-slate-950 transition-colors duration-300 px-3 py-1 text-xs text-slate-600 dark:text-slate-300 transition-colors duration-300"
                    >
                        {{ group.courts.length }} canchas con turnos libres
                    </span>
                </div>

                <div class="mt-4 grid gap-4 lg:grid-cols-3">
                    <div
                        class="hidden overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 transition-colors duration-300 sm:block"
                    >
                        <img
                            :src="group.complex.photo_url"
                            :alt="`Foto referencial de ${group.complex.name}`"
                            class="h-44 w-full object-cover"
                        />
                    </div>

                    <div
                        class="rounded-xl border border-slate-200 dark:border-slate-800 transition-colors duration-300 p-3 lg:col-span-2"
                    >
                        <p
                            v-if="group.complex.description"
                            class="text-sm text-slate-600 dark:text-slate-300 transition-colors duration-300"
                        >
                            {{ group.complex.description }}
                        </p>
                        <p
                            class="mt-1 text-xs text-slate-500 dark:text-slate-400 transition-colors duration-300"
                        >
                            Contacto:
                            {{ group.complex.phone_contact || "No informado" }}
                        </p>

                        <iframe
                            v-if="group.complex.map_embed_url"
                            :src="group.complex.map_embed_url"
                            class="mt-3 hidden h-44 w-full rounded-lg border border-slate-300 dark:border-slate-700 transition-colors duration-300 sm:block"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                        />

                        <a
                            :href="group.complex.map_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-3 hidden rounded-md border border-slate-400 dark:border-slate-600 transition-colors duration-300 px-3 py-1 text-xs font-semibold text-slate-800 dark:text-slate-200 transition-colors duration-300 hover:bg-slate-100 dark:hover:bg-slate-900 transition-colors duration-300 sm:inline-block"
                        >
                            Ver ubicacion en mapa
                        </a>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 lg:grid-cols-2">
                    <div
                        v-for="court in group.courts"
                        :key="court.id"
                        class="rounded-xl border border-slate-200 dark:border-slate-800 transition-colors duration-300 bg-slate-50/70 dark:bg-slate-950/70 transition-colors duration-300 p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold">{{ court.name }}</h3>
                                <p
                                    class="text-xs text-slate-500 dark:text-slate-400 transition-colors duration-300"
                                >
                                    {{ court.sport.name }} ·
                                    {{
                                        court.surface_type.replaceAll("_", " ")
                                    }}
                                </p>
                                <p
                                    v-if="footballFormatLabel(court)"
                                    class="mt-1 text-xs font-semibold text-emerald-600 dark:text-emerald-300 transition-colors duration-300"
                                >
                                    {{ footballFormatLabel(court) }}
                                </p>
                            </div>
                            <span
                                class="text-sm font-bold text-emerald-600 dark:text-emerald-300 transition-colors duration-300"
                                >{{ formatPrice(court.base_price) }}</span
                            >
                        </div>

                        <p
                            class="mt-2 text-xs text-slate-500 dark:text-slate-400 transition-colors duration-300"
                        >
                            {{ court.players_capacity }} jugadores · turnos de
                            {{ court.slot_duration_minutes }} min
                        </p>

                        <div class="mt-3">
                            <p
                                class="text-xs font-semibold text-slate-600 dark:text-slate-300 transition-colors duration-300"
                            >
                                Horarios libres desde
                                {{ searchForm.start_time }}
                            </p>

                            <div class="mt-2 flex flex-wrap gap-2">
                                <template
                                    v-for="slot in court.available_slots"
                                    :key="`${court.id}-${slot.start_time}-${slot.end_time}`"
                                >
                                    <button
                                        v-if="
                                            authUser &&
                                            authUser.role === 'cliente'
                                        "
                                        type="button"
                                        class="btn-primary text-xs px-3 py-1"
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
                                        class="rounded-md border border-slate-300 dark:border-slate-700 transition-colors duration-300 px-3 py-1 text-xs text-slate-600 dark:text-slate-300 transition-colors duration-300"
                                    >
                                        {{ slot.label }}
                                    </span>
                                </template>
                            </div>
                        </div>

                        <Link
                            v-if="!authUser"
                            href="/login"
                            class="mt-3 inline-block rounded-lg border border-slate-400 dark:border-slate-600 transition-colors duration-300 px-4 py-2 text-xs font-semibold text-slate-800 dark:text-slate-200 transition-colors duration-300 hover:bg-slate-100 dark:hover:bg-slate-900 transition-colors duration-300"
                        >
                            Inicia sesion para reservar
                        </Link>
                        <span
                            v-else-if="authUser.role !== 'cliente'"
                            class="mt-3 block text-xs text-amber-600 dark:text-amber-300 transition-colors duration-300"
                        >
                            Tu rol no puede reservar turnos de cliente.
                        </span>
                    </div>
                </div>
            </article>
        </section>
    </AppShell>
</template>
