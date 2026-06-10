<?php

use App\Models\Order;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$counts = Order::query()
    ->select('status', 'approval_status', DB::raw('count(*) as count'))
    ->groupBy('status', 'approval_status')
    ->get();

echo json_encode($counts, JSON_PRETTY_PRINT);
