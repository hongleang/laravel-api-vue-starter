import { reactive } from 'vue';
import {
  HttpError,
  NetworkError,
  reportError,
  request,
  type Method,
  type RequestData
} from '@/lib/http';

/** First error message per field, keyed like Laravel: "email", "items.0.name". */
export type FormErrors<TForm> = Partial<Record<keyof TForm & string, string>> &
  Record<string, string | undefined>;

export interface VisitOptions<TResponse> {
  /** Return false to cancel the request. */
  onBefore?: () => boolean | void;
  onStart?: () => void;
  onSuccess?: (response: TResponse) => void | Promise<unknown>;
  /** Validation errors from a 422 response. */
  onError?: (errors: Record<string, string>) => void;
  /** Any other error response. Return false to skip the client-wide handling (toast, redirect). */
  onHttpException?: (error: HttpError) => boolean | void;
  /** The server could not be reached. Return false to skip the client-wide toast. */
  onNetworkError?: (error: NetworkError) => boolean | void;
  onCancel?: () => void;
  onFinish?: () => void;
  headers?: Record<string, string>;
  /** Reset these fields (or all, with true) after a successful request. */
  resetOnSuccess?: boolean | string[];
}

export interface FormProps<TForm extends object, TResponse> {
  errors: FormErrors<TForm>;
  hasErrors: boolean;
  processing: boolean;
  wasSuccessful: boolean;
  recentlySuccessful: boolean;
  isDirty: boolean;
  /** The parsed body of the last successful response. */
  response: TResponse | null;
  data(): TForm;
  transform(
    callback: (data: TForm) => Record<string, unknown> | FormData
  ): this;
  defaults(): this;
  defaults<K extends keyof TForm>(field: K, value: TForm[K]): this;
  defaults(fields: Partial<TForm>): this;
  reset(...fields: (keyof TForm)[]): this;
  clearErrors(...fields: string[]): this;
  resetAndClearErrors(...fields: (keyof TForm)[]): this;
  setError(field: string, message: string): this;
  setError(errors: Record<string, string>): this;
  submit(
    method: Method,
    url: string,
    options?: VisitOptions<TResponse>
  ): Promise<TResponse | undefined>;
  get(
    url: string,
    options?: VisitOptions<TResponse>
  ): Promise<TResponse | undefined>;
  post(
    url: string,
    options?: VisitOptions<TResponse>
  ): Promise<TResponse | undefined>;
  put(
    url: string,
    options?: VisitOptions<TResponse>
  ): Promise<TResponse | undefined>;
  patch(
    url: string,
    options?: VisitOptions<TResponse>
  ): Promise<TResponse | undefined>;
  delete(
    url: string,
    options?: VisitOptions<TResponse>
  ): Promise<TResponse | undefined>;
  cancel(): void;
}

export type UseForm<TForm extends object, TResponse = unknown> = TForm &
  FormProps<TForm, TResponse>;

const RECENTLY_SUCCESSFUL_MS = 2000;

function clone<T>(value: T): T {
  // Files and Blobs cannot be structured-cloned into fresh copies worth comparing; keep them.
  if (value instanceof Blob) {
    return value;
  }

  if (Array.isArray(value)) {
    return value.map(clone) as T;
  }

  if (value && typeof value === 'object' && !(value instanceof Date)) {
    return Object.fromEntries(
      Object.entries(value).map(([key, item]) => [key, clone(item)])
    ) as T;
  }

  return value;
}

function isEqual(a: unknown, b: unknown): boolean {
  if (a instanceof Blob || b instanceof Blob) {
    return a === b;
  }

  if (Array.isArray(a) && Array.isArray(b)) {
    return (
      a.length === b.length && a.every((item, index) => isEqual(item, b[index]))
    );
  }

  if (a && b && typeof a === 'object' && typeof b === 'object') {
    const keys = new Set([...Object.keys(a), ...Object.keys(b)]);

    return [...keys].every((key) =>
      isEqual(
        (a as Record<string, unknown>)[key],
        (b as Record<string, unknown>)[key]
      )
    );
  }

  return a === b;
}

/**
 * Form state and submission, with the same API as Inertia's useForm.
 *
 * Fields live directly on the returned object (`form.email`), so they work with v-model.
 * A 422 response fills `form.errors` with the first message per field, and other failures
 * go through the client-wide handlers in main.ts unless a callback returns false.
 *
 * @example
 * const form = useForm({ email: '', password: '' })
 * form.post('/api/login', { onSuccess: () => router.push({ name: 'dashboard' }) })
 */
export function useForm<TForm extends object, TResponse = unknown>(
  initial: TForm | (() => TForm)
): UseForm<TForm, TResponse> {
  const resolveInitial = (): TForm =>
    clone(typeof initial === 'function' ? (initial as () => TForm)() : initial);

  let defaults = resolveInitial();
  let transformer:
    ((data: TForm) => Record<string, unknown> | FormData) | null = null;
  let controller: AbortController | null = null;
  let recentlyTimer: ReturnType<typeof setTimeout> | null = null;

  const fieldKeys = Object.keys(defaults) as (keyof TForm)[];

  // Built on a plain object and returned as the typed proxy: reactive() would otherwise
  // unwrap the generic field types. Methods refer to `form` (the proxy, declared below and
  // only read once the methods run), so every read is tracked.
  const fields = (): TForm =>
    Object.fromEntries(
      fieldKeys.map((key) => [key, clone((form as TForm)[key])])
    ) as TForm;

  const methods: FormProps<TForm, TResponse> = {
    errors: {} as FormErrors<TForm>,
    processing: false,
    wasSuccessful: false,
    recentlySuccessful: false,
    response: null,

    get hasErrors(): boolean {
      return Object.keys(form.errors).length > 0;
    },

    get isDirty(): boolean {
      return !isEqual(fields(), defaults);
    },

    data: fields,

    transform(callback) {
      transformer = callback;

      return form;
    },

    defaults(fieldOrFields?: keyof TForm | Partial<TForm>, value?: unknown) {
      if (fieldOrFields === undefined) {
        defaults = fields();
      } else if (typeof fieldOrFields === 'object') {
        defaults = { ...defaults, ...clone(fieldOrFields) };
      } else {
        defaults = { ...defaults, [fieldOrFields]: clone(value) };
      }

      return form;
    },

    reset(...keys: (keyof TForm)[]) {
      for (const key of keys.length > 0 ? keys : fieldKeys) {
        (form as TForm)[key] = clone(defaults[key]);
      }

      return form;
    },

    clearErrors(...keys: string[]) {
      if (keys.length === 0) {
        form.errors = {} as FormErrors<TForm>;
      } else {
        const remaining: Record<string, string | undefined> = {
          ...form.errors
        };
        keys.forEach((key) => delete remaining[key]);
        form.errors = remaining as FormErrors<TForm>;
      }

      return form;
    },

    resetAndClearErrors(...keys: (keyof TForm)[]) {
      form.reset(...keys);
      form.clearErrors(...(keys as string[]));

      return form;
    },

    setError(fieldOrErrors: string | Record<string, string>, message?: string) {
      const added =
        typeof fieldOrErrors === 'string'
          ? { [fieldOrErrors]: message ?? '' }
          : fieldOrErrors;

      form.errors = { ...form.errors, ...added } as FormErrors<TForm>;

      return form;
    },

    async submit(method, url, options = {}) {
      if (options.onBefore?.() === false) {
        return undefined;
      }

      controller?.abort();
      controller = new AbortController();

      const data = fields();
      const payload = (transformer ? transformer(data) : data) as RequestData;

      form.processing = true;
      form.wasSuccessful = false;
      form.recentlySuccessful = false;
      options.onStart?.();

      try {
        const response = await request<TResponse>(method, url, {
          data: payload,
          headers: options.headers,
          signal: controller.signal,
          report: false
        });

        form.response = response;
        form.clearErrors();
        form.wasSuccessful = true;
        form.recentlySuccessful = true;

        if (recentlyTimer) {
          clearTimeout(recentlyTimer);
        }

        recentlyTimer = setTimeout(() => {
          form.recentlySuccessful = false;
        }, RECENTLY_SUCCESSFUL_MS);

        if (options.resetOnSuccess === true) {
          form.reset();
        } else if (Array.isArray(options.resetOnSuccess)) {
          form.reset(...(options.resetOnSuccess as (keyof TForm)[]));
        }

        await options.onSuccess?.(response);

        return response;
      } catch (error) {
        if (error instanceof DOMException && error.name === 'AbortError') {
          options.onCancel?.();

          return undefined;
        }

        if (error instanceof HttpError && error.isValidation) {
          form.errors = error.errors as FormErrors<TForm>;
          options.onError?.(error.errors);
        } else if (error instanceof HttpError) {
          if (options.onHttpException?.(error) !== false) {
            reportError(error);
          }
        } else if (error instanceof NetworkError) {
          if (options.onNetworkError?.(error) !== false) {
            reportError(error);
          }
        } else {
          throw error;
        }

        return undefined;
      } finally {
        form.processing = false;
        controller = null;
        options.onFinish?.();
      }
    },

    get(url, options) {
      return form.submit('get', url, options);
    },

    post(url, options) {
      return form.submit('post', url, options);
    },

    put(url, options) {
      return form.submit('put', url, options);
    },

    patch(url, options) {
      return form.submit('patch', url, options);
    },

    delete(url, options) {
      return form.submit('delete', url, options);
    },

    cancel() {
      controller?.abort();
    }
  };

  // Copy the getters as getters (a spread would evaluate them once).
  const target = Object.defineProperties(
    clone(defaults) as Record<string, unknown>,
    Object.getOwnPropertyDescriptors(methods)
  );

  const form = reactive(target) as unknown as UseForm<TForm, TResponse>;

  return form;
}
