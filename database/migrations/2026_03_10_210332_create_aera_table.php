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
        Schema::create('governorates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('follow_up_hours')->default(0);

            $table->foreignId('default_shipper_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->foreignId('governorate_id')
                ->constrained('governorates')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['governorate_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('governorates');
        Schema::dropIfExists('cities');
    }
};
