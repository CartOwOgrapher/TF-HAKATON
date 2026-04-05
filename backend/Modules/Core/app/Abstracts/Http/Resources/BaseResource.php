<?php

namespace Modules\Core\Abstracts\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseResource extends JsonResource
{
    public static $wrap = null;
}
