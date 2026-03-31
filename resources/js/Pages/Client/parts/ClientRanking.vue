<script setup lang="ts">
import { ref, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";

const props = defineProps<{}>();
const page = usePage<any>();
const currentUserId = page.props.value?.auth?.user?.id ?? null;
const currentUserName = page.props.value?.auth?.user?.name ?? null;

const sports = ref<Array<{ id: number; name: string; slug?: string }>>([]);
const selectedSport = ref<string | number | null>(null);
const modalidad = ref<string | null>(null);
const rankings = ref<any[]>([]);

async function loadSports() {
    const res = await fetch("/api/catalog");
    const json = await res.json();
    sports.value = json.data?.sports || [];
    if (sports.value.length)
        selectedSport.value = sports.value[0].slug ?? sports.value[0].id;
}

async function loadRankings() {
    if (!selectedSport.value) return;
    const qs = new URLSearchParams({ deporte: String(selectedSport.value) });
    if (modalidad.value) qs.append("modalidad", modalidad.value);
    const res = await fetch("/api/rankings?" + qs.toString());
    const json = await res.json();
    rankings.value = json.data?.rankings || [];
}

onMounted(async () => {
    await loadSports();
    await loadRankings();
});
</script>

<template>
    <section class="card shadow-sm">
        <h2 class="text-xl font-black text-emerald-600">Rankings</h2>
        <p class="text-sm text-slate-600 mt-2">
            Seleccioná el deporte para ver la tabla y tu posición.
        </p>

        <div class="mt-4">
            <div class="flex items-center gap-3">
                <select
                    v-model="selectedSport"
                    @change="loadRankings"
                    class="form-field w-56"
                >
                    <option
                        v-for="s in sports"
                        :key="s.id"
                        :value="s.slug ?? s.id"
                    >
                        {{ s.name }}
                    </option>
                </select>

                <select
                    v-model="modalidad"
                    @change="loadRankings"
                    class="form-field w-44"
                >
                    <option :value="null">Todas modalidades</option>
                    <option value="individual">Individual</option>
                    <option value="pareja">Pareja</option>
                    <option value="equipo">Equipo</option>
                </select>
            </div>

            <div class="mt-4">
                <table class="w-full text-sm">
                    <thead
                        class="text-xs font-black uppercase text-slate-500 border-b"
                    >
                        <tr>
                            <th class="p-2">Pos</th>
                            <th>Jugador</th>
                            <th>Puntos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(r, i) in rankings"
                            :key="r.user_id ?? i"
                            :class="{
                                'bg-emerald-50':
                                    r.user_id === currentUserId ||
                                    (r.member_names &&
                                        currentUserName &&
                                        r.member_names.includes(
                                            currentUserName,
                                        )),
                            }"
                        >
                            <td class="p-2">{{ i + 1 }}</td>
                            <td class="p-2">
                                {{
                                    r.team_name ??
                                    r.member_names ??
                                    r.name ??
                                    r.user_name ??
                                    "—"
                                }}
                            </td>
                            <td class="p-2">{{ r.points ?? "-" }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</template>
