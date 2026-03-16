<div>
    <p class="mb-4 text-sm text-slate-600">Componente Livewire 4 funcionando</p>

    <div class="mb-4 text-4xl font-bold text-slate-900">{{ $count }}</div>

    <div class="flex flex-wrap gap-2">
        <button type="button" wire:click="decrement"
            class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            -1
        </button>

        <button type="button" wire:click="increment"
            class="rounded-md bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-500">
            +1
        </button>

        <button type="button" wire:click="resetCounter"
            class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
            Reset
        </button>
    </div>
</div>
