---
paths:
  - '../frontend/src/**'
---

# Frontend

## Inertia-style helpers, no Inertia and no Wayfinder
The SPA mirrors Inertia's API without Inertia. Submit forms with `useForm` (`@/composables/useForm`): fields live on the form, `form.errors` holds the first 422 message per field, and `post/put/patch/delete(url, { onSuccess, onError, onFinish })` behave like Inertia's. Load data with `useHttp`. Read shared state with `usePage()` (`page.props.auth.user`, `page.props.flash`) and set breadcrumbs or the auth-card title with `setLayoutProps()`. Do not call `fetch` directly: use `@/lib/http`, which handles the CSRF cookie, 419 retries, files as FormData and the error events.

## Routes and endpoints
Link with named Vue Router routes (`{ name: 'users.edit', params: { id } }`); guard routes with `meta` (`guest`, `auth`, `verified`, `permission`). Build API URLs only through `@/api`. Hide UI the user cannot use with `auth.can('users.create')` or the resource's `can` object, and keep the server policy as the real check.

## Components, state and style
UI components come from shadcn-vue in `src/components/ui`. Shared state lives in Pinia stores (`src/stores`). Every list and detail view has loading (spinner, `useDelayedFlag`), empty and error states. Dark mode uses `useAppearance` and the `.dark` class, with design tokens in `src/app.css`. Prettier formats with 2 spaces, single quotes and no trailing commas; run `npm run lint`, `npm run types` and `npm run build`.
