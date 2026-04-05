<?php

namespace Modules\Core\Abstracts\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class BaseController extends Controller
{
    use ApiResponse, AuthorizesRequests;
}
