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
        Schema::create('book_external_identifiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('book_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('type');
            $table->string('value');
            $table->string('url', 2048)->nullable();
            $table->timestamps();

            $table->unique(['provider', 'type', 'value']);
            $table->index(['book_id', 'provider', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_external_identifiers');
    }
};
