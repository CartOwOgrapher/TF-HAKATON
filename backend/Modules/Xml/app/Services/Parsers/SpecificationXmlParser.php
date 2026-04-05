<?php

namespace Modules\Xml\Services\Parsers;

use SimpleXMLElement;

/**
 * Парсер XML-файла спецификации (Edu_Specification).
 *
 * Структура источника (Global ERP):
 *   <Edu_Specification>
 *     <id>                — внешний ID в ERP (игнорируется)
 *     <sNumber>           — номер спецификации
 *     <dDate>             — дата (YYYY-MM-DD)
 *     <idOrganization>    — код компании
 *     <idOrganizationHL>  — название компании
 *     <TrainingGroups>
 *       <TrainingGroup>
 *         <dStartDate>         — дата начала
 *         <dEndDate>           — дата окончания
 *         <sStatus>            — статус
 *         <nParticipantsCount> — заявленное кол-во участников
 *         <Edu_Course>         — полная карточка курса (как в Edu_Course XML)
 *           <id>
 *           <sCode>
 *           <sCourseHL>
 *           <sDescription>
 *           <nDurationInDays>
 *           <nPricePerPerson>
 *         </Edu_Course>
 *         <Participants>
 *           <Edu_Participant>  — полная карточка сотрудника (как в Edu_Participant XML)
 *             <id>
 *             <sCode>
 *             <sLastName>
 *             <sMiddleName>
 *             <sFirstName>
 *             <sFIO>
 *             <sEmail>
 *             <idOrganization>
 *             <idOrganizationHL>
 *           </Edu_Participant>
 *         </Participants>
 *       </TrainingGroup>
 *     </TrainingGroups>
 *   </Edu_Specification>
 */
class SpecificationXmlParser
{
    /**
     * Парсит XML-строку и возвращает массив данных по спецификациям.
     *
     * Поддерживает два формата:
     * - одиночный: корневой тег `<Edu_Specification>`
     * - пакетный: корневой тег `<Specifications>` с дочерними `<Edu_Specification>`
     *
     * @param  string  $xmlContent  Сырое содержимое XML.
     * @return array                Массив ассоциативных массивов, каждый из которых содержит:
     *                              - `number` (string)
     *                              - `date` (string|null)
     *                              - `company_code` (string)
     *                              - `company_name` (string)
     *                              - `groups` (array) — массив учебных групп
     *
     * @throws \InvalidArgumentException Если XML невалиден, корневой тег не поддерживается,
     *                                   или нарушена внутренняя структура групп/участников.
     */
    public function parseMultiple(string $xmlContent): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            $errors = array_map(fn($e) => trim($e->message), libxml_get_errors());
            libxml_clear_errors();
            throw new \InvalidArgumentException('XML parse error: ' . implode('; ', $errors));
        }

        $results = [];

        if ($xml->getName() === 'Edu_Specification') {
            $results[] = $this->extractSpecification($xml);

        } elseif ($xml->getName() === 'Specifications') {
            foreach ($xml->children() as $child) {
                if ($child->getName() !== 'Edu_Specification') {
                    continue;
                }
                $results[] = $this->extractSpecification($child);
            }

        } else {
            throw new \InvalidArgumentException(
                "Неподдерживаемый корневой тег: <{$xml->getName()}>. "
                . "Ожидались: Edu_Specification или Specifications."
            );
        }

        return $results;
    }

    /**
     * Извлекает данные одной спецификации из XML-элемента.
     *
     * @param  SimpleXMLElement  $xml  XML-элемент `<Edu_Specification>`.
     * @return array                   Ассоциативный массив с полями спецификации и вложенным массивом групп.
     */
    private function extractSpecification(SimpleXMLElement $xml): array
    {
        $groups = [];

        foreach ($xml->TrainingGroups->TrainingGroup ?? [] as $groupXml) {
            $groups[] = $this->extractGroup($groupXml);
        }

        return [
            'number'       => (string) $xml->sNumber,
            'date'         => (string) $xml->dDate ?: null,
            'company_code' => (string) $xml->idOrganization,
            'company_name' => (string) $xml->idOrganizationHL,
            'groups'       => $groups,
        ];
    }

    /**
     * Извлекает данные одной учебной группы из XML-элемента.
     *
     * Валидирует соответствие `nParticipantsCount` и фактического числа участников.
     *
     * @param  SimpleXMLElement  $xml  XML-элемент `<TrainingGroup>`.
     * @return array                   Ассоциативный массив:
     *                                 - `course` (array)
     *                                 - `start_date` (string|null)
     *                                 - `end_date` (string|null)
     *                                 - `status` (string)
     *                                 - `participants_count` (int) — заявленное кол-во
     *                                 - `participants` (array)
     *
     * @throws \InvalidArgumentException При проблемах со структурой курса или несоответствии кол-ва участников.
     */
    private function extractGroup(SimpleXMLElement $xml): array
    {
        $declaredCount = (int) $xml->nParticipantsCount;
        $participants  = $this->extractParticipants($xml, $declaredCount);

        return [
            'course'             => $this->extractCourse($xml->Edu_Course),
            'start_date'         => (string) $xml->dStartDate ?: null,
            'end_date'           => (string) $xml->dEndDate   ?: null,
            'status'             => (string) $xml->sStatus    ?: 'planned',
            'participants_count' => $declaredCount,
            'participants'       => $participants,
        ];
    }

    /**
     * Извлекает данные курса из вложенного блока `<Edu_Course>` группы.
     *
     * @param  mixed  $courseXml  Ожидается SimpleXMLElement; при отсутствии тега придёт `null` или пустой объект.
     * @return array              Ассоциативный массив полей курса.
     *
     * @throws \InvalidArgumentException Если блок `<Edu_Course>` отсутствует или `<sCode>` пуст.
     */
    private function extractCourse(mixed $courseXml): array
    {
        if (! $courseXml || ! ($courseXml instanceof SimpleXMLElement)) {
            throw new \InvalidArgumentException(
                "В <TrainingGroup> отсутствует блок <Edu_Course>."
            );
        }

        $code = trim((string) $courseXml->sCode);

        if ($code === '') {
            throw new \InvalidArgumentException(
                "Блок <Edu_Course> содержит пустой <sCode>."
            );
        }

        $price = (string) $courseXml->nPricePerPerson;

        return [
            'code'          => $code,
            'title'         => (string) $courseXml->sCourseHL,
            'description'   => (string) $courseXml->sDescription ?: null,
            'duration_days' => (int)    $courseXml->nDurationInDays,
            'price'         => $price !== '' ? number_format((float) $price, 2, '.', '') : null,
        ];
    }

    /**
     * Извлекает список участников из блока `<Participants>` группы.
     *
     * Проверяет:
     * - Отсутствие пустого `<sCode>` у каждого участника.
     * - Соответствие фактического кол-ва участников `nParticipantsCount`
     *   (если `nParticipantsCount` > 0).
     *
     * @param  SimpleXMLElement  $groupXml       XML-элемент `<TrainingGroup>`.
     * @param  int               $declaredCount  Заявленное кол-во участников (`nParticipantsCount`).
     * @return array                             Массив ассоциативных массивов участников:
     *                                           - `employee_code`, `last_name`, `first_name`,
     *                                             `middle_name`, `full_name`, `email`,
     *                                             `company_code`, `company_name`
     *
     * @throws \InvalidArgumentException При пустом `<sCode>` или несоответствии кол-ва участников.
     */
    private function extractParticipants(SimpleXMLElement $groupXml, int $declaredCount): array
    {
        $participants = [];

        foreach ($groupXml->Participants->Edu_Participant ?? [] as $p) {
            $sCode = trim((string) $p->sCode);

            if ($sCode === '') {
                throw new \InvalidArgumentException(
                    "Участник в группе (курс: {$groupXml->Edu_Course->sCode}) содержит пустой <sCode>."
                );
            }

            $participants[] = [
                'employee_code' => $sCode,
                'last_name'     => (string) $p->sLastName,
                'first_name'    => (string) $p->sMiddleName,
                'middle_name'   => (string) $p->sFirstName ?: null,
                'full_name'     => (string) $p->sFIO,
                'email'         => (string) $p->sEmail     ?: null,
                'company_code'  => (string) $p->idOrganization,
                'company_name'  => (string) $p->idOrganizationHL,
            ];
        }

        $actualCount = count($participants);

        if ($declaredCount !== 0 && $actualCount === 0) {
            throw new \InvalidArgumentException(
                "Группа (курс: {$groupXml->Edu_Course->sCode}): "
                . "nParticipantsCount={$declaredCount}, но список <Participants> отсутствует или пуст."
            );
        }

        if ($declaredCount !== 0 && $actualCount !== $declaredCount) {
            throw new \InvalidArgumentException(
                "Группа (курс: {$groupXml->Edu_Course->sCode}): "
                . "ожидалось {$declaredCount} участников, получено {$actualCount}."
            );
        }

        return $participants;
    }
}