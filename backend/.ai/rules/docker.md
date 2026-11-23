---
paths:
  - '../docker-compose.yml'
  - 'Dockerfile'
  - 'docker/**'
  - '../docker/**'
---

# Docker

## Docker stack conventions
The Docker stack (`../docker-compose.yml`, `Dockerfile`, `../docker/`) is for local MySQL development; native `composer dev` / `npm run dev` with SQLite tests stay the default and CI path. Run tests in Docker only with `docker compose run --rm test`, never inside `app`, because env vars beat phpunit.xml and `RefreshDatabase` would wipe the dev database. Values in the compose file are literal except `DB_PASSWORD`, which comes from the root `.env`. Use the `docker-development` skill for commands.
