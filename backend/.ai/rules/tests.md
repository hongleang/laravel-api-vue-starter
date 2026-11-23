---
paths:
  - 'tests/**'
---

# Tests

## Use PHPUnit with camelCase test names
Tests are PHPUnit 12 class-style tests created with `php artisan make:test --phpunit`. Do not add Pest. Name methods in camelCase with the `test` prefix (`testAdminsCanDeleteOtherUsers`); `pint.json` enforces it.

## Test the API as the SPA calls it
Use the JSON helpers (`getJson`, `postJson`, ...). `tests/TestCase.php` sends the SPA's `Referer`, so Sanctum treats requests as stateful and `actingAs($user)` works. Cover success, validation (422), unauthenticated (401) and forbidden (403) for every endpoint, and assert sensitive fields are missing. Use factory states for roles: `User::factory()->admin()`, `->user()`, `->unverified()`.

## No N+1 on list endpoints
Every list endpoint has a query-count test that warms the permission cache with one request, then asserts the count does not grow with rows.
