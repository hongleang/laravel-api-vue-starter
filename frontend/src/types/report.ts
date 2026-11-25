/** One row of the user report, from GET /api/reports/users. */
export interface UserReportRow {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  mobile_phone: string | null;
  role: string;
  verified: boolean;
  created_at: string;
}
