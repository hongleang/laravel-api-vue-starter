import { fileURLToPath, URL } from 'node:url';

import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');

  // The Laravel API. The dev server proxies /api and /sanctum to it, so the browser only
  // ever talks to this origin: Sanctum's session cookie is first-party and there is no CORS.
  const apiTarget = env.VITE_API_PROXY_TARGET || 'http://localhost:8000';

  return {
    plugins: [vue(), tailwindcss()],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url))
      }
    },
    server: {
      host: env.VITE_DEV_HOST || 'localhost',
      port: 8080,
      strictPort: true,
      // Bind mounts (Docker on macOS/Windows) often drop file events, so Docker turns polling on.
      watch:
        env.VITE_USE_POLLING === 'true'
          ? { usePolling: true, interval: 300 }
          : undefined,
      proxy: {
        '/api': { target: apiTarget },
        '/sanctum': { target: apiTarget }
      }
    }
  };
});
