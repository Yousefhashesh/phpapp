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
    Schema::table('shipper_returns', function (Blueprint $table) {
        $table->string('client_tracking_number')->nullable()->after('notes');
        $table->string('internal_tracking_number')->nullable()->after('client_tracking_number');
    });
}

public function down(): void
{
    Schema::table('shipper_returns', function (Blueprint $table) {
        $table->dropColumn(['client_tracking_number', 'internal_tracking_number']);
    });
}
};
