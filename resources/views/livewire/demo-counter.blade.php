<div>
    <p class="mb-4 text-sm text-slate-600">Componente Livewire 4 funcionando</p>

    <div class="mb-4 text-4xl font-bold text-slate-900">{{ $count }}</div>

    <div class="flex flex-wrap gap-2">
        <button type="button" wire:click="decrement" class="btn-secondary">
            -1
        </button>

        <button type="button" wire:click="increment" class="btn-primary">
            +1
        </button>

        <button type="button" wire:click="resetCounter" class="btn-secondary">
            Reset
        </button>
    </div>
</div>
