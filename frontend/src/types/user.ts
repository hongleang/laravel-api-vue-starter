import type { Role } from './auth';

export interface UserRecord {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  mobile_phone: string | null;
  email_verified_at: string | null;
  created_at: string | null;
  roles?: Role[];
  can: {
    update: boolean;
    delete: boolean;
  };
}

export interface UserFormData {
  first_name: string;
  last_name: string;
  mobile_phone: string;
  email: string;
  role: Role;
}
