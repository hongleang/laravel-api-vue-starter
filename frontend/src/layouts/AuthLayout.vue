<script setup lang="ts">
import { LayoutGrid } from '@lucide/vue';
import { computed } from 'vue';

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle
} from '@/components/ui/card';
import { usePageStore } from '@/stores/page';

defineOptions({ name: 'AuthLayout' });

const page = usePageStore();

/** Set by each page with setLayoutProps({ title, description }). */
const title = computed(() => page.layoutProps.title ?? '');
const description = computed(() => page.layoutProps.description ?? '');
const appName = import.meta.env.VITE_APP_NAME ?? 'Laravel Vue Starter';
</script>

<template>
  <div
    class="bg-background flex min-h-screen flex-col items-center justify-center gap-6 p-6"
  >
    <div class="flex items-center gap-2 font-semibold">
      <div
        class="bg-primary text-primary-foreground flex size-8 items-center justify-center rounded-lg"
      >
        <LayoutGrid class="size-4" aria-hidden="true" />
      </div>
      {{ appName }}
    </div>
    <Card class="w-full max-w-sm">
      <CardHeader v-if="title">
        <CardTitle class="text-xl">{{ title }}</CardTitle>
        <CardDescription v-if="description">{{ description }}</CardDescription>
      </CardHeader>
      <CardContent>
        <slot />
      </CardContent>
    </Card>
  </div>
</template>
