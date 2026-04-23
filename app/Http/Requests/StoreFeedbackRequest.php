<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreFeedbackRequest extends FormRequest
{
    private const BLOCKED_TERMS = [
        'test',
        'sample',
        '123',
        'none',
        'asdf',
        'qwerty',
        'n/a',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|min:5|max:255',
            'category' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'department_other' => 'nullable|string|max:255',
            'description' => 'required|string|min:15',
            'impact' => 'required|string|min:10',
            'frequency' => 'required|string|max:255',
            'current_process' => 'required|string|max:255',
            'affected_users' => 'required|string|max:255',
            'affected_group' => 'required|array|min:1',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'category_other' => 'required_if:category,Other|nullable|string|max:100',
            'current_process_other' => 'required_if:current_process,Other|nullable|string|max:100',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach (['title', 'description', 'impact'] as $field) {
                $value = (string) $this->input($field, '');

                if ($this->hasRepeatedCharacters($value)) {
                    $validator->errors()->add($field, 'Please enter meaningful text without repeated characters.');
                }

                if ($this->containsBlockedTerm($value)) {
                    $validator->errors()->add($field, 'Please avoid placeholder text such as test, sample, 123, or none.');
                }
            }

            if (! $this->hasMeaningfulDescription((string) $this->input('description', ''))) {
                $validator->errors()->add('description', 'Please describe the problem with enough specific details.');
            }
        });
    }

    private function hasRepeatedCharacters(string $value): bool
    {
        return preg_match('/(.)\1{5,}/i', $value) === 1;
    }

    private function containsBlockedTerm(string $value): bool
    {
        $normalized = strtolower(trim($value));

        foreach (self::BLOCKED_TERMS as $term) {
            if ($term === '123' && str_contains($normalized, $term)) {
                return true;
            }

            if (preg_match('/\b'.preg_quote($term, '/').'\b/i', $normalized) === 1) {
                return true;
            }
        }

        return false;
    }

    private function hasMeaningfulDescription(string $value): bool
    {
        preg_match_all('/[a-zA-Z]{4,}/', strtolower($value), $matches);

        return collect($matches[0] ?? [])
            ->reject(fn (string $word): bool => in_array($word, ['this', 'that', 'with', 'from', 'have', 'none'], true))
            ->unique()
            ->count() >= 4;
    }
}
