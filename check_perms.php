<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::find(1);
if (!$user) { echo "User 1 not found\n"; exit; }

echo "User: " . $user->name . "\n";
echo "Roles: " . $user->getRoleNames()->implode(', ') . "\n";
echo "Permissions Count: " . $user->getAllPermissions()->count() . "\n";

$perms = $user->getAllPermissions()->pluck('name')->toArray();
echo "First 10 Permissions: " . implode(', ', array_slice($perms, 0, 10)) . "\n";

$tokens = \DB::table('personal_access_tokens')->where('tokenable_id', 1)->count();
echo "Active Tokens for User 1: " . $tokens . "\n";
