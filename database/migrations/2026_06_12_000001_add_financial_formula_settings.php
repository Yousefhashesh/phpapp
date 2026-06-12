<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (Setting::getDefaultsByGroup()['financial_formulas'] ?? [] as $key => $value) {
            if (DB::table('settings')->where('key', $key)->exists()) {
                continue;
            }

            DB::table('settings')->insert([
                'group' => 'financial_formulas',
                'key' => $key,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'financial_formulas')
            ->whereIn('key', array_keys(Setting::getDefaultsByGroup()['financial_formulas'] ?? []))
            ->delete();
    }
};
