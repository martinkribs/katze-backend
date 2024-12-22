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
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'page.min' => 'Die Seitenzahl muss mindestens 1 sein.',
            'per_page.min' => 'Die Anzahl der Nachrichten pro Seite muss mindestens 1 sein.',
            'per_page.max' => 'Die Anzahl der Nachrichten pro Seite darf maximal 100 sein.',
        ];
    }
}
