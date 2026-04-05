<?php

namespace Modules\Core\Traits;

/**
 * Трейт для расчёта НДС и итоговых сумм
 * 
 * Применяется к моделям, имеющим денежную стоимость.
 * Ставка НДС задаётся в свойстве $vatRate (по умолчанию 22%).
 * 
 * @package Modules\Core\Traits
 * 
 * @property-read float $vat_rate Текущая ставка НДС (0.22)
 * 
 * @method float calculateVAT(float $amount) Рассчитать сумму НДС для заданной суммы
 * @method float calculateTotalWithVAT(float $amount) Рассчитать итоговую сумму с НДС
 */
trait CalculatesVAT
{
    /**
     * Ставка НДС (22% по умолчанию)
     * 
     * @var float
     */
    protected static float $vatRate = 0.22;

    /**
     * Рассчитать сумму НДС для заданной суммы без налога
     * 
     * @param float $amount Сумма без НДС
     * @return float Сумма НДС, округлённая до 2 знаков
     * 
     * @example calculateVAT(1000.00) // returns 220.00
     */
    public function calculateVAT(float $amount): float
    {
        return round($amount * static::$vatRate, 2);
    }

    /**
     * Рассчитать итоговую сумму с учётом НДС
     * 
     * @param float $amount Сумма без НДС
     * @return float Итоговая сумма с НДС, округлённая до 2 знаков
     * 
     * @example calculateTotalWithVAT(1000.00) // returns 1220.00
     */
    public function calculateTotalWithVAT(float $amount): float
    {
        return round($amount + $this->calculateVAT($amount), 2);
    }
}
