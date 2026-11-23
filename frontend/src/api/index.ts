/**
 * API endpoint URLs, in one place. The Vite dev server proxies /api and /sanctum to the
 * Laravel backend, so the SPA and API share an origin and Sanctum's session cookie works.
 */
export const api = {
  login: () => '/api/login',
  logout: () => '/api/logout',
  currentUser: () => '/api/user',
  forgotPassword: () => '/api/forgot-password',
  resetPassword: () => '/api/reset-password',
  verificationNotification: () => '/api/email/verification-notification',
  verifyEmail: (id: string, hash: string, query: string) =>
    `/api/email/verify/${encodeURIComponent(id)}/${encodeURIComponent(hash)}${query}`,
  profile: () => '/api/profile',
  profilePassword: () => '/api/profile/password',
  users: {
    index: () => '/api/users',
    store: () => '/api/users',
    show: (id: number | string) => `/api/users/${id}`,
    update: (id: number | string) => `/api/users/${id}`,
    destroy: (id: number | string) => `/api/users/${id}`
  }
} as const;
