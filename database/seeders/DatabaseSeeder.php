<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ExpenseAuthorizationSeeder::class,
            SettingSeeder::class,
        ]);

        $admin = User::query()->firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'System Admin',
                'phone' => null,
                'password' => Hash::make('12345678'),
                'is_blocked' => false,
            ]
        );

        if (! $admin->hasRole('super-admin')) {
            $admin->assignRole('super-admin');
        }

        $this->call([
            FakeDataSeeder::class,
        ]);
    }
}
