<script setup lang="ts">
import { cn } from '@/lib/utils';

defineOptions({ name: 'SettingsNav' });

defineProps<{
  current: 'profile' | 'preferences';
}>();

const items = [
  { key: 'profile', title: 'Profile', href: { name: 'profile.edit' } },
  {
    key: 'preferences',
    title: 'Preferences',
    href: { name: 'settings.preferences' }
  }
] as const;
</script>

<template>
  <nav aria-label="Settings" class="flex gap-1 border-b">
    <RouterLink
      v-for="item in items"
      :key="item.key"
      :to="item.href"
      :aria-current="current === item.key ? 'page' : undefined"
      :class="
        cn(
          'focus-visible:ring-ring -mb-px border-b-2 px-3 py-2 text-sm font-medium outline-none focus-visible:ring-2',
          current === item.key
            ? 'border-primary text-foreground'
            : 'text-muted-foreground hover:text-foreground border-transparent'
        )
      "
    >
      {{ item.title }}
    </RouterLink>
  </nav>
</template>
