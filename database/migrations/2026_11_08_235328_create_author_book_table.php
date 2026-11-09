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
        Schema::create('author_book', function (Blueprint $table): void {
            $table->foreignUuid('book_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('author_id')->constrained()->cascadeOnDelete();
            $table->primary(['book_id', 'author_id']);
            $table->index('author_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('author_book');
    }
};
