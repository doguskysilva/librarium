<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TransitionReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'date_precision' => ['nullable', 'in:exact,year,unknown'],
            'date' => ['nullable', 'date'],
            'year' => ['nullable', 'integer', 'min:1000', 'max:'.now()->year],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $requiresDate = $this->routeIs(
                'readings.start',
                'readings.complete',
                'readings.skip',
                'readings.abandon',
            );

            if (! $requiresDate) {
                return;
            }

            $precision = $this->input('date_precision');

            if ($precision === null) {
                $validator->errors()->add('date_precision', 'Choose exact date, year only, or unknown.');

                return;
            }

            if ($precision === 'exact' && ! $this->filled('date')) {
                $validator->errors()->add('date', 'Enter the exact date.');
            }

            if ($precision === 'year' && ! $this->filled('year')) {
                $validator->errors()->add('year', 'Enter the year.');
            }

            if ($precision !== 'exact' && $this->filled('date')) {
                $validator->errors()->add('date', 'An exact date is not allowed with this precision.');
            }

            if ($precision !== 'year' && $this->filled('year')) {
                $validator->errors()->add('year', 'A year-only value is not allowed with this precision.');
            }
        }];
    }

    /** @return array{on: ?string, year: ?int} */
    public function datePayload(): array
    {
        return [
            'on' => $this->input('date_precision') === 'exact' ? $this->input('date') : null,
            'year' => $this->input('date_precision') === 'year' ? $this->integer('year') : null,
        ];
    }
}
