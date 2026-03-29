<template>
    <AppShell>
        <div class="flex flex-col md:flex-row gap-6 items-start">
            <div class="w-full md:w-64 flex-shrink-0 sticky top-24">
                <ClientSidebar class="rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden" />
            </div>
            <div class="flex-1 min-w-0 w-full">
                <div class="bg-white dark:bg-slate-900/50 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 p-6">
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100 mb-6">Mi Historial</h1>
                    <div v-if="history.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Complejo/Cancha</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Horario</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="reservation in history" :key="reservation.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatDate(reservation.start_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <p class="font-medium text-gray-900">{{ reservation.complex?.name || 'N/A' }}</p>
                                        <p>{{ reservation.court?.name || 'N/A' }} ({{ reservation.court?.sport?.name || 'N/A' }})</p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatTime(reservation.start_at) }} - {{ formatTime(reservation.end_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="[
                                            'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                            reservation.status === 'cancelada' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
                                        ]">
                                            {{ reservation.status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div v-else class="text-center py-12 bg-white rounded-lg shadow">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay historial</h3>
                        <p class="mt-1 text-sm text-gray-500">Aún no has tenido reservas pasadas o canceladas.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppShell>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import AppShell from '@/Components/AppShell.vue';
import ClientSidebar from '@/Components/ClientSidebar.vue';

export default {
    components: {
        Link,
        AppShell,
        ClientSidebar,
    },
    props: {
        history: Array,
    },
    methods: {
        formatDate(datetimeStr) {
            if (!datetimeStr) return '';
            const date = new Date(datetimeStr);
            return date.toLocaleDateString('es-AR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        },
        formatTime(datetimeStr) {
            if (!datetimeStr) return '';
            const date = new Date(datetimeStr);
            return date.toLocaleTimeString('es-AR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
}
</script>
