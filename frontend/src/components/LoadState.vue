<script setup lang="ts">
import { AlertCircle, Loader2 } from '@lucide/vue';

import { Button } from '@/components/ui/button';

defineOptions({ name: 'LoadState' });

defineProps<{
  loading: boolean;
  error: string | null;
}>();

const emit = defineEmits<{
  retry: [];
}>();
</script>

<template>
  <div
    v-if="loading"
    role="status"
    class="text-muted-foreground flex items-center justify-center gap-2 py-12 text-sm"
  >
    <Loader2 class="text-primary size-5 animate-spin" aria-hidden="true" />
    Loading…
  </div>
  <div
    v-else-if="error"
    role="alert"
    class="border-destructive/50 text-destructive flex items-center gap-3 rounded-lg border p-4 text-sm"
  >
    <AlertCircle class="size-4 shrink-0" aria-hidden="true" />
    <span class="flex-1">{{ error }}</span>
    <Button variant="outline" size="sm" @click="emit('retry')"
      >Try again</Button
    >
  </div>
  <slot v-else />
</template>
