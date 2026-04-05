<?php

namespace Modules\Core\Contracts;

interface CalculableContract
{
    public function recalculateCost(): void;

    public function getTotalCost(): float;
}
