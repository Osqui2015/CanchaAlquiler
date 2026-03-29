<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Complex;
use App\Models\Court;
use App\Models\Sport;
use App\Models\Reservation;

$email = 'test.user+padel@example.com';
$user = User::where('email', $email)->first();
if (! $user) {
  echo "User not found: {$email}\n";
  exit(1);
}

$complex = Complex::where('slug', 'del-galpon')->first();
if (! $complex) {
  echo "Complex not found: del-galpon\n";
  exit(1);
}

$court = Court::where('complex_id', $complex->id)->where('name', 'Cancha Padel 1')->first();
if (! $court) {
  echo "Court not found: Cancha Padel 1\n";
  exit(1);
}

$sport = Sport::where('slug', 'padel')->first();
if (! $sport) {
  echo "Sport not found: padel\n";
  exit(1);
}

// Compute next Monday at 20:00
$now = Carbon::now();
$nextMonday = $now->copy()->next(Carbon::MONDAY)->setTime(20, 0, 0);

$startAt = $nextMonday;
$endAt = $startAt->copy()->addHours(2);

// Avoid duplicate: check if reservation exists for same client, court and start_at
$exists = Reservation::where('client_user_id', $user->id)
  ->where('court_id', $court->id)
  ->whereDate('start_at', $startAt->toDateString())
  ->whereTime('start_at', $startAt->format('H:i:s'))
  ->exists();

if ($exists) {
  echo "Reservation already exists for {$email} on {$startAt}\n";
  exit(0);
}

$total = $court->base_price ?? 1000;

$reservation = Reservation::create([
  'code' => Str::upper(Str::random(8)),
  'client_user_id' => $user->id,
  'complex_id' => $complex->id,
  'court_id' => $court->id,
  'sport_id' => $sport->id,
  'start_at' => $startAt->toDateTimeString(),
  'end_at' => $endAt->toDateTimeString(),
  'total_amount' => $total,
  'deposit_amount' => 0,
  'currency' => 'ARS',
  'status' => Reservation::STATUS_CONFIRMADA,
  'is_paid' => true,
  'client_name' => $user->name,
  'client_phone' => $user->phone,
]);

echo "Created reservation id={$reservation->id} for {$email} on {$startAt}\n";
