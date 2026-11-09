<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LookupBookMetadataRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'isbn' => Str::of((string) $this->input('isbn'))
                ->replace([' ', '-'], '')
                ->upper()
                ->toString(),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'isbn' => ['required', 'string', 'regex:/^(?:[0-9]{9}[0-9X]|[0-9]{13})$/'],
        ];
    }
}
