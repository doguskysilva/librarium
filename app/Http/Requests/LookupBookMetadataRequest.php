<?php

namespace App\Http\Requests;

use App\Rules\ValidIsbn;
use App\Support\Isbn;
use Illuminate\Foundation\Http\FormRequest;

class LookupBookMetadataRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'isbn' => Isbn::normalizeInput($this->input('isbn')),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'isbn' => ['required', 'string', 'regex:/^(?:[0-9]{10}|[0-9]{13})$/', new ValidIsbn],
        ];
    }
}
