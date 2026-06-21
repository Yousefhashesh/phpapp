<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_sessions', function (Blueprint $table): void {
            $table->index(['user_id', 'session_id'], 'login_sessions_user_session_index');
            $table->index(['user_id', 'is_current'], 'login_sessions_user_current_index');
        });
    }

    public function down(): void
    {
        Schema::table('login_sessions', function (Blueprint $table): void {
            $table->dropIndex('login_sessions_user_session_index');
            $table->dropIndex('login_sessions_user_current_index');
        });
    }
};
