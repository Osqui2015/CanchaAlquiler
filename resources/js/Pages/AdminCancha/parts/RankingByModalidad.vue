<script setup lang="ts">
const props = defineProps();
const { rankings, ctx } = props as any;
</script>

<template>
    <div class="card shadow-sm p-4">
        <h3 class="font-black text-emerald-600 mb-3">Ranking por Modalidad</h3>

        <div
            v-if="!rankings || rankings.length === 0"
            class="text-sm text-slate-500"
        >
            No hay ranking cargado para los filtros seleccionados.
        </div>

        <table v-else class="w-full text-sm text-left">
            <thead class="text-xs font-black uppercase text-slate-500 border-b">
                <tr>
                    <th class="p-2">Pos</th>
                    <th class="p-2">Nombre</th>
                    <th class="p-2">Puntos</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr v-for="(r, idx) in rankings" :key="r.id || idx">
                    <td class="p-2 font-black">{{ (Number(idx) || 0) + 1 }}</td>
                    <td class="p-2">
                        {{ r.team_name || r.name || r.player_name }}
                    </td>
                    <td class="p-2">{{ r.points ?? 0 }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
