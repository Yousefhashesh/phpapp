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
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
        
            $table->string('code')->unique()->nullable();
        
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('expense_categories')
                ->nullOnDelete();
        
            $table->decimal('amount', 12, 2);
            $table->date('expense_date')->index();
        
            $table->string('title');
            $table->text('notes')->nullable();
        
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'PAID', 'CANCELLED'])
                ->default('PENDING')
                ->index();
        
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
        
            $table->timestamps();
            $table->softDeletes();
        
            $table->index(['expense_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
