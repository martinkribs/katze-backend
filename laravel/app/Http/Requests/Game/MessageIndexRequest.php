<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class MessageIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        $game = $this->route('game');
        return $this->user()->can('participate', $game);
    }

    public function rules(): array
    {
        return [
            'night_chat' => ['sometimes', 'boolean'],
        ];
    }
}
