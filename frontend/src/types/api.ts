import type { PaginatedResource } from './pagination';

/** A single API Resource: { data: T }, with any `additional` keys such as `message`. */
export interface ResourceResponse<T> {
  data: T;
  message?: string;
}

export interface MessageResponse {
  message: string;
}

export type PaginatedResponse<T, TFilters = unknown> = PaginatedResource<T> & {
  filters: TFilters;
};
