export interface PaginationLinkItem {
  url: string | null;
  label: string;
  active: boolean;
}

export interface PaginatedResource<T> {
  data: T[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
    links: PaginationLinkItem[];
  };
}

export interface ResourceItem<T> {
  data: T;
}
