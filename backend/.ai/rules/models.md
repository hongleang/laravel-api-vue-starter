---
paths:
  - 'app/Models/**'
---

# Models

## Model fillable, casts and docs conventions
Declare mass-assignment as `protected $fillable = [...]`, never the `#[Fillable([...])]` attribute. Declare casts as `protected $casts = [...]` (a property array), never a `casts()` method. Do not write `@property` docblocks for columns; types come from `$casts` and the migrations. Keep PHPDoc generics on relationships (for example `@return BelongsTo<Team, $this>`): Larastan level 7 requires them.

## Users
Users have `first_name`, `last_name`, nullable `mobile_phone` and `email`, with no `name` column. Roles and permissions come from `spatie/laravel-permission` (`HasRoles`) on the `web` guard.
