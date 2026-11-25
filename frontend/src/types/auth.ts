export type Role = 'admin' | 'user';

export type Permission =
  | 'users.view'
  | 'users.create'
  | 'users.update'
  | 'users.delete'
  | 'reports.view';

/** The signed-in user, from GET /api/user. */
export interface CurrentUser {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  mobile_phone: string | null;
  email_verified_at: string | null;
  roles: Role[];
  permissions: Permission[];
}

export interface Auth {
  user: CurrentUser | null;
}

export interface Flash {
  success?: string | null;
  error?: string | null;
}

/** Shared state every page can read through usePage(), like Inertia's shared props. */
export interface SharedProps {
  name: string;
  auth: Auth;
  flash: Flash;
}
