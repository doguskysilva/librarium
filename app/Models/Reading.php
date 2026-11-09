<?php

namespace App\Models;

use App\Enums\ReadingStatus;
use Database\Factories\ReadingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property ReadingStatus $status */
#[Fillable(['started_on', 'started_year', 'finished_on', 'finished_year', 'rating', 'review', 'private_notes'])]
class Reading extends Model
{
    /** @use HasFactory<ReadingFactory> */
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    /** @return BelongsTo<Book, $this> */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function canStart(): bool
    {
        return $this->status === ReadingStatus::Queued;
    }

    public function canPause(): bool
    {
        return $this->status === ReadingStatus::Reading;
    }

    public function canResume(): bool
    {
        return $this->status === ReadingStatus::Paused;
    }

    public function canComplete(): bool
    {
        return in_array($this->status, [ReadingStatus::Reading, ReadingStatus::Paused], true);
    }

    public function canSkip(): bool
    {
        return $this->status === ReadingStatus::Queued;
    }

    public function canAbandon(): bool
    {
        return in_array($this->status, [ReadingStatus::Reading, ReadingStatus::Paused], true);
    }

    /** @param  array{on: ?string, year: ?int}  $date */
    public function start(array $date): void
    {
        $this->ensureTransition($this->canStart());
        $this->status = ReadingStatus::Reading;
        $this->started_on = $date['on'];
        $this->started_year = $date['year'];
        $this->save();
    }

    public function pause(): void
    {
        $this->ensureTransition($this->canPause());
        $this->status = ReadingStatus::Paused;
        $this->save();
    }

    public function resume(): void
    {
        $this->ensureTransition($this->canResume());
        $this->status = ReadingStatus::Reading;
        $this->save();
    }

    /** @param  array{on: ?string, year: ?int}  $date */
    public function complete(array $date): void
    {
        $this->ensureTransition($this->canComplete());
        $this->status = ReadingStatus::Completed;
        $this->finished_on = $date['on'];
        $this->finished_year = $date['year'];
        $this->save();
    }

    /** @param  array{on: ?string, year: ?int}  $date */
    public function skip(array $date): void
    {
        $this->ensureTransition($this->canSkip());
        $this->status = ReadingStatus::Skipped;
        $this->finished_on = $date['on'];
        $this->finished_year = $date['year'];
        $this->save();
    }

    /** @param  array{on: ?string, year: ?int}  $date */
    public function abandon(array $date): void
    {
        $this->ensureTransition($this->canAbandon());
        $this->status = ReadingStatus::Abandoned;
        $this->finished_on = $date['on'];
        $this->finished_year = $date['year'];
        $this->save();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ReadingStatus::class,
            'started_on' => 'date',
            'started_year' => 'integer',
            'finished_on' => 'date',
            'finished_year' => 'integer',
            'rating' => 'integer',
        ];
    }

    private function ensureTransition(bool $allowed): void
    {
        abort_unless($allowed, 422, 'This reading cannot perform that action.');
    }
}
