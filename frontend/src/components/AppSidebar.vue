<script setup lang="ts">
import { LayoutDashboard, LayoutGrid, Settings, Users } from '@lucide/vue';
import { computed } from 'vue';

import {
  Sidebar,
  SidebarContent,
  SidebarGroup,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarRail,
  useSidebar
} from '@/components/ui/sidebar';
import { useAuthStore } from '@/stores/auth';
import type { NavItem } from '@/types';

defineOptions({ name: 'AppSidebar' });

const { state } = useSidebar();
const auth = useAuthStore();
const appName = import.meta.env.VITE_APP_NAME ?? 'Laravel Vue Starter';

const allNavItems: NavItem[] = [
  { title: 'Dashboard', href: { name: 'dashboard' }, icon: LayoutDashboard },
  {
    title: 'Users',
    href: { name: 'users.index' },
    icon: Users,
    permission: 'users.view'
  },
  { title: 'Settings', href: { name: 'profile.edit' }, icon: Settings }
];

/** Items the user has no permission for are hidden, matching the route guards. */
const mainNavItems = computed(() =>
  allNavItems.filter((item) => !item.permission || auth.can(item.permission))
);
</script>

<template>
  <Sidebar collapsible="icon" variant="sidebar">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <RouterLink :to="{ name: 'dashboard' }">
              <div
                class="bg-primary text-primary-foreground flex size-8 items-center justify-center rounded-lg"
              >
                <LayoutGrid class="size-4" aria-hidden="true" />
              </div>
              <span
                :class="{ hidden: state === 'collapsed' }"
                class="truncate font-semibold"
                >{{ appName }}</span
              >
            </RouterLink>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>
    <SidebarContent>
      <SidebarGroup>
        <SidebarMenu>
          <SidebarMenuItem v-for="item in mainNavItems" :key="item.title">
            <SidebarMenuButton as-child :tooltip="item.title">
              <RouterLink :to="item.href">
                <component :is="item.icon" aria-hidden="true" />
                <span>{{ item.title }}</span>
              </RouterLink>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarGroup>
    </SidebarContent>
    <SidebarRail />
  </Sidebar>
</template>
