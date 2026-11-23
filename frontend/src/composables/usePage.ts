import { reactive } from 'vue';
import { useRoute } from 'vue-router';

import { useAuthStore } from '@/stores/auth';
import { usePageStore, type LayoutProps } from '@/stores/page';
import type { SharedProps } from '@/types';

export interface Page {
  /** Shared state, like Inertia's page.props: { name, auth: { user }, flash }. */
  readonly props: SharedProps;
  readonly url: string;
  readonly component: string;
}

/**
 * Inertia-style access to shared page state, backed by the Pinia stores. The returned
 * object is reactive, so `page.props.auth.user` updates when the user changes.
 *
 * @example
 * const page = usePage()
 * const firstName = computed(() => page.props.auth.user?.first_name)
 */
export function usePage(): Page {
  const auth = useAuthStore();
  const pageStore = usePageStore();
  const route = useRoute();

  return reactive({
    get props(): SharedProps {
      return {
        name: import.meta.env.VITE_APP_NAME ?? 'Laravel Vue Starter',
        auth: { user: auth.user },
        flash: pageStore.flash
      };
    },
    get url(): string {
      return route.fullPath;
    },
    get component(): string {
      return String(route.name ?? '');
    }
  });
}

/**
 * Pass props up to the current layout (breadcrumbs, title, description), like Inertia's
 * setLayoutProps. Layout props reset on every navigation.
 */
export function setLayoutProps(props: LayoutProps): void {
  usePageStore().setLayoutProps(props);
}
