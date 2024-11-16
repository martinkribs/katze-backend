<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class GameIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'string', 'in:pending,in_progress,completed'],
            'search' => ['sometimes', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'per_page.integer' => 'Items per page must be a number',
            'per_page.min' => 'Items per page must be at least 1',
            'per_page.max' => 'Items per page cannot exceed 100',
            'page.integer' => 'Page number must be a number',
            'page.min' => 'Page number must be at least 1',
            'status.in' => 'Invalid game status',
        ];
    }

    /**
     * Get the pagination parameters.
     *
     * @return array{per_page: int, page: int}
     */
    public function getPaginationParams(): array
    {
        return [
            'per_page' => (int) $this->input('per_page', 10),
            'page' => (int) $this->input('page', 1),
        ];
    }

    /**
     * Get the filter parameters.
     *
     * @return array{status: ?string, search: ?string}
     */
    public function getFilterParams(): array
    {
        return [
            'status' => $this->input('status'),
            'search' => $this->input('search'),
        ];
    }
}
