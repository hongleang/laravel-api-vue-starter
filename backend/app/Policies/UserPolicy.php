<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::UsersView->value);
    }

    public function view(User $user, User $model): bool
    {
        return $user->is($model) || $user->can(Permission::UsersView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::UsersCreate->value);
    }

    /**
     * Everyone may update their own profile; updating others needs the permission.
     */
    public function update(User $user, User $model): bool
    {
        return $user->is($model) || $user->can(Permission::UsersUpdate->value);
    }

    /**
     * Users with the permission may delete other users, but never themselves.
     */
    public function delete(User $user, User $model): bool
    {
        return ! $user->is($model) && $user->can(Permission::UsersDelete->value);
    }

    /**
     * Changing roles is part of managing other users, never of editing your own profile.
     */
    public function assignRole(User $user, User $model): bool
    {
        return ! $user->is($model) && $user->can(Permission::UsersUpdate->value);
    }

    /**
     * Running and exporting the user report.
     */
    public function report(User $user): bool
    {
        return $user->can(Permission::ReportsView->value);
    }
}
