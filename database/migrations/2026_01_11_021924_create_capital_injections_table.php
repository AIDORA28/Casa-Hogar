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
        Schema::create('capital_injections', function (Blueprint $table) {
            $table->id();
            $table->date('injection_date');
            $table->decimal('amount', 10, 2);
            $table->string('reason'); // "Donación externa", "Aporte administración"
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            
            $table->index('injection_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capital_injections');
    }
};
