<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
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
            'office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'evidence' => 'nullable|array|max:5',
            'evidence.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'evidence_captions' => 'nullable|array',
            'evidence_captions.*' => 'nullable|string|max:255',
            'category_other' => 'nullable|string|max:100',
            'current_process_other' => 'nullable|string|max:100',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'evidence.max' => 'You can attach up to 5 evidence files.',
        ];
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        throw new HttpResponseException(
            redirect()
                ->back()
                ->withErrors($validator)
                ->withInput()
                ->with('current_step', (int) $this->input('current_step', 1))
        );
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

            if ($this->filled('office_id')) {
                $user = $this->user();

                if (! $user || ! $user->representsActiveOffice($this->input('office_id'))) {
                    $validator->errors()->add('office_id', 'Please choose an active office that you represent.');
                }
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
