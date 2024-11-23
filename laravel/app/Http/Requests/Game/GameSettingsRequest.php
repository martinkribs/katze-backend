<?php

namespace App\Http\Requests\Game;

use App\Models\Game;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class GameSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Game $game */
        $game = $this->route('game');

        /** @var ?int $userId */
        $userId = Auth::id();

        return $userId !== null && $game->created_by === $userId;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'use_default' => ['required', 'boolean'],
            'role_configuration' => ['required_if:use_default,false', 'array'],
            'role_configuration.*' => ['integer', 'min:0'],
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
            'use_default.required' => 'The use_default flag is required',
            'use_default.boolean' => 'The use_default flag must be true or false',
            'role_configuration.required_if' => 'Role configuration is required when not using default settings',
            'role_configuration.array' => 'Role configuration must be an array',
            'role_configuration.*.integer' => 'Role counts must be integers',
            'role_configuration.*.min' => 'Role counts cannot be negative',
        ];
    }

    /**
     * Get the settings data.
     *
     * @return array<string, mixed>
     */
    public function getSettingsData(): array
    {
        return [
            'use_default' => $this->boolean('use_default'),
            'role_configuration' => $this->input('role_configuration', []),
        ];
    }
}
