<script setup lang="ts">
import { AlertCircle, Plus } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, ref, toRef, watch } from 'vue';
import { useRoute, useRouter, type LocationQuery } from 'vue-router';

import { api } from '@/api';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationNav from '@/components/PaginationNav.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select';
import { useDelayedFlag } from '@/composables/useDelayedFlag';
import { useForm } from '@/composables/useForm';
import { useHttp } from '@/composables/useHttp';
import { setLayoutProps } from '@/composables/usePage';
import { useAuthStore } from '@/stores/auth';
import type {
  DataTableColumn,
  DataTableSort,
  PaginatedResponse,
  Role,
  UserRecord
} from '@/types';

defineOptions({ name: 'UsersIndexPage' });

setLayoutProps({
  breadcrumbs: [
    { title: 'Dashboard', href: { name: 'dashboard' } },
    { title: 'Users' }
  ]
});

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const ALL = 'all';

const columns: DataTableColumn[] = [
  { key: 'first_name', header: 'First name', sortable: true },
  { key: 'last_name', header: 'Last name', sortable: true },
  { key: 'email', header: 'Email', sortable: true },
  { key: 'roles', header: 'Role' },
  { key: 'actions', header: 'Actions', class: 'text-right' }
];

function queryString(query: LocationQuery, key: string): string {
  const value = query[key];

  return typeof value === 'string' ? value : '';
}

/** The list request. Its data are the filters, sent as the query string. */
const list = useHttp<
  {
    search: string;
    role: string;
    sort: string;
    direction: 'asc' | 'desc';
    per_page: number;
    page: number;
  },
  PaginatedResponse<UserRecord>
>(() => ({
  search: queryString(route.query, 'search'),
  role: queryString(route.query, 'role'),
  sort: queryString(route.query, 'sort') || 'first_name',
  direction: queryString(route.query, 'direction') === 'desc' ? 'desc' : 'asc',
  per_page: Number(queryString(route.query, 'per_page')) || 10,
  page: Number(queryString(route.query, 'page')) || 1
}));

/** The API uses Spatie Query Builder's format: filter[search], filter[role] and sort=-column. */
list.transform((data) => ({
  'filter[search]': data.search,
  'filter[role]': data.role,
  sort: `${data.direction === 'desc' ? '-' : ''}${data.sort}`,
  per_page: data.per_page,
  page: data.page
}));

const loadError = ref<string | null>(null);
const showLoader = useDelayedFlag(toRef(list, 'processing'), 300);

const users = computed(() => list.response?.data ?? []);
const hasFilters = computed(() => list.search !== '' || list.role !== '');
const sort = computed<DataTableSort>(() => ({
  key: list.sort,
  direction: list.direction
}));

const roleFilter = computed({
  get: () => list.role || ALL,
  set: (value: string) => {
    list.role = value === ALL ? '' : value;
  }
});

/** Keep the filters in the URL, so reloads, links and back/forward keep the view. */
function syncQuery(): void {
  const query: Record<string, string> = {};

  if (list.search) query.search = list.search;
  if (list.role) query.role = list.role;
  if (list.sort !== 'first_name') query.sort = list.sort;
  if (list.direction !== 'asc') query.direction = list.direction;
  if (list.per_page !== 10) query.per_page = String(list.per_page);
  if (list.page > 1) query.page = String(list.page);

  void router.replace({ query });
}

function load(): void {
  loadError.value = null;
  syncQuery();

  void list.get(api.users.index(), {
    onError: (errors) => {
      loadError.value =
        Object.values(errors)[0] ?? 'Some filters were not valid.';
    },
    onHttpException: (error) => {
      loadError.value = error.message;

      return false;
    },
    onNetworkError: (error) => {
      loadError.value = error.message;

      return false;
    }
  });
}

load();

watchDebounced(
  () => list.search,
  () => {
    list.page = 1;
    load();
  },
  { debounce: 300 }
);

watch(
  () => list.role,
  () => {
    list.page = 1;
    load();
  }
);

function toggleSort(key: string): void {
  list.direction =
    list.sort === key && list.direction === 'asc' ? 'desc' : 'asc';
  list.sort = key;
  list.page = 1;
  load();
}

/** Pagination links come from Laravel; only the page number matters here. */
function goToPage(url: string): void {
  list.page =
    Number(new URL(url, window.location.origin).searchParams.get('page')) || 1;
  load();
}

function resetFilters(): void {
  list.search = '';
  list.role = '';
  list.page = 1;
  load();
}

const userToDelete = ref<UserRecord | null>(null);
const confirmOpen = ref(false);
const deleteForm = useForm({});

function askToDelete(user: UserRecord): void {
  userToDelete.value = user;
  confirmOpen.value = true;
}

function confirmDelete(): void {
  const user = userToDelete.value;

  if (!user) {
    return;
  }

  deleteForm.delete(api.users.destroy(user.id), {
    onSuccess: () => {
      load();
    },
    onFinish: () => {
      confirmOpen.value = false;
    }
  });
}

function cellText(row: UserRecord, column: DataTableColumn): string {
  return String(row[column.key as keyof UserRecord] ?? '');
}

function rowKey(row: UserRecord): number {
  return row.id;
}

const roleOptions: { value: Role; label: string }[] = [
  { value: 'admin', label: 'Admin' },
  { value: 'user', label: 'User' }
];
</script>

<template>
  <PageHeader title="Users" description="Manage the people who can sign in.">
    <template v-if="auth.can('users.create')" #actions>
      <Button as-child>
        <RouterLink :to="{ name: 'users.create' }">
          <Plus aria-hidden="true" />
          New user
        </RouterLink>
      </Button>
    </template>
  </PageHeader>

  <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
    <div class="sm:max-w-sm sm:flex-1">
      <label for="user-search" class="sr-only">Search users</label>
      <Input
        id="user-search"
        v-model="list.search"
        type="search"
        placeholder="Search by name or email"
      />
    </div>
    <div class="sm:w-44">
      <label for="user-role" class="sr-only">Role</label>
      <Select v-model="roleFilter">
        <SelectTrigger id="user-role" class="w-full">
          <SelectValue placeholder="All roles" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem :value="ALL">All roles</SelectItem>
          <SelectItem
            v-for="option in roleOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>
    <Button v-if="hasFilters" variant="ghost" @click="resetFilters">
      Reset
    </Button>
  </div>

  <div
    v-if="loadError"
    role="alert"
    class="border-destructive/50 text-destructive flex items-center gap-3 rounded-lg border p-4 text-sm"
  >
    <AlertCircle class="size-4 shrink-0" aria-hidden="true" />
    <span class="flex-1">{{ loadError }}</span>
    <Button
      variant="outline"
      size="sm"
      @click="hasFilters ? resetFilters() : load()"
    >
      {{ hasFilters ? 'Reset filters' : 'Try again' }}
    </Button>
  </div>

  <DataTable
    caption="Users"
    :columns="columns"
    :rows="users"
    :row-key="rowKey"
    :loading="showLoader || (list.processing && !list.response)"
    :sort="sort"
    @sort="toggleSort"
  >
    <template #cell="{ row, column }">
      <div v-if="column.key === 'actions'" class="flex justify-end gap-1">
        <Button variant="ghost" size="sm" as-child>
          <RouterLink :to="{ name: 'users.show', params: { id: row.id } }">
            View
          </RouterLink>
        </Button>
        <Button v-if="row.can.update" variant="ghost" size="sm" as-child>
          <RouterLink :to="{ name: 'users.edit', params: { id: row.id } }">
            Edit
          </RouterLink>
        </Button>
        <Button
          v-if="row.can.delete"
          variant="ghost"
          size="sm"
          @click="askToDelete(row)"
        >
          Delete
        </Button>
      </div>
      <div v-else-if="column.key === 'roles'" class="flex gap-1">
        <Badge v-for="role in row.roles" :key="role" variant="secondary">
          {{ role }}
        </Badge>
      </div>
      <template v-else>{{ cellText(row, column) }}</template>
    </template>
    <template #empty>
      {{
        hasFilters
          ? 'No users match these filters.'
          : 'No users yet. Create the first one.'
      }}
    </template>
  </DataTable>

  <PaginationNav
    v-if="list.response"
    :meta="list.response.meta"
    :links="list.response.links"
    :disabled="list.processing"
    @navigate="goToPage"
  />

  <ConfirmDialog
    v-model:open="confirmOpen"
    title="Delete this user?"
    :description="
      userToDelete
        ? `${userToDelete.first_name} ${userToDelete.last_name} will be permanently deleted. This cannot be undone.`
        : ''
    "
    confirm-label="Delete"
    destructive
    :processing="deleteForm.processing"
    @confirm="confirmDelete"
  />
</template>
