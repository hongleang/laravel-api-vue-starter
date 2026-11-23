import { onScopeDispose, ref, watch } from 'vue';
import type { Ref } from 'vue';

/**
 * Mirrors `source`, but only becomes true after it has stayed true for `delay`
 * milliseconds. Fast requests never flash a loader; it turns off immediately.
 */
export function useDelayedFlag(
  source: Ref<boolean>,
  delay = 300
): Ref<boolean> {
  const visible = ref(false);
  let timer: ReturnType<typeof setTimeout> | undefined;

  watch(
    source,
    (active) => {
      clearTimeout(timer);

      if (active) {
        timer = setTimeout(() => {
          visible.value = true;
        }, delay);
      } else {
        visible.value = false;
      }
    },
    { immediate: true }
  );

  onScopeDispose(() => clearTimeout(timer));

  return visible;
}
