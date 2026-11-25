<script setup lang="ts">
import { AlertCircle, Download } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter, type LocationQuery } from 'vue-router';

import { api } from '@/api';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select';
import { useHttp } from '@/composables/useHttp';
import { setLayoutProps } from '@/composables/usePage';
import type {
  PaginatedResponse,
  Role,
  UserReportRow
} from '@/types';

defineOptions({ name: 'UserReportPage' });

setLayoutProps({
  breadcrumbs: [
    { title: 'Dashboard', href: { name: 'dashboard' } },
    { title: 'User report' }
  ]
});

const route = useRoute();
const router = useRouter();

const ALL = 'all';

function queryString(query: LocationQuery, key: string): string {
  const value = query[key];

  return typeof value === 'string' ? value : '';
}

const report = useHttp<
  { from: string; to: string; role: string; per_page: number; page: number },
  PaginatedResponse<UserReportRow>
>(() => ({
  from: queryString(route.query, 'from'),
  to: queryString(route.query, 'to'),
  role: queryString(route.query, 'role'),
  per_page: Number(queryString(route.query, 'per_page')) || 10,
  page: Number(queryString(route.query, 'page')) || 1
}));

const loadError = ref<string | null>(null);
const hasFilters = computed(
  () => report.from !== '' || report.to !== '' || report.role !== ''
);

const roleFilter = computed({
  get: () => report.role || ALL,
  set: (value: string) => {
    report.role = value === ALL ? '' : value;
  }
});

/** The CSV download carries the same filters, minus paging. */
const exportUrl = computed(() => {
  const params = new URLSearchParams();

  if (report.from) params.set('from', report.from);
  if (report.to) params.set('to', report.to);
  if (report.role) params.set('role', report.role);

  const query = params.toString();

  return api.reports.usersExport() + (query ? `?${query}` : '');
});

/** Keep the filters in the URL, so reloads and links keep the view. */
function syncQuery(): void {
  const query: Record<string, string> = {};

  if (report.from) query.from = report.from;
  if (report.to) query.to = report.to;
  if (report.role) query.role = report.role;
  if (report.per_page !== 10) query.per_page = String(report.per_page);
  if (report.page > 1) query.page = String(report.page);

  void router.replace({ query });
}

function load(): void {
  loadError.value = null;
  syncQuery();

  void report.get(api.reports.users(), {
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

watch(
  () => [report.from, report.to, report.role],
  () => {
    report.page = 1;
    load();
  }
);

function resetFilters(): void {
  report.from = '';
  report.to = '';
  report.role = '';
}

const roleOptions: { value: Role; label: string }[] = [
  { value: 'admin', label: 'Admin' },
  { value: 'user', label: 'User' }
];
</script>

<template>
  <PageHeader
    title="User report"
    description="Filter users by sign-up date and role, then download the result as CSV."
  >
    <template #actions>
      <Button as-child>
        <a :href="exportUrl" download>
          <Download aria-hidden="true" />
          Download CSV
        </a>
      </Button>
    </template>
  </PageHeader>

  <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
    <div class="flex flex-col gap-1.5 sm:w-44">
      <label for="report-from" class="text-sm font-medium">Created from</label>
      <Input id="report-from" v-model="report.from" type="date" />
    </div>
    <div class="flex flex-col gap-1.5 sm:w-44">
      <label for="report-to" class="text-sm font-medium">Created to</label>
      <Input id="report-to" v-model="report.to" type="date" />
    </div>
    <div class="flex flex-col gap-1.5 sm:w-44">
      <label for="report-role" class="text-sm font-medium">Role</label>
      <Select v-model="roleFilter">
        <SelectTrigger id="report-role" class="w-full">
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
</template>
