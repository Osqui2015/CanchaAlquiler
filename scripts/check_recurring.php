<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$count = \App\Models\RecurringReservation::where('client_name', 'Usuario Padel Test')->count();
echo "recurring_count:" . $count . PHP_EOL;
