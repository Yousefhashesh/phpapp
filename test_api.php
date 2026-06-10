<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Orders\OrderController;
use Illuminate\Support\Facades\Auth;

$user = User::query()->where('id', 1)->first(); // Admin check
Auth::login($user);

$request = Request::create('/orders', 'GET', [
    'status' => 'HOLD,OUT_FOR_DELIVERY',
    'approval_status' => 'APPROVED,PENDING',
    'per_page' => 100
]);

$controller = new OrderController();
$response = $controller->index($request);
$data = json_decode($response->getContent(), true);

echo "Total results: " . ($data['total'] ?? 0) . "\n";
$counts = [];
foreach($data['data'] as $item) {
    if (!isset($counts[$item['status']])) $counts[$item['status']] = 0;
    $counts[$item['status']]++;
}
foreach($counts as $k => $v) {
    echo "$k: $v\n";
}
echo "Totals check from response: " . json_encode($data['totals']) . "\n";
