<?php
// scripts/export_padel.php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \Illuminate\Support\Facades\DB::table('user_rankings')
    ->join('users', 'users.id', '=', 'user_rankings.user_id')
    ->join('sports', 'sports.id', '=', 'user_rankings.sport_id')
    ->where('sports.slug', 'padel')
    ->select('users.id', 'users.name', 'users.email', 'user_rankings.rankid', 'user_rankings.points')
    ->get()
    ->toArray();

file_put_contents(__DIR__ . '/../database/padel_players.json', json_encode($users, JSON_PRETTY_PRINT));

echo "exported: " . count($users) . " users\n";
