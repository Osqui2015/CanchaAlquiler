<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ExpireReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:expire';
    protected $description = 'Exprira las reservas que ya pasaron su fecha de inicio y siguen pendientes de pago';

    public function handle()
    {
        $count = \App\Models\Reservation::where('status', \App\Models\Reservation::STATUS_PENDIENTE_PAGO)
            ->where('start_at', '<', now())
            ->update(['status' => \App\Models\Reservation::STATUS_EXPIRADA]);

        $this->info("Se han expirado {$count} reservas.");
    }
}
