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
        Schema::table('availability_schedules', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->string('status')->default('available');
        });
    }

    public function down(): void
    {
        Schema::table('availability_schedules', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->dropColumn('status');
        });
    }
};
