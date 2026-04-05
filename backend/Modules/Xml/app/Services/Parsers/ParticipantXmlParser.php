<?php

namespace Modules\Xml\Services\Parsers;

use SimpleXMLElement;

/**
 * Парсер XML-файла участника обучения (Edu_Participant).
 *
 * Структура источника (Global ERP):
 *   <Edu_Participant>
 *     <id>          — внешний ID в ERP
 *     <sCode>       — табельный номер (employee_code)
 *     <sLastName>   — фамилия
 *     <sMiddleName> — имя (в ERP поле названо «Middle», но содержит имя)
 *     <sFirstName>  — отчество (в ERP — «First», но содержит отчество)
 *     <sFIO>        — ФИО целиком
 *     <idOrganization>   — внешний ID компании
 *     <idOrganizationHL> — название компании (human-label)
 *   </Edu_Participant>
 */
class ParticipantXmlParser
{
    /**
     * Парсит XML-строку и возвращает массив данных по участникам.
     *
     * Поддерживает два формата:
     * - одиночный: корневой тег `<Edu_Participant>`
     * - пакетный: корневой тег `<Participants>` с дочерними `<Edu_Participant>`
     *
     * @param  string  $xmlContent  Сырое содержимое XML.
     * @return array                Массив ассоциативных массивов, каждый из которых содержит:
     *                              - `external_id` (string)
     *                              - `employee_code` (string)
     *                              - `last_name` (string)
     *                              - `first_name` (string) — из тега `<sMiddleName>`
     *                              - `middle_name` (string) — из тега `<sFirstName>`
     *                              - `full_name` (string)
     *                              - `company_external_id` (string)
     *                              - `company_name` (string)
     *
     * @throws \InvalidArgumentException Если XML невалиден или корневой тег не поддерживается.
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

        $participants = [];

        if ($xml->getName() === 'Edu_Participant') {
            $participants[] = $this->extractParticipant($xml);

        } elseif ($xml->getName() === 'Participants') {
            foreach ($xml->children() as $child) {
                if ($child->getName() !== 'Edu_Participant') {
                    continue;
                }
                $participants[] = $this->extractParticipant($child);
            }

        } else {
            throw new \InvalidArgumentException(
                "Неподдерживаемый корневой тег: <{$xml->getName()}>. Ожидались: Edu_Participant или Participants."
            );
        }

        return $participants;
    }

    /**
     * Извлекает данные одного участника из XML-элемента.
     *
     * @param  SimpleXMLElement  $xml  XML-элемент `<Edu_Participant>`.
     * @return array                   Ассоциативный массив полей участника.
     */
    private function extractParticipant(SimpleXMLElement $xml): array
    {
        return [
            'external_id'      => (string) $xml->id,
            'employee_code'    => (string) $xml->sCode,
            'last_name'        => (string) $xml->sLastName,
            'first_name'       => (string) $xml->sMiddleName,
            'middle_name'      => (string) $xml->sFirstName,
            'full_name'        => (string) $xml->sFIO,
            'company_external_id'   => (string) $xml->idOrganization,
            'company_name'          => (string) $xml->idOrganizationHL,
        ];
    }
}