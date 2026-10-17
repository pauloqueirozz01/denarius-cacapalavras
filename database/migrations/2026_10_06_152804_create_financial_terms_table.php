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
        Schema::create('financial_terms', function (Blueprint $table) {
            $table->id();
            $table->string('term', 80);
            $table->string('normalized_term', 24)->unique();
            $table->string('description', 500);
            $table->string('difficulty', 16)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_terms');
    }
};
