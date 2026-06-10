<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('has_return')
                ->default(false)
                ->after('is_client_returned')
                ->index();

            $table->date('has_return_at')
                ->nullable()
                ->after('has_return')
                ->index();
        });

        // Backfill for existing rows using current return flags.
        DB::table('orders')
            ->where(function ($query) {
                $query->where('is_client_returned', true)
                    ->orWhere('is_shipper_returned', true);
            })
            ->update([
                'has_return' => true,
                'has_return_at' => DB::raw('COALESCE(client_returned_at, shipper_returned_at)'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['has_return', 'has_return_at']);
        });
    }
};
