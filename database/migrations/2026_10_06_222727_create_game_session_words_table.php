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
        Schema::create('game_session_words', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_term_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_term', 80);
            $table->string('normalized_term', 24);
            $table->unsignedSmallInteger('start_row');
            $table->unsignedSmallInteger('start_column');
            $table->unsignedSmallInteger('end_row');
            $table->unsignedSmallInteger('end_column');
            $table->string('direction', 24);
            $table->boolean('is_found')->default(false);
            $table->timestamp('found_at')->nullable();
            $table->timestamps();

            $table->unique(['game_session_id', 'normalized_term']);
            $table->index(['game_session_id', 'is_found']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_session_words');
    }
};
