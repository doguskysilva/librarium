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
        Schema::create('books', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('isbn', 20)->nullable()->index();
            $table->string('publisher')->nullable();
            $table->string('edition', 100)->nullable();
            $table->smallInteger('publication_year')->nullable();
            $table->string('language', 10)->nullable();
            $table->text('description')->nullable();
            $table->string('cover_path', 2048)->nullable();
            $table->timestamps();

            $table->index('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
