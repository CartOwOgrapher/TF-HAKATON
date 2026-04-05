<?php

namespace Modules\Training\Http\Controllers;

use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Training\Http\Requests\UploadCertificateRequest;
use Modules\Training\Models\TrainingGroup;
use Modules\Training\Models\GroupParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Сертификаты участников
 *
 * Загрузка, скачивание и удаление сертификатов об обучении для участников учебной группы.
 * Файлы хранятся на диске `public` по пути `certificates/{training_group_id}/{participant_id}.pdf`.
 */
class CertificateController extends BaseController
{
    /**
     * Загрузить сертификат участника
     *
     * Загружает PDF-сертификат для участника учебной группы.
     * Если сертификат уже существует — он заменяется новым (старый файл удаляется).
     * Файл сохраняется по пути: `certificates/{training_group_id}/{participant_id}.pdf`.
     *
     * @authenticated
     *
     * @urlParam training_group integer required ID учебной группы. Example: 1
     * @urlParam participant integer required ID записи участника группы. Example: 10
     *
     * @bodyParam certificate file required PDF-файл сертификата.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OK",
     *   "data": {
     *     "message": "Сертификат успешно загружен.",
     *     "certificate_path": "certificates/1/10.pdf"
     *   }
     * }
     *
     * @response 404 { "message": "No query results for model [GroupParticipant]." }
     * @response 422 { "message": "The certificate must be a file of type: pdf." }
     */
    public function upload(
        UploadCertificateRequest $request,
        TrainingGroup $trainingGroup,
        GroupParticipant $participant,
    ): JsonResponse {
        if ($participant->certificate_path) {
            Storage::disk('public')->delete($participant->certificate_path);
        }
        $path = $request->file('certificate')->storeAs(
            "certificates/{$trainingGroup->id}",
            "{$participant->id}.pdf",
            'public'
        );

        $participant->update(['certificate_path' => $path]);

        return $this->success([
            'message'          => 'Сертификат успешно загружен.',
            'certificate_path' => $path,
        ]);
    }

    /**
     * Скачать сертификат участника
     *
     * Возвращает PDF-файл сертификата для скачивания.
     * Имя файла при скачивании формируется из полного имени сотрудника.
     *
     * @authenticated
     *
     * @urlParam training_group integer required ID учебной группы. Example: 1
     * @urlParam participant integer required ID записи участника группы. Example: 10
     *
     * @response 200 scenario="Файл найден" {
     *   "Content-Type": "application/pdf",
     *   "Content-Disposition": "attachment; filename=\"certificate_Иванов Иван Иванович.pdf\""
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Сертификат не найден.",
     *   "errors": null
     * }
     */
    public function download(
        TrainingGroup $trainingGroup,
        GroupParticipant $participant,
    ): StreamedResponse|JsonResponse {
        if (!$participant->hasCertificate()) {
            return $this->error('Сертификат не найден.', 404);
        }

        $employeeName = $participant->employee?->full_name ?? 'participant';
        $fileName     = "certificate_{$employeeName}.pdf";

        return Storage::disk('public')->download(
            $participant->certificate_path,
            $fileName,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Удалить сертификат участника
     *
     * Удаляет файл сертификата с диска и обнуляет поле `certificate_path` у участника.
     *
     * @authenticated
     *
     * @urlParam training_group integer required ID учебной группы. Example: 1
     * @urlParam participant integer required ID записи участника группы. Example: 10
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OK",
     *   "data": { "message": "Сертификат удалён." }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Сертификат не найден.",
     *   "errors": null
     * }
     */
    public function destroy(
        TrainingGroup $trainingGroup,
        GroupParticipant $participant,
    ): JsonResponse {
        if (!$participant->certificate_path) {
            return $this->error('Сертификат не найден.', 404);
        }

        $participant->deleteCertificate();

        return $this->success(['message' => 'Сертификат удалён.']);
    }
}