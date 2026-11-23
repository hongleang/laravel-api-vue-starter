/**
 * A small fetch client for the Laravel API, shaped after how Inertia talks to Laravel:
 * session cookies (Sanctum), the XSRF-TOKEN header, JSON or FormData bodies, and
 * Laravel's error formats. Pages use it through useForm / useHttp rather than directly.
 */

export type Method = 'get' | 'post' | 'put' | 'patch' | 'delete';

export type RequestData = Record<string, unknown> | FormData | null | undefined;

export interface RequestOptions {
  data?: RequestData;
  headers?: Record<string, string>;
  signal?: AbortSignal;
  /**
   * Emit the client-wide unauthorized / httpException / networkError events on failure
   * (toasts, sign-in redirect). useForm turns this off and reports after its own callbacks.
   */
  report?: boolean;
}

/** Field errors as Laravel returns them: { "email": ["The email field is required."] }. */
export type LaravelErrors = Record<string, string[]>;

export class HttpError extends Error {
  readonly status: number;
  readonly body: unknown;
  /** First message per field, present on 422 responses. */
  readonly errors: Record<string, string>;

  constructor(status: number, body: unknown) {
    super(messageFrom(body) ?? `Request failed with status ${status}`);
    this.name = 'HttpError';
    this.status = status;
    this.body = body;
    this.errors = status === 422 ? flattenErrors(body) : {};
  }

  get isValidation(): boolean {
    return this.status === 422;
  }
}

export class NetworkError extends Error {
  constructor(cause: unknown) {
    super('Could not reach the server. Check your connection.');
    this.name = 'NetworkError';
    this.cause = cause;
  }
}

type Listener<T> = (payload: T) => void;

interface HttpEvents {
  /** A 401: the session ended or the user never signed in. */
  unauthorized: HttpError;
  /** Any other non-validation error response (403, 404, 419, 429, 5xx). */
  httpException: HttpError;
  networkError: NetworkError;
  /** A successful response, with its parsed body. */
  success: { method: Method; url: string; body: unknown };
}

const listeners: { [K in keyof HttpEvents]: Set<Listener<HttpEvents[K]>> } = {
  unauthorized: new Set(),
  httpException: new Set(),
  networkError: new Set(),
  success: new Set()
};

/**
 * Listen for client-wide events, like Inertia's router.on(). Returns an unsubscribe function.
 */
export function on<K extends keyof HttpEvents>(
  event: K,
  listener: Listener<HttpEvents[K]>
): () => void {
  listeners[event].add(listener);

  return () => listeners[event].delete(listener);
}

function emit<K extends keyof HttpEvents>(
  event: K,
  payload: HttpEvents[K]
): void {
  listeners[event].forEach((listener) => listener(payload));
}

function messageFrom(body: unknown): string | null {
  if (body && typeof body === 'object' && 'message' in body) {
    const { message } = body as { message: unknown };

    return typeof message === 'string' && message !== '' ? message : null;
  }

  return null;
}

function flattenErrors(body: unknown): Record<string, string> {
  if (!body || typeof body !== 'object' || !('errors' in body)) {
    return {};
  }

  const errors = (body as { errors: LaravelErrors }).errors ?? {};

  return Object.fromEntries(
    Object.entries(errors).map(([field, messages]) => [
      field,
      Array.isArray(messages) ? (messages[0] ?? '') : String(messages)
    ])
  );
}

function readCookie(name: string): string | null {
  const match = document.cookie.match(
    new RegExp(
      `(?:^|; )${name.replace(/[.$?*|{}()[\]\\/+^]/g, '\\$&')}=([^;]*)`
    )
  );

  return match ? decodeURIComponent(match[1]) : null;
}

let csrfRequest: Promise<void> | null = null;

/**
 * Fetch the XSRF-TOKEN cookie Sanctum uses for CSRF protection, once per page load.
 */
export function ensureCsrfCookie(force = false): Promise<void> {
  if (!force && readCookie('XSRF-TOKEN')) {
    return Promise.resolve();
  }

  csrfRequest ??= fetch('/sanctum/csrf-cookie', {
    credentials: 'include',
    headers: { Accept: 'application/json' }
  })
    .then(() => undefined)
    .finally(() => {
      csrfRequest = null;
    });

  return csrfRequest;
}

function hasFiles(value: unknown): boolean {
  if (value instanceof Blob) {
    return true;
  }

  if (Array.isArray(value)) {
    return value.some(hasFiles);
  }

  if (value && typeof value === 'object') {
    return Object.values(value).some(hasFiles);
  }

  return false;
}

/**
 * Convert nested data to FormData with Laravel's bracket keys, like Inertia's objectToFormData:
 * { items: [{ name: 'a', photos: [File] }] } becomes items[0][name], items[0][photos][0].
 */
export function toFormData(
  data: Record<string, unknown>,
  form: FormData = new FormData(),
  parentKey?: string
): FormData {
  for (const [key, value] of Object.entries(data)) {
    const name = parentKey ? `${parentKey}[${key}]` : key;
    appendValue(form, name, value);
  }

  return form;
}

function appendValue(form: FormData, name: string, value: unknown): void {
  if (value === undefined) {
    return;
  }

  if (value instanceof Blob) {
    form.append(name, value);
  } else if (Array.isArray(value)) {
    value.forEach((item, index) =>
      appendValue(form, `${name}[${index}]`, item)
    );
  } else if (value instanceof Date) {
    form.append(name, value.toISOString());
  } else if (value !== null && typeof value === 'object') {
    toFormData(value as Record<string, unknown>, form, name);
  } else if (typeof value === 'boolean') {
    form.append(name, value ? '1' : '0');
  } else {
    form.append(name, value === null ? '' : String(value));
  }
}

function toQueryString(data: Record<string, unknown>): string {
  const params = new URLSearchParams();

  for (const [key, value] of Object.entries(data)) {
    if (value === undefined || value === null || value === '') {
      continue;
    }

    if (Array.isArray(value)) {
      value.forEach((item) => params.append(`${key}[]`, String(item)));
    } else {
      params.append(key, String(value));
    }
  }

  const query = params.toString();

  return query ? `?${query}` : '';
}

async function parseBody(response: Response): Promise<unknown> {
  if (response.status === 204) {
    return null;
  }

  const text = await response.text();

  if (text === '') {
    return null;
  }

  try {
    return JSON.parse(text);
  } catch {
    return text;
  }
}

/**
 * Send a request to the API. Resolves with the parsed body; rejects with HttpError for error
 * responses or NetworkError when the server cannot be reached. A 419 (expired CSRF token) is
 * retried once with a fresh token.
 */
export async function request<T = unknown>(
  method: Method,
  url: string,
  options: RequestOptions = {},
  retried = false
): Promise<T> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    ...options.headers
  };

  let target = url;
  let body: BodyInit | undefined;
  let fetchMethod = method.toUpperCase();
  const { data } = options;

  if (method === 'get') {
    if (data && !(data instanceof FormData)) {
      target += toQueryString(data);
    }
  } else {
    await ensureCsrfCookie();

    const token = readCookie('XSRF-TOKEN');

    if (token) {
      headers['X-XSRF-TOKEN'] = token;
    }

    if (data instanceof FormData || (data && hasFiles(data))) {
      body = data instanceof FormData ? data : toFormData(data);

      // PHP only parses multipart bodies on POST, so spoof PUT/PATCH/DELETE like Laravel forms do.
      if (method !== 'post') {
        (body as FormData).append('_method', fetchMethod);
        fetchMethod = 'POST';
      }
    } else if (data) {
      headers['Content-Type'] = 'application/json';
      body = JSON.stringify(data);
    }
  }

  let response: Response;

  try {
    response = await fetch(target, {
      method: fetchMethod,
      headers,
      body,
      credentials: 'include',
      signal: options.signal
    });
  } catch (cause) {
    if (cause instanceof DOMException && cause.name === 'AbortError') {
      throw cause;
    }

    const error = new NetworkError(cause);

    if (options.report !== false) {
      reportError(error);
    }

    throw error;
  }

  const parsed = await parseBody(response);

  if (response.ok) {
    emit('success', { method, url, body: parsed });

    return parsed as T;
  }

  if (response.status === 419 && !retried) {
    await ensureCsrfCookie(true);

    return request<T>(method, url, options, true);
  }

  const error = new HttpError(response.status, parsed);

  if (options.report !== false) {
    reportError(error);
  }

  throw error;
}

/**
 * Send a failure to the client-wide listeners. Validation errors (422) are not reported:
 * they belong to the form that made the request.
 */
export function reportError(error: HttpError | NetworkError): void {
  if (error instanceof NetworkError) {
    emit('networkError', error);
  } else if (error.status === 401) {
    emit('unauthorized', error);
  } else if (!error.isValidation) {
    emit('httpException', error);
  }
}

export const http = {
  get: <T = unknown>(url: string, options?: RequestOptions) =>
    request<T>('get', url, options),
  post: <T = unknown>(url: string, options?: RequestOptions) =>
    request<T>('post', url, options),
  put: <T = unknown>(url: string, options?: RequestOptions) =>
    request<T>('put', url, options),
  patch: <T = unknown>(url: string, options?: RequestOptions) =>
    request<T>('patch', url, options),
  delete: <T = unknown>(url: string, options?: RequestOptions) =>
    request<T>('delete', url, options)
};
