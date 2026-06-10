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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('login_session_id')->nullable()->constrained('login_sessions')->nullOnDelete();
        
            $table->string('event_type')->index();      
            $table->string('entity_type')->index();     
            $table->unsignedBigInteger('entity_id')->nullable()->index();
        
            $table->string('action')->index();          
            $table->string('label')->nullable();        
        
            $table->json('old_values')->nullable();     
            $table->json('new_values')->nullable();     
            $table->json('meta')->nullable();           
        
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
        
            $table->timestamps();
        
            $table->index(['entity_type', 'entity_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};