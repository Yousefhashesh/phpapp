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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('code')->unique()->nullable();

            $table->decimal('cost_price', 10, 2);
            $table->decimal('sale_price', 10, 2);

            $table->integer('stock')->default(0);

            $table->boolean('is_active')->default(true);

            $table->text('notes')->nullable();

            $table->timestamps();
        });

        Schema::create('material_requests', function (Blueprint $table) {

            $table->id();

            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();

            $table->enum('delivery_type', [
                'PICKUP',
                'DELIVERY',
            ])->default('DELIVERY');

            $table->boolean('combined_visit')->default(false);

            $table->decimal('materials_total', 10, 2)->default(0);

            $table->decimal('shipping_cost', 10, 2)->default(0);

            $table->enum('status', [
                'PENDING',
                'PROCESSING',
                'COMPLETED',
                'CANCELLED',
            ])->default('PENDING');
            $table->enum('approval_status', [
                'PENDING',
                'APPROVED',
                'REJECTED',
            ])->default('PENDING')->index();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();

            $table->text('approval_note')->nullable();
            $table->timestamps();
        });
        Schema::create('material_request_items', function (Blueprint $table) {

            $table->id();

            $table->foreignId('material_request_id')
                ->constrained('material_requests')
                ->cascadeOnDelete();

            $table->foreignId('material_id')
                ->constrained('materials')
                ->cascadeOnDelete();

            $table->integer('quantity');

            $table->decimal('price', 10, 2);

            $table->decimal('total', 10, 2);

            $table->timestamps();
        });

        Schema::create('pickup_requests', function (Blueprint $table) {

            $table->id();

            $table->foreignId('client_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('shipper_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('pickup_date')->nullable();

            $table->boolean('combined_with_material')->default(false);

            $table->decimal('pickup_cost', 10, 2)->default(0);

            $table->enum('status', [
                'PENDING',
                'ASSIGNED',
                'COMPLETED',
                'CANCELLED',
            ])->default('PENDING');
            $table->enum('approval_status', [
                'PENDING',
                'APPROVED',
                'REJECTED',
            ])->default('PENDING')->index();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();

            $table->text('approval_note')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });

        Schema::create('visits', function (Blueprint $table) {

            $table->id();

            $table->foreignId('shipper_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('client_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('pickup_request_id')
                ->nullable()
                ->constrained('pickup_requests')
                ->nullOnDelete();

            $table->foreignId('material_request_id')
                ->nullable()
                ->constrained('material_requests')
                ->nullOnDelete();

            $table->decimal('visit_cost', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
        Schema::dropIfExists('material_requests');
        Schema::dropIfExists('material_request_items');
        Schema::dropIfExists('pickup_requests');
        Schema::dropIfExists('visits');

    }
};
