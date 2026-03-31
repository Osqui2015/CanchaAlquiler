<script setup lang="ts">
const props = defineProps();
const { catalogs, ctx } = props as any;
</script>

<template>
    <div class="card shadow-sm p-4">
        <div class="flex items-center gap-3 mb-3">
            <h3 class="font-black text-emerald-600">Filtrar Ranking</h3>
        </div>

        <div class="flex gap-2 items-center">
            <select
                v-model="ctx.rankingFilter.sport_id"
                @change="ctx.setSport(ctx.rankingFilter.sport_id)"
                class="form-field"
            >
                <option value="">Todos los deportes</option>
                <option
                    v-for="s in catalogs?.sports || []"
                    :key="s.id"
                    :value="String(s.id)"
                >
                    {{ s.name }}
                </option>
            </select>

            <select
                v-model="ctx.rankingFilter.modalidad_id"
                @change="ctx.setModalidad(ctx.rankingFilter.modalidad_id)"
                class="form-field"
            >
                <option value="">Todas las modalidades</option>
                <option
                    v-for="m in catalogs?.modalidades || []"
                    :key="m.id"
                    :value="String(m.id)"
                >
                    {{ m.name }}
                </option>
            </select>

            <button @click="ctx.loadRankings()" class="btn-primary">
                Cargar
            </button>
        </div>
    </div>
</template>
