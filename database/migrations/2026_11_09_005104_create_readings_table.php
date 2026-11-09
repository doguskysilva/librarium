<?php

use App\Enums\ReadingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('readings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('book_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default(ReadingStatus::Queued->value)->index();
            $table->date('started_on')->nullable();
            $table->smallInteger('started_year')->nullable();
            $table->date('finished_on')->nullable();
            $table->smallInteger('finished_year')->nullable();
            $table->smallInteger('rating')->nullable();
            $table->text('review')->nullable();
            $table->text('private_notes')->nullable();
            $table->timestamps();

        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE readings ADD CONSTRAINT readings_started_precision_check CHECK (started_on IS NULL OR started_year IS NULL)');
            DB::statement('ALTER TABLE readings ADD CONSTRAINT readings_finished_precision_check CHECK (finished_on IS NULL OR finished_year IS NULL)');
            DB::statement("ALTER TABLE readings ADD CONSTRAINT readings_rating_check CHECK (rating IS NULL OR (rating BETWEEN 1 AND 5 AND status = 'completed'))");
            DB::statement("ALTER TABLE readings ADD CONSTRAINT readings_queued_start_check CHECK (status != 'queued' OR (started_on IS NULL AND started_year IS NULL))");
            DB::statement("ALTER TABLE readings ADD CONSTRAINT readings_active_finish_check CHECK (status NOT IN ('queued', 'reading', 'paused') OR (finished_on IS NULL AND finished_year IS NULL))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('readings');
    }
};
