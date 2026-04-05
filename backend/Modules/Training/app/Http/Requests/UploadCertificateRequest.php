<?php

namespace Modules\Training\Http\Requests;

use Modules\Core\Abstracts\Http\Requests\BaseFormRequest;

class UploadCertificateRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'certificate' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'certificate.required' => 'Файл сертификата обязателен.',
            'certificate.file'     => 'Сертификат должен быть файлом.',
            'certificate.mimes'    => 'Сертификат должен быть в формате PDF.',
            'certificate.max'      => 'Размер файла не должен превышать 5 МБ.',
        ];
    }
}
