<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\Order;
use Illuminate\Support\Facades\DB;

$statuses = Order::query()->select('status', DB::raw('count(*) as count'))->groupBy('status')->get();
foreach($statuses as $s) {
    echo $s->status . ": " . $s->count . "\n";
}
