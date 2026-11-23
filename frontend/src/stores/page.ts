import { defineStore } from 'pinia';
import { ref } from 'vue';

import type { BreadcrumbItem, Flash } from '@/types';

export interface LayoutProps {
  breadcrumbs?: BreadcrumbItem[];
  title?: string;
  description?: string;
}

/**
 * Per-page state that Inertia would keep on the page object: flash messages and the props
 * a page passes up to its layout.
 */
export const usePageStore = defineStore('page', () => {
  const flash = ref<Flash>({});
  const layoutProps = ref<LayoutProps>({});

  /** Show a flash message; useFlashToast turns it into a toast. */
  function setFlash(value: Flash): void {
    flash.value = { ...value };
  }

  function setLayoutProps(props: LayoutProps): void {
    layoutProps.value = { ...props };
  }

  function resetLayoutProps(): void {
    layoutProps.value = {};
  }

  return { flash, layoutProps, setFlash, setLayoutProps, resetLayoutProps };
});
