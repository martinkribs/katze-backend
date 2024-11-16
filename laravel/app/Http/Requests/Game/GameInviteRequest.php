<?php

namespace App\Http\Requests\Game;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GameInviteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Game */
        $game = $this->route('game');
        
        /** @var ?int */
        $userId = Auth::id();
        
        return $userId !== null && $game->created_by === $userId;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        /** @var Game */
        $game = $this->route('game');

        return [
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('game_user_role', 'user_id')->where(function ($query) use ($game) {
                    return $query->where('game_id', $game->id);
                }),
            ],
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
            'user_id.required' => 'A user ID is required',
            'user_id.exists' => 'Selected user does not exist',
            'user_id.unique' => 'User is already in the game',
        ];
    }

    /**
     * Get the user ID to invite.
     */
    public function getUserId(): int
    {
        return (int) $this->input('user_id');
    }
}
