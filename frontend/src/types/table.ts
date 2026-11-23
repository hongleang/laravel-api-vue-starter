export interface DataTableColumn {
  key: string;
  header: string;
  sortable?: boolean;
  class?: string;
}

export interface DataTableSort {
  key: string;
  direction: 'asc' | 'desc';
}
