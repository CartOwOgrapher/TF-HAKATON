<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('id');

        return [
            'employee_code' => [
                'required',
                'string',
                'max:50',
                'unique:employees,employee_code,' . $employeeId,
            ],
            'last_name' => ['required','string', 'max:100'],
            'first_name' => ['required','string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:employees,email,' . $employeeId,
            ],
            'company_id' => [
                'required',
                'integer',
                'exists:companies,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Полное имя обязательно.',
            'email.email' => 'Некорректный email.',
            'email.unique' => 'Сотрудник с таким email уже существует.',
            'employee_code.unique' => 'Сотрудник с таким табельным номером уже существует.',
            'company_id.required' => 'Компания обязательна.',
            'company_id.exists' => 'Компания не найдена.',
        ];
    }
}