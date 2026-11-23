<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexUsersRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function index(IndexUsersRequest $request): AnonymousResourceCollection
    {
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, string $value): void {
                    $term = '%'.addcslashes(trim($value), '%_\\').'%';

                    $query->where(function (Builder $query) use ($term): void {
                        $query->where('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhere('email', 'like', $term);
                    });
                }),
                AllowedFilter::scope('role'),
            )
            ->allowedSorts(...IndexUsersRequest::SORTABLE_COLUMNS)
            ->defaultSort('first_name')
            ->with('roles')
            ->orderBy('id')
            ->paginate($request->perPage())
            ->withQueryString();

        return UserResource::collection($users);
    }

    /**
     * New users get a random password and an email to choose their own.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::create([
                ...$request->userAttributes(),
                'password' => Str::password(32),
            ]);

            $user->assignRole($request->role()->value);

            return $user;
        });

        Password::sendResetLink(['email' => $user->email]);

        return (new UserResource($user->load('roles')))
            ->additional(['message' => 'User created. A password reset link has been sent.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        Gate::authorize('view', $user);

        return new UserResource($user->load('roles'));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        DB::transaction(function () use ($request, $user): void {
            $user->update($request->userAttributes());

            if ($request->role() !== null) {
                $user->syncRoles([$request->role()->value]);
            }
        });

        return (new UserResource($user->load('roles')))->additional(['message' => 'User updated.']);
    }

    public function destroy(User $user): JsonResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }
}
