<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Complex;
use App\Models\Court;
use App\Models\Reservation;
use App\Services\AvailabilitySearchService;
use Carbon\Carbon;

// Mock/Find a court
$court = Court::first();
if (!$court) {
    echo "No court found\n";
    exit;
}

$service = app(AvailabilitySearchService::class);

$date = Carbon::now()->addDay()->toDateString();
echo "Testing for date: $date\n";

// Ensure we have a 120 min reservation
$startAt = Carbon::parse($date . ' 09:00:00');
$endAt = $startAt->copy()->addMinutes(120);

// Create reservation
Reservation::create([
    'code' => 'TEST-' . rand(1000, 9999),
    'complex_id' => $court->complex_id,
    'court_id' => $court->id,
    'sport_id' => $court->sport_id,
    'start_at' => $startAt,
    'end_at' => $endAt,
    'status' => Reservation::STATUS_CONFIRMADA,
    'total_amount' => 1000,
    'deposit_amount' => 500,
    'currency' => 'ARS',
    'client_user_id' => 1,
]);

$result = $service->searchForComplex($court->complex, [
    'date' => $date,
    'start_time' => '00:00',
    'sport_id' => $court->sport_id,
]);

echo "Slots for $date (Reservation 09:00-11:00):\n";
foreach ($result['courts'] as $c) {
    if ($c['id'] == $court->id) {
        foreach ($c['available_slots'] as $slot) {
            echo " - {$slot['start_time']} to {$slot['end_time']}\n";
        }
    }
}
