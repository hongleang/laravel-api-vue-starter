import {
  createRouter,
  createWebHistory,
  type RouteRecordRaw
} from 'vue-router';

import { useAuthStore } from '@/stores/auth';
import { usePageStore } from '@/stores/page';
import type { Permission } from '@/types';

declare module 'vue-router' {
  interface RouteMeta {
    /** Which layout wraps the page. */
    layout?: 'app' | 'auth' | 'blank';
    /** Document title. */
    title?: string;
    /** Only for signed-out visitors (login, forgot password). */
    guest?: boolean;
    /** Requires a signed-in user. */
    auth?: boolean;
    /** Requires a verified email address. */
    verified?: boolean;
    /** Requires this permission. */
    permission?: Permission;
  }
}

const routes: RouteRecordRaw[] = [
  { path: '/', redirect: { name: 'dashboard' } },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/pages/auth/Login.vue'),
    meta: { layout: 'auth', guest: true, title: 'Log in' }
  },
  {
    path: '/forgot-password',
    name: 'password.request',
    component: () => import('@/pages/auth/ForgotPassword.vue'),
    meta: { layout: 'auth', guest: true, title: 'Forgot password' }
  },
  {
    path: '/reset-password/:token',
    name: 'password.reset',
    component: () => import('@/pages/auth/ResetPassword.vue'),
    meta: { layout: 'auth', guest: true, title: 'Reset password' }
  },
  {
    path: '/verify-email',
    name: 'verification.notice',
    component: () => import('@/pages/auth/VerifyEmail.vue'),
    meta: { layout: 'auth', auth: true, title: 'Verify email' }
  },
  {
    path: '/verify-email/:id/:hash',
    name: 'verification.verify',
    component: () => import('@/pages/auth/ConfirmEmail.vue'),
    meta: { layout: 'auth', auth: true, title: 'Verifying email' }
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/pages/Dashboard.vue'),
    meta: { layout: 'app', auth: true, verified: true, title: 'Dashboard' }
  },
  {
    path: '/settings',
    redirect: { name: 'profile.edit' }
  },
  {
    path: '/settings/profile',
    name: 'profile.edit',
    component: () => import('@/pages/settings/Profile.vue'),
    meta: { layout: 'app', auth: true, title: 'Profile' }
  },
  {
    path: '/settings/preferences',
    name: 'settings.preferences',
    component: () => import('@/pages/settings/Preferences.vue'),
    meta: { layout: 'app', auth: true, title: 'Preferences' }
  },
  {
    path: '/reports',
    name: 'reports',
    component: () => import('@/pages/reports/Index.vue'),
    meta: {
      layout: 'app',
      auth: true,
      verified: true,
      permission: 'reports.view',
      title: 'User report'
    }
  },
  {
    path: '/users',
    name: 'users.index',
    component: () => import('@/pages/users/Index.vue'),
    meta: {
      layout: 'app',
      auth: true,
      verified: true,
      permission: 'users.view',
      title: 'Users'
    }
  },
  {
    path: '/users/create',
    name: 'users.create',
    component: () => import('@/pages/users/Create.vue'),
    meta: {
      layout: 'app',
      auth: true,
      verified: true,
      permission: 'users.create',
      title: 'New user'
    }
  },
  {
    path: '/users/:id',
    name: 'users.show',
    component: () => import('@/pages/users/Show.vue'),
    meta: {
      layout: 'app',
      auth: true,
      verified: true,
      permission: 'users.view',
      title: 'User'
    }
  },
  {
    path: '/users/:id/edit',
    name: 'users.edit',
    component: () => import('@/pages/users/Edit.vue'),
    meta: {
      layout: 'app',
      auth: true,
      verified: true,
      permission: 'users.update',
      title: 'Edit user'
    }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/pages/NotFound.vue'),
    meta: { layout: 'blank', title: 'Page not found' }
  }
];

export const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: (_to, _from, savedPosition) => savedPosition ?? { top: 0 }
});

/**
 * Route guards, like Laravel's guest / auth / verified / can middleware. The first
 * navigation waits for GET /api/user, so the session decides where the visitor lands.
 */
router.beforeEach(async (to) => {
  const auth = useAuthStore();

  usePageStore().resetLayoutProps();

  try {
    await auth.load();
  } catch {
    // The API is unreachable; let the page render and show its own error state.
  }

  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'dashboard' };
  }

  if (to.meta.auth && !auth.isAuthenticated) {
    return {
      name: 'login',
      query: to.fullPath === '/dashboard' ? {} : { redirect: to.fullPath }
    };
  }

  if (to.meta.verified && !auth.isVerified) {
    return { name: 'verification.notice' };
  }

  if (to.meta.permission && !auth.can(to.meta.permission)) {
    usePageStore().setFlash({
      error: 'You do not have permission to do that.'
    });

    return { name: 'dashboard' };
  }

  return true;
});

router.afterEach((to) => {
  const appName = import.meta.env.VITE_APP_NAME ?? 'Laravel Vue Starter';

  document.title = to.meta.title ? `${to.meta.title} - ${appName}` : appName;
});
