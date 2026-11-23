<script setup lang="ts">
import { Button } from '@/components/ui/button';

defineOptions({ name: 'NotFoundPage' });
</script>

<template>
  <div
    class="flex min-h-screen flex-col items-center justify-center gap-4 p-6 text-center"
  >
    <p class="text-muted-foreground text-sm font-medium">404</p>
    <h1 class="text-2xl font-semibold">Page not found</h1>
    <p class="text-muted-foreground max-w-sm text-sm">
      The page you are looking for does not exist or has moved.
    </p>
    <Button as-child>
      <RouterLink :to="{ name: 'dashboard' }">Back to the dashboard</RouterLink>
    </Button>
  </div>
</template>
