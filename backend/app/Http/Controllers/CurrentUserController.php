<?php

namespace App\Http\Controllers;

use App\Http\Resources\CurrentUserResource;
use App\Models\User;
use Illuminate\Http\Request;

class CurrentUserController extends Controller
{
    /**
     * The signed-in user, with their roles and permissions, for the SPA's auth store.
     */
    public function __invoke(Request $request): CurrentUserResource
    {
        /** @var User $user */
        $user = $request->user();

        return new CurrentUserResource($user);
    }
}
