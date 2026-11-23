<script setup lang="ts">
import { ChevronsUpDown, LogOut, Settings } from '@lucide/vue';
import { computed } from 'vue';
import { useRouter } from 'vue-router';

import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from '@/components/ui/dropdown-menu';
import { usePage } from '@/composables/usePage';
import { useAuthStore } from '@/stores/auth';

defineOptions({ name: 'UserMenu' });

const page = usePage();
const auth = useAuthStore();
const router = useRouter();

const user = computed(() => page.props.auth.user);

const fullName = computed(() =>
  [user.value?.first_name, user.value?.last_name].filter(Boolean).join(' ')
);

const initials = computed(() =>
  `${user.value?.first_name?.charAt(0) ?? ''}${user.value?.last_name?.charAt(0) ?? ''}`.toUpperCase()
);

async function logout(): Promise<void> {
  await auth.logout();
  await router.push({ name: 'login' });
}
</script>

<template>
  <DropdownMenu v-if="user">
    <DropdownMenuTrigger
      class="hover:bg-accent focus-visible:ring-ring flex items-center gap-2 rounded-md p-1 pr-2 text-sm outline-none focus-visible:ring-2"
      aria-label="Open user menu"
    >
      <Avatar class="size-8">
        <AvatarFallback>{{ initials }}</AvatarFallback>
      </Avatar>
      <span class="hidden max-w-40 truncate font-medium sm:inline">
        {{ fullName }}
      </span>
      <ChevronsUpDown class="text-muted-foreground size-4" aria-hidden="true" />
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="w-56">
      <DropdownMenuLabel class="flex flex-col">
        <span class="truncate font-medium">{{ fullName }}</span>
        <span class="text-muted-foreground truncate text-xs font-normal">
          {{ user.email }}
        </span>
      </DropdownMenuLabel>
      <DropdownMenuSeparator />
      <DropdownMenuItem as-child>
        <RouterLink
          :to="{ name: 'profile.edit' }"
          class="w-full cursor-pointer"
        >
          <Settings class="size-4" aria-hidden="true" />
          Settings
        </RouterLink>
      </DropdownMenuItem>
      <DropdownMenuItem class="cursor-pointer" @select="logout">
        <LogOut class="size-4" aria-hidden="true" />
        Log out
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
