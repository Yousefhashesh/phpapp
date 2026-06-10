<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('group')->nullable()->after('guard_name');  // مجموعة الصلاحية: orders, clients, reports
            $table->string('label')->nullable()->after('group');        // اسم مفهوم للعرض
            $table->string('type')->default('action')->after('label'); // page | button | column | action
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->string('label')->nullable()->after('guard_name');
            $table->boolean('is_active')->default(true)->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['group', 'label', 'type']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['label', 'is_active']);
        });
    }
};
