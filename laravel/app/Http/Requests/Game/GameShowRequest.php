<?php

namespace App\Http\Requests\Game;

use App\Models\Game;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class GameShowRequest extends FormRequest
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
        
        if ($userId === null) {
            return false;
        }

        // Allow access if:
        // 1. Game is public
        // 2. User created the game
        // 3. User is a participant
        return !$game->is_private || 
               $game->created_by === $userId || 
               $game->users()->where('user_id', $userId)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return []; // No validation rules needed for showing game details
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Get the error message for a forbidden response.
     */
    public function forbiddenResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'You do not have permission to view this game'
        ], 403);
    }
}
