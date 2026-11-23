import { ref, watch } from 'vue';
import { useRoute } from 'vue-router';

import { api } from '@/api';
import { useHttp } from '@/composables/useHttp';
import type { ResourceResponse, UserRecord } from '@/types';

/**
 * Load the user named by the current route's :id, with loading and error state.
 * Reloads when the id changes.
 */
export function useUser() {
  const route = useRoute();
  const request = useHttp<
    Record<string, never>,
    ResourceResponse<UserRecord>
  >();
  const user = ref<UserRecord | null>(null);
  const error = ref<string | null>(null);

  function load(): Promise<unknown> {
    error.value = null;

    return request.get(api.users.show(String(route.params.id)), {
      onSuccess: (response) => {
        user.value = response.data;
      },
      onHttpException: (exception) => {
        error.value =
          exception.status === 404
            ? 'This user no longer exists.'
            : exception.message;

        return false;
      },
      onNetworkError: (exception) => {
        error.value = exception.message;

        return false;
      }
    });
  }

  watch(
    () => route.params.id,
    () => void load(),
    { immediate: true }
  );

  return { user, request, error, load };
}
