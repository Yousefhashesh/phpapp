<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (\App\Models\User::with('roles')->get() as $user) {
    echo "ID: " . $user->id . " | Name: " . $user->name . " | Roles: " . $user->roles->pluck('name')->implode(', ') . "\n";
}
