<?php

namespace Modules\Company\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->route('id');

        return [
            'code' => [
                'required',
                'string',
                'max:10',
                'unique:companies,code,' . $companyId,
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Код компании обязателен.',
            'code.unique' => 'Компания с таким кодом уже существует.',
            'code.max' => 'Код компании не должен превышать 10 символов.',

            'name.required' => 'Название компании обязательно.',
            'name.max' => 'Название компании не должно превышать 255 символов.',
        ];
    }
}