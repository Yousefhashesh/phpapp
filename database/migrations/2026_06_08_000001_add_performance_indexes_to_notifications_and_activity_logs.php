<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_notifiable_read_index');
            $table->index('created_at', 'notifications_created_at_index');
        });

        foreach (Setting::getDefaultsByGroup()['whatsapp'] ?? [] as $key => $value) {
            Setting::query()->firstOrCreate(
                ['key' => $key],
                ['group' => 'whatsapp', 'value' => $value]
            );
        }
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_notifiable_read_index');
            $table->dropIndex('notifications_created_at_index');
        });
    }
};
