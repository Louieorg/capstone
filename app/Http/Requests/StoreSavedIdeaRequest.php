<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSavedIdeaRequest extends FormRequest
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
            'title' => 'required|string|min:5|max:255',
            'description' => 'required|string|min:15|max:5000',
            'category' => 'required|string|max:255',
            'office_id' => ['nullable', 'integer', 'exists:offices,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'This capstone opportunity is missing a title, so it could not be saved.',
            'title.min' => 'The capstone opportunity title is too short to be saved.',
            'title.max' => 'The capstone opportunity title is too long to be saved.',
            'description.required' => 'This capstone opportunity is missing a description, so it could not be saved.',
            'description.min' => 'The capstone opportunity description is too short to be saved.',
            'description.max' => 'The capstone opportunity description is too long to be saved.',
            'category.required' => 'This capstone opportunity is missing a category, so it could not be saved.',
            'category.max' => 'The capstone opportunity category is too long to be saved.',
        ];
    }
}
