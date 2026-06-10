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
        Schema::table('orders', function (Blueprint $table) {
            $cols = [
                'is_in_shipper_collection',
                'is_in_client_settlement',
                'is_in_shipper_return',
                'is_in_client_return',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_in_shipper_collection')->default(false)->index();
            $table->boolean('is_in_client_settlement')->default(false)->index();
            $table->boolean('is_in_shipper_return')->default(false)->index();
            $table->boolean('is_in_client_return')->default(false)->index();
        });
    }
};
