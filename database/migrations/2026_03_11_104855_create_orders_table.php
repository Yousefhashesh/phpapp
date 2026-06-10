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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('external_code')->nullable()->index();

            $table->timestamp('registered_at')->useCurrent()->index();
            $table->date('captain_date')->nullable()->index();

            $table->string('receiver_name');
            $table->string('phone', 30);
            $table->string('phone_2', 30)->nullable();
            $table->text('address');

            $table->foreignId('governorate_id')->constrained('governorates')->restrictOnDelete();
            $table->foreignId('city_id')->constrained('cities')->restrictOnDelete();

            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('shipping_fee', 12, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('company_amount', 12, 2)->default(0);
            $table->decimal('cod_amount', 12, 2)->default(0);

            $table->enum('status', [
                'OUT_FOR_DELIVERY',
                'DELIVERED',
                'HOLD',
                'UNDELIVERED',
            ])->default('OUT_FOR_DELIVERY')->index();

            $table->text('latest_status_note')->nullable();
            $table->longText('order_note')->nullable();

            $table->foreignId('shipper_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('client_user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('allow_open')->default(true)->index();

            $table->foreignId('shipping_content_id')->nullable()->constrained('content')->nullOnDelete();

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


            $table->boolean('is_shipper_collected')->default(false)->index();
            $table->date('shipper_collected_at')->nullable()->index();


            $table->boolean('is_client_settled')->default(false)->index();
            $table->date('client_settled_at')->nullable()->index();


            $table->boolean('is_shipper_returned')->default(false)->index();
            $table->date('shipper_returned_at')->nullable()->index();


            $table->boolean('is_client_returned')->default(false)->index();
            $table->date('client_returned_at')->nullable()->index();

            $table->date('shipper_date')->nullable()->index();

            $table->timestamps();
        });

        // shipper collections

        Schema::create('shipper_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipper_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('collection_date')->index();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->integer('number_of_orders')->default(0);
            $table->decimal('shipper_fees', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->enum('status', ['PENDING', 'COMPLETED', 'CANCELLED'])->default('PENDING')->index();

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

        Schema::create('shipper_collection_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipper_collection_id')->constrained('shipper_collections')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            $table->decimal('order_amount', 12, 2)->default(0);
            $table->decimal('shipper_fee', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);

            $table->timestamp('added_at')->useCurrent();
            $table->timestamps();

            $table->unique(['shipper_collection_id', 'order_id']);
            $table->index(['order_id']);
        });
        // returns shipper
        Schema::create('shipper_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipper_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('return_date')->index();
            $table->integer('number_of_orders')->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['PENDING', 'COMPLETED', 'CANCELLED'])->default('PENDING')->index();
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
        Schema::create('shipper_return_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipper_return_id')->constrained('shipper_returns')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->timestamp('added_at')->useCurrent();
            $table->timestamps();

            $table->unique(['shipper_return_id', 'order_id']);
            $table->index(['order_id']);
        });

        // end
        // client settlements

        Schema::create('client_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('settlement_date')->index();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->integer('number_of_orders')->default(0);
            $table->decimal('fees', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->enum('status', ['PENDING', 'COMPLETED', 'CANCELLED'])->default('PENDING')->index();
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
        Schema::create('client_settlement_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_settlement_id')->constrained('client_settlements')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            $table->decimal('order_amount', 12, 2)->default(0);
            $table->decimal('fee', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);

            $table->timestamp('added_at')->useCurrent();
            $table->timestamps();

            $table->unique(['client_settlement_id', 'order_id']);
            $table->index(['order_id']);
        });
        // returns client
        Schema::create('client_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('return_date')->index();
            $table->integer('number_of_orders')->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['PENDING', 'COMPLETED', 'CANCELLED'])->default('PENDING')->index();
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
        Schema::create('client_return_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_return_id')->constrained('client_returns')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->timestamp('added_at')->useCurrent();
            $table->timestamps();

            $table->unique(['client_return_id', 'order_id']);
            $table->index(['order_id']);
        });

        // end

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_return_orders');
        Schema::dropIfExists('client_returns');
        Schema::dropIfExists('client_settlement_orders');
        Schema::dropIfExists('client_settlements');
        Schema::dropIfExists('shipper_return_orders');
        Schema::dropIfExists('shipper_returns');
        Schema::dropIfExists('shipper_collection_orders');
        Schema::dropIfExists('shipper_collections');
        Schema::dropIfExists('orders');

    }
};
