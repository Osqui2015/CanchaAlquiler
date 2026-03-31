<script setup lang="ts">
import type { Client, ClientsCtx } from "../types";

const props = defineProps<{
    clients: Client[];
    activeTab: string;
    ctx: ClientsCtx;
}>();
</script>

<template>
    <div v-if="activeTab === 'clientes'" class="space-y-6">
        <section class="card shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2
                    class="text-xl font-black text-emerald-600 dark:text-emerald-400 font-black uppercase italic tracking-tighter"
                >
                    Gestión de Clientes
                </h2>
                <button @click="ctx.openNewClientModal()" class="btn-primary">
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
                            <td class="p-4 text-slate-600 dark:text-slate-400">
                                {{ client.email }}
                            </td>
                            <td class="p-4 text-slate-500 dark:text-slate-400">
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
                                    @click="ctx.editClient(client)"
                                    class="text-sky-400 hover:underline font-bold"
                                >
                                    EDITAR
                                </button>
                                <button
                                    v-if="client.status === 'activo'"
                                    @click="ctx.disableClient(client)"
                                    class="text-rose-500 hover:underline font-bold italic"
                                >
                                    SUSPENDER
                                </button>
                                <button
                                    v-else
                                    @click="ctx.enableClient(client)"
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
</template>
