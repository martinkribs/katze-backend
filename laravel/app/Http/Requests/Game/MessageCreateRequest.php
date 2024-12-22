<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class MessageCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $game = $this->route('game');
        return $this->user()->can('participate', $game);
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:1000'],
            'is_night_chat' => ['required', 'boolean'],
        ];
    }
}
