<?php

namespace Modules\Xml\Services\Parsers;

use SimpleXMLElement;

/**
 * Парсер XML-файла курса обучения (Edu_Course).
 *
 * Структура источника (Global ERP):
 *   <Edu_Course>
 *     <id>              — внешний ID в ERP
 *     <sCode>           — код курса
 *     <sCourseHL>       — название курса (human-label)
 *     <sDescription>    — описание
 *     <nDurationInDays> — длительность в днях
 *     <nPricePerPerson> — цена за человека (руб.)
 *   </Edu_Course>
 */
class CourseXmlParser
{
    /**
     * Парсит XML-строку и возвращает массив данных по курсам.
     *
     * Поддерживает два формата:
     * - одиночный: корневой тег `<Edu_Course>`
     * - пакетный: корневой тег `<Courses>` с дочерними `<Edu_Course>`
     *
     * @param  string  $xmlContent  Сырое содержимое XML.
     * @return array                Массив ассоциативных массивов, каждый из которых содержит:
     *                              - `external_id` (string)
     *                              - `code` (string)
     *                              - `title` (string)
     *                              - `description` (string|null)
     *                              - `duration_days` (int)
     *                              - `price` (string|null) — decimal-строка с двумя знаками
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

        if ($xml->getName() === 'Edu_Course') {
            $participants[] = $this->extractCourse($xml);

        } elseif ($xml->getName() === 'Courses') {
            foreach ($xml->children() as $child) {
                if ($child->getName() !== 'Edu_Course') {
                    continue;
                }
                $participants[] = $this->extractCourse($child);
            }

        } else {
            throw new \InvalidArgumentException(
                "Неподдерживаемый корневой тег: <{$xml->getName()}>. Ожидались: Course или Courses."
            );
        }

        return $participants;
    }

    /**
     * Извлекает данные одного курса из XML-элемента.
     *
     * @param  SimpleXMLElement  $xml  XML-элемент `<Edu_Course>`.
     * @return array                   Ассоциативный массив полей курса.
     */
    private function extractCourse(SimpleXMLElement $xml): array
    {
        $price = (string) $xml->nPricePerPerson;

        return [
            'external_id'   => (string) $xml->id,
            'code'          => (string) $xml->sCode,
            'title'         => (string) $xml->sCourseHL,
            'description'   => (string) $xml->sDescription ?: null,
            'duration_days' => (int) $xml->nDurationInDays,
            'price'         => $price !== '' ? number_format((float) $price, 2, '.', '') : null,
        ];
    }
}