<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('disconnected_hosts', function (Blueprint $table) {
            $table->id();
            $table->string('from');
            $table->string('to');
            $table->timestamps();
            $table->unique(['to', 'from']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('disconnected_hosts');
    }
};
