<?php

namespace Modules\Core\Enums;

enum Role: string
{
    case Admin = 'admin';
    case HR = 'hr';
    case TrainingCenter = 'training';
    case Accounting = 'accounting';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Администратор',
            self::HR => 'HR-специалист',
            self::TrainingCenter => 'Центр обучения',
            self::Accounting => 'Бухгалтерия',
        };
    }
}
