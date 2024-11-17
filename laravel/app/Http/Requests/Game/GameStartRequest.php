<?php

namespace App\Http\Requests\Game;

use App\Models\Game;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class GameStartRequest extends FormRequest
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
            'role_configuration' => ['required', 'array'],
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
            'role_configuration.required' => 'Role configuration is required',
            'role_configuration.array' => 'Role configuration must be an array',
            'role_configuration.*.integer' => 'Role counts must be integers',
            'role_configuration.*.min' => 'Role counts cannot be negative',
        ];
    }

    /**
     * Get the role configuration.
     *
     * @return array<int, int>
     */
    public function getRoleConfiguration(): array
    {
        /** @var array<int, int> */
        return $this->input('role_configuration', []);
    }
}
