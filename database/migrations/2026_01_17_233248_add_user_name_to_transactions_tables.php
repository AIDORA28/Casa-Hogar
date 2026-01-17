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
        // Agregar user_name a sales
        Schema::table('sales', function (Blueprint $table) {
            $table->string('user_name')->nullable()->after('user_id');
        });

        // Agregar user_name a expenses
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('user_name')->nullable()->after('user_id');
        });

        // Agregar user_name a capital_injections
        Schema::table('capital_injections', function (Blueprint $table) {
            $table->string('user_name')->nullable()->after('user_id');
        });

        // Agregar user_name a daily_closings
        Schema::table('daily_closings', function (Blueprint $table) {
            $table->string('user_name')->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('user_name');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('user_name');
        });

        Schema::table('capital_injections', function (Blueprint $table) {
            $table->dropColumn('user_name');
        });

        Schema::table('daily_closings', function (Blueprint $table) {
            $table->dropColumn('user_name');
        });
    }
};
