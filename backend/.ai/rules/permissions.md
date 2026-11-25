---
paths:
  - 'app/Policies/**'
  - 'app/Enums/**'
  - 'database/seeders/**'
---

# Permissions

## Policies check permissions, not roles
Permissions are the `App\Enums\Permission` cases (`users.view`, `users.create`, `users.update`, `users.delete`, `reports.view`). Roles are `App\Enums\Role` (`admin`, `user`), and `Role::permissions()` is the single source of which role gets what. Policies call `$user->can(Permission::X->value)`; never compare role names in a policy or controller.

## Adding a permission
Add the case to `Permission`, grant it in `Role::permissions()`, guard the endpoint with a policy method, add the route `meta.permission` in the SPA, and re-run `php artisan db:seed --class=RolesAndPermissionsSeeder`. The seeder is idempotent and syncs every role.

## Self-protection
Users can always view and update themselves, but can never delete themselves or change their own role (`UserPolicy::delete` and `assignRole`).
