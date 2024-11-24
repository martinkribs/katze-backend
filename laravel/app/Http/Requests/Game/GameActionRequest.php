<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Game;
use App\Models\ActionType;

class GameActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Detailed authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action_type_id' => 'required|exists:action_types,id',
            'targets' => 'required|array',
            'targets.*' => 'required|exists:users,id'
        ];
    }

    /**
     * Get the action type from the request.
     */
    public function getActionType(): ActionType
    {
        return ActionType::findOrFail($this->input('action_type_id'));
    }

    /**
     * Get the target users from the request.
     *
     * @return array<int>
     */
    public function getTargets(): array
    {
        return $this->input('targets', []);
    }
}
