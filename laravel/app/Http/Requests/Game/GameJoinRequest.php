<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class GameJoinRequest extends FormRequest
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
        $game = $this->route('game');
        
        if ($game && $game->is_private) {
            return [
                'token' => ['required_without:join_code', 'string', 'uuid'],
                'join_code' => ['required_without:token', 'string', 'size:8'],
            ];
        }

        return [];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token.required_without' => 'Either an invitation token or join code is required for private games',
            'token.uuid' => 'Invalid invitation token format',
            'join_code.required_without' => 'Either an invitation token or join code is required for private games',
            'join_code.size' => 'Join code must be exactly 8 characters',
        ];
    }

    /**
     * Get the join credentials.
     *
     * @return array<string, string|null>
     */
    public function getJoinCredentials(): array
    {
        return [
            'token' => $this->input('token'),
            'join_code' => $this->input('join_code'),
        ];
    }
}
