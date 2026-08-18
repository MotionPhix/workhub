<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller as BaseController;
use App\Models\User;
use Illuminate\Http\Request;

abstract class Controller extends BaseController
{
    /**
     * Resolve the authenticated user from the request.
     */
    protected function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
