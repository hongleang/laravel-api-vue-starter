import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

import { api } from '@/api';
import { HttpError, http } from '@/lib/http';
import type { CurrentUser, Permission, ResourceResponse } from '@/types';

/**
 * The signed-in user, loaded once from GET /api/user and kept in sync after sign-in,
 * sign-out and profile changes. Like Inertia's `auth.user` shared prop.
 */
export const useAuthStore = defineStore('auth', () => {
  const user = ref<CurrentUser | null>(null);
  const loaded = ref(false);
  let loading: Promise<CurrentUser | null> | null = null;

  const isAuthenticated = computed(() => user.value !== null);
  const isVerified = computed(() => Boolean(user.value?.email_verified_at));

  function can(permission: Permission): boolean {
    return user.value?.permissions.includes(permission) ?? false;
  }

  function setUser(value: CurrentUser | null): void {
    user.value = value;
    loaded.value = true;
  }

  /**
   * Load the current user once per page load (the router guard awaits this).
   * A 401 simply means nobody is signed in.
   */
  function load(force = false): Promise<CurrentUser | null> {
    if (loaded.value && !force) {
      return Promise.resolve(user.value);
    }

    loading ??= http
      .get<ResourceResponse<CurrentUser>>(api.currentUser(), { report: false })
      .then((response) => {
        setUser(response.data);

        return response.data;
      })
      .catch((error: unknown) => {
        if (error instanceof HttpError && error.status === 401) {
          setUser(null);

          return null;
        }

        throw error;
      })
      .finally(() => {
        loading = null;
      });

    return loading;
  }

  async function logout(): Promise<void> {
    try {
      await http.post(api.logout());
    } finally {
      setUser(null);
    }
  }

  return {
    user,
    loaded,
    isAuthenticated,
    isVerified,
    can,
    setUser,
    load,
    logout
  };
});
