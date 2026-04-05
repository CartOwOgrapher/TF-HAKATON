<?php

namespace Modules\Core\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface HasCompanyScope
{
    public function company(): BelongsTo;
}
