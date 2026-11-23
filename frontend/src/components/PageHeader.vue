<script setup lang="ts">
defineOptions({ name: 'PageHeader' });

withDefaults(
  defineProps<{
    title: string;
    description?: string;
  }>(),
  { description: '' }
);
</script>

<template>
  <div
    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
  >
    <div class="min-w-0">
      <h1 class="truncate text-2xl font-semibold tracking-tight">
        {{ title }}
      </h1>
      <p v-if="description" class="text-muted-foreground text-sm">
        {{ description }}
      </p>
    </div>
    <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
      <slot name="actions" />
    </div>
  </div>
</template>
