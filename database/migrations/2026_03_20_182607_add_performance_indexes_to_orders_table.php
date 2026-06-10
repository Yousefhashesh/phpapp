<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            // Most common filter combinations
            $table->index(['approval_status', 'id'], 'orders_approval_status_id_idx');
            $table->index(['approval_status', 'status', 'id'], 'orders_approval_status_status_id_idx');
            $table->index(['approval_status', 'governorate_id', 'id'], 'orders_approval_gov_id_idx');
            $table->index(['approval_status', 'shipper_user_id', 'id'], 'orders_approval_shipper_id_idx');
            $table->index(['approval_status', 'client_user_id', 'id'], 'orders_approval_client_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_approval_status_id_idx');
            $table->dropIndex('orders_approval_status_status_id_idx');
            $table->dropIndex('orders_approval_gov_id_idx');
            $table->dropIndex('orders_approval_shipper_id_idx');
            $table->dropIndex('orders_approval_client_id_idx');
        });
    }
};
