import { createPinia } from 'pinia';
import { createApp } from 'vue';

import App from './App.vue';
import './app.css';
import { on } from '@/lib/http';
import { router } from '@/router';
import { useAuthStore } from '@/stores/auth';
import { usePageStore } from '@/stores/page';

const app = createApp(App);

app.use(createPinia());
app.use(router);

app.directive('focus', {
  mounted: (el: HTMLElement, binding) => {
    if (binding.value !== false) {
      el.focus();
    }
  }
});

/*
 * Client-wide response handling, the SPA version of Laravel's exception handler plus
 * Inertia's flash messages: success messages and errors become toasts, and a 401 sends
 * the visitor back to sign in.
 */
const page = usePageStore();
const auth = useAuthStore();

on('success', ({ method, body }) => {
  if (
    method !== 'get' &&
    body &&
    typeof body === 'object' &&
    'message' in body
  ) {
    const { message } = body as { message: unknown };

    if (typeof message === 'string' && message !== '') {
      page.setFlash({ success: message });
    }
  }
});

on('httpException', (error) => {
  page.setFlash({ error: error.message });
});

on('networkError', (error) => {
  page.setFlash({ error: error.message });
});

on('unauthorized', () => {
  auth.setUser(null);

  const current = router.currentRoute.value;

  if (!current.meta.guest) {
    page.setFlash({ error: 'Your session ended. Please sign in again.' });
    void router.push({ name: 'login', query: { redirect: current.fullPath } });
  }
});

// Mount after the first navigation, so the right layout renders from the start.
void router.isReady().then(() => app.mount('#app'));
