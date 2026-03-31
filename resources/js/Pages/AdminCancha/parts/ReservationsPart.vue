<script setup lang="ts">
const props = defineProps();
const { complexes, selectedDate, activeTab, ctx } = props as any;

function courtsForComplex(complex: any) {
    return (complex.courts || []).filter(
        (c: any) =>
            ctx.selectedCourtId === "all" ||
            c.id === Number(ctx.selectedCourtId),
    );
}

function dailyReservationsForCourt(complex: any, court: any) {
    return (complex.daily_reservations || []).filter(
        (r: any) => r.court?.name === court.name,
    );
}

function availableSlotsForCourt(complex: any, courtId: any) {
    return (
        complex.availability?.courts?.find((c: any) => c.id === courtId)
            ?.available_slots ?? []
    );
}
</script>

<template>
    <div v-if="activeTab === 'reservas'" class="space-y-6">
        <section class="card shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1
                        class="text-2xl font-black text-emerald-600 dark:text-emerald-400"
                    >
                        Panel de Control
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Visualización rápida de ocupación y reservas.
                    </p>
                </div>
                <form
                    class="flex items-center gap-2"
                    @submit.prevent="ctx.submitDateFilter()"
                >
                    <input
                        v-model="ctx.dateFilter.date"
                        type="date"
                        class="form-field"
                    />
                    <button class="btn-primary">Ir al día</button>
                </form>
            </div>
        </section>

        <div
            v-for="complex in complexes"
            :key="complex.id"
            class="grid gap-6 lg:grid-cols-12"
        >
            <!-- Calendario -->
            <div class="lg:col-span-5 card shadow-sm">
                <h3
                    class="font-black text-emerald-600 dark:text-emerald-400 mb-4 uppercase tracking-tighter italic"
                >
                    Calendario Mensual ({{ ctx.monthName }})
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
                        v-for="(date, i) in ctx.calendarDays"
                        :key="i"
                        class="aspect-square flex items-center justify-center rounded-lg text-sm border transition-all relative"
                        :class="[
                            !date
                                ? 'border-transparent'
                                : 'border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 cursor-pointer hover:border-emerald-500',
                            date && date === selectedDate
                                ? 'ring-2 ring-emerald-400 bg-emerald-500/10 dark:bg-emerald-400/10'
                                : '',
                        ]"
                        @click="
                            date
                                ? ((ctx.dateFilter.date = date),
                                  ctx.submitDateFilter())
                                : null
                        "
                    >
                        <span
                            v-if="date"
                            :class="
                                date === selectedDate
                                    ? 'font-black text-emerald-600 dark:text-emerald-400'
                                    : 'text-slate-500 dark:text-slate-400'
                            "
                            >{{ parseInt(date.split("-")[2]) }}</span
                        >
                        <div
                            v-if="
                                date &&
                                complex.monthly_reserved_dates.includes(date)
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
                            Reservas del {{ selectedDate }}
                        </h3>

                        <select
                            v-model="ctx.selectedCourtId"
                            class="text-[10px] font-black uppercase text-emerald-600 dark:text-emerald-400 bg-white dark:bg-slate-900 border border-emerald-100 dark:border-emerald-800/40 rounded-lg px-2 py-1 focus:ring-2 focus:ring-emerald-500/20 tracking-tighter outline-none cursor-pointer hover:border-emerald-400 shadow-sm transition-all italic"
                        >
                            <option value="all">Todas las canchas</option>
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
                        @click="ctx.openCalendarModal(complex.id, selectedDate)"
                        class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline"
                    >
                        Ver detalle de horarios
                    </button>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="court in courtsForComplex(complex)"
                        :key="court.id"
                        class="p-4 rounded-xl border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900/60 shadow-sm transition-all hover:shadow-md"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <span
                                class="text-xs font-black uppercase text-emerald-600 dark:text-emerald-500 tracking-widest"
                                >{{ court.name }}</span
                            >
                            <span
                                class="text-[10px] text-slate-400 dark:text-slate-500 font-bold italic"
                                >{{
                                    ctx.formatMoney(
                                        Number(
                                            (court as any).price_60_min ||
                                                court.base_price,
                                        ),
                                    )
                                }}/60min</span
                            >
                        </div>
                        <div class="space-y-1">
                            <div
                                v-for="res in dailyReservationsForCourt(
                                    complex,
                                    court,
                                )"
                                :key="res.id"
                                @click="ctx.openReservationDetails(res)"
                                class="flex items-center justify-between p-2 rounded-lg text-xs border cursor-pointer transition-colors"
                                :class="
                                    res.is_recurring
                                        ? 'bg-sky-50 dark:bg-sky-900/20 border-sky-100 dark:border-sky-800/30 hover:bg-sky-100 dark:hover:bg-sky-800/40'
                                        : 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-100 dark:border-emerald-800/30 hover:bg-emerald-100 dark:hover:bg-emerald-800/40'
                                "
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        class="font-black"
                                        :class="
                                            res.is_recurring
                                                ? 'text-sky-900 dark:text-sky-300'
                                                : 'text-emerald-900 dark:text-emerald-300'
                                        "
                                        >{{
                                            (res.start_at || "").substring(
                                                11,
                                                16,
                                            )
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
                                            res.client_name || res.client?.name
                                        }}</span
                                    >
                                </div>
                                <div class="flex items-center gap-2">
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
                                            ctx.cancelReservation(
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

                            <div
                                v-for="slot in availableSlotsForCourt(
                                    complex,
                                    court.id,
                                )"
                                :key="slot.start_time"
                                class="flex items-center justify-between p-2 rounded-lg bg-white dark:bg-slate-800/20 text-xs border border-slate-100 dark:border-slate-800 border-dashed hover:border-sky-400 hover:bg-sky-50 dark:hover:bg-sky-900/10 transition-all cursor-pointer group"
                                @click="
                                    ctx.startFastReservation(
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
                                    dailyReservationsForCourt(complex, court)
                                        .length === 0 &&
                                    (availableSlotsForCourt(complex, court.id)
                                        .length ?? 0) === 0
                                "
                                class="text-[10px] italic text-slate-400 text-center py-2"
                            >
                                No hay disponibilidad ni reservas para hoy.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
