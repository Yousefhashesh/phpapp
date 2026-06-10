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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('order_count')->default(0);
            $table->timestamps();
        });
        Schema::create('plan_prices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plan_id')
                ->constrained('plans')
                ->cascadeOnDelete();

            $table->foreignId('governorate_id')
                ->constrained('governorates')
                ->cascadeOnDelete();

            $table->decimal('price', 10, 2)->nullable();

            $table->timestamps();

            $table->unique(['plan_id', 'governorate_id']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->foreign('plan_id')
                ->references('id')
                ->on('plans')
                ->nullOnDelete();

            $table->foreign('shipping_content_id')
                ->references('id')
                ->on('content')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_prices');

        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropForeign(['shipping_content_id']);
        });

        Schema::dropIfExists('plans');
    }
};
