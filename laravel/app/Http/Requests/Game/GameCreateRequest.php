<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class GameCreateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_private' => ['boolean'],
            'timezone' => ['required', 'string', 'max:50', 'timezone'],
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
            'name.required' => 'A game name is required',
            'name.max' => 'Game name cannot be longer than 255 characters',
            'timezone.required' => 'A timezone is required',
            'timezone.timezone' => 'Please provide a valid timezone',
        ];
    }

    /**
     * Get the validated game data.
     *
     * @return array<string, mixed>
     */
    public function getGameData(): array
    {
        return [
            'name' => $this->input('name'),
            'description' => $this->input('description'),
            'is_private' => $this->boolean('is_private', false),
            'timezone' => $this->input('timezone'),
        ];
    }
}
