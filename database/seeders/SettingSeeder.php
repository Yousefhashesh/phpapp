<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $groups = Setting::getDefaultsByGroup();

        foreach ($groups as $group => $settings) {
            foreach ($settings as $key => $value) {
                Setting::query()->firstOrCreate(
                    ['key' => $key],
                    [
                        'group' => $group,
                        'value' => $value,
                    ]
                );
            }
        }
    }
}
