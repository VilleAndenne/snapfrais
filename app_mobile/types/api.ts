/**
 * API Types for SnapFrais
 */

export interface User {
  id: number;
  name: string;
  email: string;
  is_admin: boolean;
  is_head: boolean;
  notify_expense_sheet_to_approval: boolean;
  notify_receipt_expense_sheet: boolean;
  notify_remind_approval: boolean;
  bank_account_number?: string;
  address?: string;
  created_at: string;
  updated_at: string;
  deleted_at?: string | null;
}

export interface Form {
  id: number;
  name: string;
  description: string;
  organization_id: number;
  created_at: string;
  updated_at: string;
  deleted_at?: string | null;
  costs?: FormCost[];
}

export interface FormCost {
  id: number;
  form_id: number;
  name: string;
  description: string;
  type: string;
  processing_department?: string | null;
  created_at: string;
  updated_at: string;
  reimbursementRates?: FormCostReimbursementRate[];
  requirements?: FormCostRequirement[];
}

export interface FormCostReimbursementRate {
  id: number;
  cost_id: number;
  start_date: string;
  end_date: string;
  value: number;
  transport: string;
}

export interface FormCostRequirement {
  id: number;
  form_cost_id: number;
  requirement_type: string;
  required: boolean;
}

export interface ExpenseSheet {
  id: number;
  form_id: number;
  user_id: number;
  distance?: number | null;
  route?: any[] | null;
  total?: number | null;
  status: string;
  validated_at?: string | null;
  validated_by?: number | null;
  approved?: boolean | null;
  refusal_reason?: string | null;
  department_id?: number | null;
  created_by?: number | null;
  is_draft: boolean;
  created_at: string;
  updated_at: string;
  deleted_at?: string | null;

  // Relations
  form?: Form;
  costs?: ExpenseSheetCost[];
  department?: Department;
  user?: User;
  validatedBy?: User;
}

export interface ExpenseSheetCost {
  id: number;
  expense_sheet_id: number;
  form_cost_id: number;
  type: string;
  distance?: number | null;
  google_distance?: number | null;
  route?: any[] | null;
  requirements?: any[] | null;
  total: number;
  amount: number;
  date: string;
  created_at: string;
  updated_at: string;
  form_cost?: FormCost;
}

export interface Department {
  id: number;
  name: string;
  parent_id?: number | null;
  created_at: string;
  updated_at: string;
  deleted_at?: string | null;
  users?: User[];
  heads?: User[];
  parent?: Department;
}

// API Response types
export interface LoginResponse {
  token: string;
  user: User;
}

export interface VerifyResponse {
  message: string;
  user: User;
}

export interface FormsResponse {
  forms: Form[];
}

export interface FormDetailsResponse {
  form: Form;
  departments: Department[];
}

export interface ExpenseSheetsResponse {
  expenseSheets: ExpenseSheet[];
}

export interface ExpenseSheetDetailsResponse {
  expenseSheet: ExpenseSheet;
  canApprove: boolean;
  canReject: boolean;
  canEdit: boolean;
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}
