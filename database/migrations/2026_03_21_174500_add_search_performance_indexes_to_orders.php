<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Index for individual column filters
            $table->index('receiver_name', 'orders_receiver_name_idx');
            $table->index('phone', 'orders_phone_idx');
            
            // Composite index for common status + collection filter patterns
            $table->index(['status', 'is_shipper_collected', 'is_client_settled'], 'orders_status_collection_idx');
            
            // Index for date-based sorting/filtering which is very common
            $table->index('created_at', 'orders_created_at_idx');
        });

        // If using MySQL/MariaDB, add FULLTEXT for name and address search
        try {
            DB::statement('ALTER TABLE orders ADD FULLTEXT orders_search_fulltext (receiver_name, address, phone)');
        } catch (\Exception $e) {
            // Silently fail if not supported or already exists
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_receiver_name_idx');
            $table->dropIndex('orders_phone_idx');
            $table->dropIndex('orders_status_collection_idx');
            $table->dropIndex('orders_created_at_idx');
        });
        
        try {
            DB::statement('ALTER TABLE orders DROP INDEX orders_search_fulltext');
        } catch (\Exception $e) {}
    }
};
