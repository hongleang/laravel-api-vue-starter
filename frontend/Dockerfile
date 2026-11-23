# syntax=docker/dockerfile:1

# Local development image for the Vue SPA's Vite dev server. Source is bind-mounted by
# ../docker-compose.yml, which also mounts a named volume over node_modules.
FROM node:22-slim

WORKDIR /app

# Dependencies are installed on start, so a changed package.json is picked up without a rebuild.
CMD ["sh", "-c", "npm install && npm run dev"]
