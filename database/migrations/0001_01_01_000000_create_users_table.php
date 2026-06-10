<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('avatar')->nullable();
            $table->string('username')->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('password');
            $table->boolean('is_blocked')->default(false);
            $table->softDeletes();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('shippers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('address')->nullable();
            $table->foreignId('plan_id')->nullable()->index();
            $table->foreignId('shipping_content_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('login_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('session_id')->nullable()->index();

            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();

            $table->string('device_name')->nullable();     // مثال: Windows Chrome
            $table->string('device_type')->nullable();     // desktop / mobile / tablet
            $table->string('browser')->nullable();         // Chrome / Edge / Safari
            $table->string('platform')->nullable();        // Windows / Android / iOS

            $table->string('country')->nullable();
            $table->string('city')->nullable();

            $table->timestamp('login_at')->useCurrent()->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('logout_at')->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_current')->default(false);

            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_sessions');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('shippers');
        Schema::dropIfExists('users');
    }
};
