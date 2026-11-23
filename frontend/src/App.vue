<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';

import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import BlankLayout from '@/layouts/BlankLayout.vue';
import { Toaster } from '@/components/ui/sonner';
import { useAppearance } from '@/composables/useAppearance';
import { useFlashToast } from '@/composables/useFlashToast';

defineOptions({ name: 'App' });

useAppearance();
useFlashToast();

const route = useRoute();

const layout = computed(() => {
  switch (route.meta.layout) {
    case 'auth':
      return AuthLayout;
    case 'blank':
      return BlankLayout;
    default:
      return AppLayout;
  }
});
</script>

<template>
  <div>
    <component :is="layout">
      <RouterView v-slot="{ Component }">
        <component :is="Component" />
      </RouterView>
    </component>
    <Toaster position="bottom-right" rich-colors expand :visible-toasts="4" />
  </div>
</template>
