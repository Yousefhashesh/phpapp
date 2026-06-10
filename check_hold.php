<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\Order;
use Illuminate\Support\Facades\DB;

$res = Order::query()->where('status', 'HOLD')->select('approval_status', DB::raw('count(*) as count'))->groupBy('approval_status')->get();
foreach($res as $s) {
    echo $s->approval_status . ": " . $s->count . "\n";
}
