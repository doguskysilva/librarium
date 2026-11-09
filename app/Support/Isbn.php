<?php

namespace App\Support;

use Illuminate\Support\Str;

final class Isbn
{
    public static function normalizeInput(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $normalized = self::normalize($value);

        return self::toIsbn13($normalized) ?? $normalized;
    }

    public static function normalize(string $isbn): string
    {
        return Str::of($isbn)
            ->replaceMatches('/[\s-]+/', '')
            ->upper()
            ->toString();
    }

    public static function toIsbn13(string $isbn): ?string
    {
        $isbn = self::normalize($isbn);

        if (preg_match('/^\d{9}[\dX]$/', $isbn) === 1) {
            $sum = 0;

            foreach (str_split($isbn) as $index => $digit) {
                $sum += ($digit === 'X' ? 10 : (int) $digit) * (10 - $index);
            }

            if ($sum % 11 !== 0) {
                return null;
            }

            $prefix = '978'.substr($isbn, 0, 9);

            return $prefix.self::isbn13CheckDigit($prefix);
        }

        if (
            preg_match('/^(?:978|979)\d{10}$/', $isbn) !== 1
            || (int) $isbn[12] !== self::isbn13CheckDigit(substr($isbn, 0, 12))
        ) {
            return null;
        }

        return $isbn;
    }

    /**
     * @return list<string>
     */
    public static function equivalents(string $isbn): array
    {
        $normalized = self::normalize($isbn);
        $isbn13 = self::toIsbn13($normalized);

        if ($isbn13 === null) {
            return $normalized === '' ? [] : [$normalized];
        }

        if (! str_starts_with($isbn13, '978')) {
            return [$isbn13];
        }

        $isbn10 = substr($isbn13, 3, 9);
        $sum = 0;

        foreach (str_split($isbn10) as $index => $digit) {
            $sum += (int) $digit * (10 - $index);
        }

        $check = (11 - ($sum % 11)) % 11;

        return [$isbn13, $isbn10.($check === 10 ? 'X' : (string) $check)];
    }

    private static function isbn13CheckDigit(string $firstTwelveDigits): int
    {
        $sum = 0;

        foreach (str_split($firstTwelveDigits) as $index => $digit) {
            $sum += (int) $digit * ($index % 2 === 0 ? 1 : 3);
        }

        return (10 - ($sum % 10)) % 10;
    }
}
