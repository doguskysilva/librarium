<?php

namespace App\Http\Requests;

use App\Enums\ReadingStatus;
use App\Models\Reading;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateReadingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string'],
            'private_notes' => ['nullable', 'string'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $reading = $this->route('reading');

            if ($this->filled('rating') && $reading instanceof Reading && $reading->status !== ReadingStatus::Completed) {
                $validator->errors()->add('rating', 'A rating is only allowed for completed readings.');
            }
        }];
    }
}
