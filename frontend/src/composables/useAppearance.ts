import { onMounted, ref } from 'vue';

export type Appearance = 'system' | 'light' | 'dark';

const STORAGE_KEY = 'appearance';

const appearance = ref<Appearance>('system');

function isAppearance(value: unknown): value is Appearance {
  return value === 'system' || value === 'light' || value === 'dark';
}

function readStoredAppearance(): Appearance {
  try {
    const stored = window.localStorage.getItem(STORAGE_KEY);

    return isAppearance(stored) ? stored : 'system';
  } catch {
    return 'system';
  }
}

function applyAppearance(value: Appearance): void {
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

  document.documentElement.classList.toggle(
    'dark',
    value === 'dark' || (value === 'system' && prefersDark)
  );
}

export function useAppearance() {
  onMounted(() => {
    appearance.value = readStoredAppearance();
    applyAppearance(appearance.value);
  });

  function updateAppearance(value: Appearance): void {
    appearance.value = value;

    try {
      window.localStorage.setItem(STORAGE_KEY, value);
    } catch {
      // Storage can be unavailable (private mode); the theme still applies for this visit.
    }

    applyAppearance(value);
  }

  return { appearance, updateAppearance };
}
