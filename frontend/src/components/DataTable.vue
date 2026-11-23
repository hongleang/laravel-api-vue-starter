<script setup lang="ts" generic="T">
import { ArrowDown, ArrowUp, ArrowUpDown, Loader2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table';
import type { DataTableColumn, DataTableSort } from '@/types';

defineOptions({ name: 'DataTable' });

const props = withDefaults(
  defineProps<{
    columns: DataTableColumn[];
    rows: T[];
    rowKey: (row: T) => string | number;
    loading?: boolean;
    sort?: DataTableSort | null;
    caption?: string;
  }>(),
  { loading: false, sort: null, caption: '' }
);

const emit = defineEmits<{
  sort: [key: string];
}>();

defineSlots<{
  cell(props: { row: T; column: DataTableColumn }): unknown;
  empty(): unknown;
}>();

function ariaSort(
  column: DataTableColumn
): 'ascending' | 'descending' | 'none' | undefined {
  if (!column.sortable) {
    return undefined;
  }

  if (props.sort?.key !== column.key) {
    return 'none';
  }

  return props.sort.direction === 'asc' ? 'ascending' : 'descending';
}
</script>

<template>
  <div class="bg-card relative rounded-lg border" :aria-busy="loading">
    <Table>
      <caption v-if="caption" class="sr-only">
        {{
          caption
        }}
      </caption>
      <TableHeader>
        <TableRow>
          <TableHead
            v-for="column in columns"
            :key="column.key"
            :class="column.class"
            class="first:ps-4 last:pe-4"
            :aria-sort="ariaSort(column)"
          >
            <Button
              v-if="column.sortable"
              type="button"
              variant="ghost"
              size="sm"
              class="-ml-3"
              @click="emit('sort', column.key)"
            >
              {{ column.header }}
              <ArrowUp
                v-if="sort?.key === column.key && sort.direction === 'asc'"
                aria-hidden="true"
              />
              <ArrowDown
                v-else-if="sort?.key === column.key"
                aria-hidden="true"
              />
              <ArrowUpDown v-else class="opacity-50" aria-hidden="true" />
            </Button>
            <template v-else>{{ column.header }}</template>
          </TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        <TableRow v-if="rows.length === 0">
          <TableCell
            :colspan="columns.length"
            class="text-muted-foreground h-24 text-center"
          >
            <slot name="empty">No results.</slot>
          </TableCell>
        </TableRow>
        <template v-else>
          <TableRow v-for="row in rows" :key="rowKey(row)">
            <TableCell
              v-for="column in columns"
              :key="column.key"
              :class="column.class"
              class="first:ps-4 last:pe-4"
            >
              <slot name="cell" :row="row" :column="column" />
            </TableCell>
          </TableRow>
        </template>
      </TableBody>
    </Table>
    <div
      v-if="loading"
      role="status"
      class="bg-background/60 absolute inset-0 flex items-center justify-center rounded-lg"
    >
      <Loader2 class="text-primary size-6 animate-spin" aria-hidden="true" />
      <span class="sr-only">Loading</span>
    </div>
  </div>
</template>
