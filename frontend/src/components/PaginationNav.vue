<script setup lang="ts">
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { PaginatedResource } from '@/types';

defineOptions({ name: 'PaginationNav' });

defineProps<{
  meta: PaginatedResource<unknown>['meta'];
  links: PaginatedResource<unknown>['links'];
  disabled?: boolean;
}>();

const emit = defineEmits<{
  navigate: [url: string];
}>();
</script>

<template>
  <nav
    aria-label="Pagination"
    class="flex flex-col items-center justify-between gap-3 sm:flex-row"
  >
    <p class="text-muted-foreground text-sm" aria-live="polite">
      <template v-if="meta.total > 0">
        Showing {{ meta.from }} to {{ meta.to }} of {{ meta.total }}
      </template>
      <template v-else>No results</template>
    </p>
    <div class="flex items-center gap-2">
      <Button
        type="button"
        variant="outline"
        size="sm"
        :disabled="disabled || !links.prev"
        @click="links.prev && emit('navigate', links.prev)"
      >
        <ChevronLeft aria-hidden="true" />
        Previous
      </Button>
      <span class="text-sm">
        Page {{ meta.current_page }} of {{ meta.last_page }}
      </span>
      <Button
        type="button"
        variant="outline"
        size="sm"
        :disabled="disabled || !links.next"
        @click="links.next && emit('navigate', links.next)"
      >
        Next
        <ChevronRight aria-hidden="true" />
      </Button>
    </div>
  </nav>
</template>
