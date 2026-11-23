## Working agreement

This starter is a Laravel JSON API (`backend/`) and a Vue 3 SPA (`frontend/`) in one repository. Follow these rules in every session.

### Process

- Plan first. Propose a plan and wait for approval before writing code.
- One ticket per branch. Keep changes small and focused on the ticket.
- Do not touch unrelated files or refactor code outside the ticket.
- If a requirement is unclear, ask. Never invent business rules.
- Do not add a package (Composer or npm) without asking first.
- Check project rules (`.ai/rules/index.md`) before editing a file, and use Laravel Boost tools (schema, docs search, last error) instead of guessing APIs.

### Quality gates

Before saying a task is done, run all of these and report the real output, including failures:

- `backend/`: `composer test`, `composer lint`, `composer analyse`
- `frontend/`: `npm run lint`, `npm run types`, `npm run build`

Never disable, skip, or loosen tests, lint rules, or static analysis to get a green result. Never claim something works without running it.

### Definition of done

- Every acceptance criterion in the ticket is met
- Tests added and passing, including validation, unauthenticated (401) and forbidden (403) cases
- No N+1 queries on new list endpoints (add a query-count test)
- Formatting, static analysis, type check, and build all pass

### Security

- Never commit `.env` files or secrets.
- Every controller action is protected by a Policy, and policies check permissions, not role names.
- Never send raw models to the SPA: always an API Resource that lists its fields.
