---
paths:
  - 'app/Http/**'
  - 'routes/**'
---

# Http

## The API is JSON only, behind Sanctum's SPA cookie auth
Routes live in `routes/api.php` under `/api`. The SPA signs in with the session guard through Sanctum (`statefulApi()`); there are no API tokens. Protected routes use `auth:sanctum`, and routes that need a verified email also use `verified`. Return API Resources (or `response()->json(['message' => ...])` for plain actions), never views or redirects.

## Controllers return API Resources, never raw models
Every action that returns model data wraps it in an Eloquent API Resource from `app/Http/Resources` (created with `php artisan make:resource`). Lists paginate and pass the paginator to `Resource::collection()`, so the response has `data`, `links` and `meta`; extra keys go in `additional()`. Resources list their fields explicitly and expose what the viewer may do as a `can` object (for example `can.update`), computed from the policy.

## Errors follow one JSON shape
Validation errors are 422 `{ "message", "errors": { "field": ["..."] } }`, which the SPA's `useForm` maps onto `form.errors`. Every other API error is `{ "message" }` with a readable sentence (set in `bootstrap/app.php`). Mutating actions include a `message` on success; the SPA shows it as a toast.

## Validation lives in Form Requests
Use a Form Request (with `authorize()` calling the policy, so unauthorised users get 403, not 422) for writes and for list query strings.

## List endpoints use Spatie Query Builder
Build lists with `QueryBuilder::for(Model::class)` and allow-list `allowedFilters()` and `allowedSorts()`. Clients send `filter[name]=x`, `sort=-column` and `per_page`/`page`. Validate those values in the list's Form Request so bad input is a 422, and keep a stable tie-breaker such as `->orderBy('id')`.

## Emailed links open the SPA
Password reset and email verification links point at `FRONTEND_URL` (configured in `AppServiceProvider`). The verification signature is relative (`signed:relative`), so it validates through the dev proxy.
