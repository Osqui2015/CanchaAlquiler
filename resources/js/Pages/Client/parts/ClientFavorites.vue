<script setup lang="ts">
import { ref, onMounted } from "vue";

const favorites = ref<any[]>([]);

// Try to fetch favorites from API if available
async function loadFavorites() {
    try {
        const res = await fetch("/api/client/favorites", {
            credentials: "same-origin",
            headers: { "X-Requested-With": "XMLHttpRequest" },
        });
        if (!res.ok) return;
        const json = await res.json();
        favorites.value = json.data?.favorites || [];
    } catch (e) {
        // ignore
    }
}

async function toggleFavorite(complexId: number) {
    try {
        const res = await fetch("/api/client/favorites", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify({ complex_id: complexId }),
        });
        if (res.ok) {
            await loadFavorites();
        }
    } catch (e) {
        // ignore
    }
}

onMounted(() => {
    loadFavorites();
});
</script>

<template>
    <section class="card shadow-sm">
        <h2 class="text-xl font-black text-emerald-600">Favoritos</h2>
        <p class="text-sm text-slate-600 mt-2">Tus complejos favoritos.</p>

        <div class="mt-4">
            <div
                v-if="favorites.length === 0"
                class="p-4 rounded border border-dashed text-slate-500"
            >
                No tenés favoritos todavía. Buscá complejos y marcá la estrella
                para agregarlos.
            </div>

            <ul v-else class="mt-2 space-y-2">
                <li
                    v-for="f in favorites"
                    :key="f.id"
                    class="p-2 border rounded flex items-center justify-between"
                >
                    <div>
                        <div class="font-bold">{{ f.name }}</div>
                        <div class="text-sm text-slate-500">
                            {{ f.city?.name ?? "" }}
                        </div>
                    </div>
                    <div>
                        <button
                            @click.prevent="toggleFavorite(f.id)"
                            class="text-sm text-rose-600"
                        >
                            Quitar
                        </button>
                    </div>
                </li>
            </ul>
        </div>
    </section>
</template>
