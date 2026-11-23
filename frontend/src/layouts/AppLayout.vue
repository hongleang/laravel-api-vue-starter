<script setup lang="ts">
import { computed } from 'vue';

import AppSidebar from '@/components/AppSidebar.vue';
import UserMenu from '@/components/UserMenu.vue';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator
} from '@/components/ui/breadcrumb';
import { Separator } from '@/components/ui/separator';
import {
  SidebarInset,
  SidebarProvider,
  SidebarTrigger
} from '@/components/ui/sidebar';
import { usePageStore } from '@/stores/page';

defineOptions({ name: 'AppLayout' });

const page = usePageStore();

/** Set by each page with setLayoutProps({ breadcrumbs }). */
const breadcrumbs = computed(() => page.layoutProps.breadcrumbs ?? []);
</script>

<template>
  <SidebarProvider>
    <AppSidebar />
    <SidebarInset>
      <header
        class="bg-background flex h-14 shrink-0 items-center gap-2 border-b px-4"
      >
        <SidebarTrigger class="-ml-1" />
        <Separator orientation="vertical" class="mr-2 h-4" />
        <Breadcrumb v-if="breadcrumbs.length > 0" class="min-w-0 flex-1">
          <BreadcrumbList>
            <template v-for="(crumb, index) in breadcrumbs" :key="crumb.title">
              <BreadcrumbItem>
                <BreadcrumbPage v-if="index === breadcrumbs.length - 1">
                  {{ crumb.title }}
                </BreadcrumbPage>
                <BreadcrumbLink v-else-if="crumb.href" as-child>
                  <RouterLink :to="crumb.href">{{ crumb.title }}</RouterLink>
                </BreadcrumbLink>
                <span v-else>{{ crumb.title }}</span>
              </BreadcrumbItem>
              <BreadcrumbSeparator v-if="index < breadcrumbs.length - 1" />
            </template>
          </BreadcrumbList>
        </Breadcrumb>
        <div v-else class="flex-1" />
        <UserMenu />
      </header>
      <main class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <slot />
      </main>
    </SidebarInset>
  </SidebarProvider>
</template>
