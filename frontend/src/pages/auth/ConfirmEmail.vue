<script setup lang="ts">
import { Loader2 } from '@lucide/vue';
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import { api } from '@/api';
import { Button } from '@/components/ui/button';
import { useHttp } from '@/composables/useHttp';
import { setLayoutProps } from '@/composables/usePage';
import { useAuthStore } from '@/stores/auth';
import type { MessageResponse } from '@/types';

defineOptions({ name: 'ConfirmEmailPage' });

setLayoutProps({ title: 'Verifying your email' });

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();
const verify = useHttp<Record<string, never>, MessageResponse>();

/**
 * The emailed link opens this page with the API's id, hash and signature; pass them on
 * unchanged, because the signature covers the exact path and query.
 */
function confirm(): void {
  const query = window.location.search;

  verify.post(
    api.verifyEmail(String(route.params.id), String(route.params.hash), query),
    {
      onHttpException: () => false,
      onSuccess: async () => {
        await auth.load(true);
        await router.replace({ name: 'dashboard' });
      }
    }
  );
}

onMounted(confirm);
</script>

<template>
  <div
    v-if="verify.processing"
    role="status"
    class="text-muted-foreground flex items-center gap-2 text-sm"
  >
    <Loader2 class="text-primary size-4 animate-spin" aria-hidden="true" />
    Checking your link…
  </div>
  <div v-else-if="!verify.wasSuccessful" class="flex flex-col gap-3 text-sm">
    <p role="alert" class="text-destructive">
      This verification link is invalid or has expired.
    </p>
    <Button as-child>
      <RouterLink :to="{ name: 'verification.notice' }">
        Send a new link
      </RouterLink>
    </Button>
  </div>
</template>
