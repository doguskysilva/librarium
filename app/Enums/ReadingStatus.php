<?php

namespace App\Enums;

enum ReadingStatus: string
{
    case Queued = 'queued';
    case Reading = 'reading';
    case Paused = 'paused';
    case Completed = 'completed';
    case Skipped = 'skipped';
    case Abandoned = 'abandoned';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Skipped, self::Abandoned => true,
            default => false,
        };
    }
}
