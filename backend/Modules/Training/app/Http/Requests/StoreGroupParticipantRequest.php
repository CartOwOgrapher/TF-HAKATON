<?php

namespace Modules\Training\Http\Requests;

use Modules\Core\Abstracts\Http\Requests\BaseFormRequest;

class StoreGroupParticipantRequest extends BaseFormRequest
{
   public function rules(): array
{
    $groupId = $this->route('training_group');
    
    if ($groupId instanceof \Modules\Training\Models\TrainingGroup) {
        $groupId = $groupId->id;
    }

    return [
        'employee_id' => [
            'required',
            'exists:employees,id',
            \Illuminate\Validation\Rule::unique('group_participants')
                ->where('training_group_id', $groupId),
        ],
    ];
}

    public function messages(): array
    {
        return [
            'employee_id.unique' => 'Этот сотрудник уже добавлен в группу.',
        ];
    }
}
