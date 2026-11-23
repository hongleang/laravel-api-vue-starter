import { useForm, type UseForm } from '@/composables/useForm';

/**
 * Standalone requests with the same state and callbacks as useForm, like Inertia's useHttp:
 * for loading data (search, availability, lookups) rather than submitting a form.
 * Request data lives on the object, and the parsed body is kept on `response`.
 *
 * @example
 * const search = useHttp<{ search: string }, Paginated<User>>({ search: '' })
 * search.search = 'ada'
 * await search.get('/api/users')
 * search.response?.data
 */
export function useHttp<
  TData extends object = Record<string, never>,
  TResponse = unknown
>(initial: TData | (() => TData) = {} as TData): UseForm<TData, TResponse> {
  return useForm<TData, TResponse>(initial);
}
