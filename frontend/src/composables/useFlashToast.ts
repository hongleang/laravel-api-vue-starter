import { watch } from 'vue';
import { toast } from 'vue-sonner';

import { usePageStore } from '@/stores/page';

/**
 * Shows the `flash.success` and `flash.error` messages as toasts. The flash object is
 * replaced on every message, so the same text twice still toasts twice.
 */
export function useFlashToast(): void {
  const page = usePageStore();

  watch(
    () => page.flash,
    (flash) => {
      if (flash?.success) {
        toast.success(flash.success);
      }

      if (flash?.error) {
        toast.error(flash.error);
      }
    },
    { immediate: true }
  );
}
