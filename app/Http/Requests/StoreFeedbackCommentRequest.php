<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreFeedbackCommentRequest extends FormRequest
{
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
            'body' => 'required|string|min:12|max:1000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'Please add a supporting experience before posting.',
            'body.min' => 'Supporting experiences should include enough detail to be useful.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $value = strtolower(trim((string) $this->input('body')));

            if (preg_match('/\b(test|sample|none|n\/a)\b/', $value) === 1) {
                $validator->errors()->add('body', 'Please share a real supporting experience instead of placeholder text.');
            }
        });
    }
}
