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
        Schema::create('refused_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('reason')->unique();
            $table->enum('status', ['OUT_FOR_DELIVERY',
                'DELIVERED',
                'HOLD',
                'UNDELIVERED'])->default('OUT_FOR_DELIVERY');
            $table->boolean('is_active')->default(true);
            
            $table->boolean('is_clear')->default(false);
            $table->boolean('is_return')->default(false);
            $table->boolean('is_edit_amount')->default(false);
            
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refused_reasons');
    }
};
