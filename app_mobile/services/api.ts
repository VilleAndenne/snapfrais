/**
 * API Service Layer for SnapFrais
 */

import * as SecureStore from 'expo-secure-store';
import { API_BASE_URL, API_ENDPOINTS, API_TIMEOUT, STORAGE_KEYS } from '@/constants/api';
import type {
  LoginResponse,
  VerifyResponse,
  FormsResponse,
  FormDetailsResponse,
  ExpenseSheetsResponse,
  ExpenseSheetDetailsResponse,
  ApiError,
} from '@/types/api';

class ApiService {
  private baseURL: string;

  constructor() {
    this.baseURL = API_BASE_URL;
  }

  /**
   * Get auth token from secure storage
   */
  private async getToken(): Promise<string | null> {
    try {
      return await SecureStore.getItemAsync(STORAGE_KEYS.AUTH_TOKEN);
    } catch (error) {
      console.error('Error getting auth token:', error);
      return null;
    }
  }

  /**
   * Make authenticated API request
   */
  private async request<T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<T> {
    const token = await this.getToken();
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), API_TIMEOUT);

    try {
      const headers: HeadersInit = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...options.headers,
      };

      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }

      const response = await fetch(`${this.baseURL}${endpoint}`, {
        ...options,
        headers,
        signal: controller.signal,
      });

      clearTimeout(timeoutId);

      if (!response.ok) {
        const error: ApiError = await response.json().catch(() => ({
          message: `HTTP Error ${response.status}`,
        }));
        throw new Error(error.message || `HTTP Error ${response.status}`);
      }

      return await response.json();
    } catch (error) {
      clearTimeout(timeoutId);
      if (error instanceof Error) {
        if (error.name === 'AbortError') {
          throw new Error('Request timeout');
        }
        throw error;
      }
      throw new Error('Unknown error occurred');
    }
  }

  /**
   * Authentication API
   */
  async login(email: string, password: string): Promise<LoginResponse> {
    const response = await this.request<LoginResponse>(API_ENDPOINTS.LOGIN, {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });

    // Store token securely
    if (response.token) {
      await SecureStore.setItemAsync(STORAGE_KEYS.AUTH_TOKEN, response.token);
    }

    return response;
  }

  async verify(): Promise<VerifyResponse> {
    return this.request<VerifyResponse>(API_ENDPOINTS.VERIFY);
  }

  async logout(): Promise<void> {
    await SecureStore.deleteItemAsync(STORAGE_KEYS.AUTH_TOKEN);
  }

  /**
   * Forms API
   */
  async getForms(): Promise<FormsResponse> {
    return this.request<FormsResponse>(API_ENDPOINTS.FORMS);
  }

  async getFormDetails(id: number): Promise<FormDetailsResponse> {
    return this.request<FormDetailsResponse>(API_ENDPOINTS.FORM_DETAILS(id));
  }

  /**
   * Expense Sheets API
   */
  async getExpenseSheets(): Promise<ExpenseSheetsResponse> {
    return this.request<ExpenseSheetsResponse>(API_ENDPOINTS.EXPENSE_SHEETS);
  }

  async getAllExpenseSheets(): Promise<ExpenseSheetsResponse> {
    return this.request<ExpenseSheetsResponse>(API_ENDPOINTS.EXPENSE_SHEETS_ALL);
  }

  async getExpenseSheetsToValidate(): Promise<ExpenseSheetsResponse> {
    return this.request<ExpenseSheetsResponse>(API_ENDPOINTS.EXPENSE_SHEETS_VALIDATE);
  }

  async getExpenseSheetDetails(id: number): Promise<ExpenseSheetDetailsResponse> {
    return this.request<ExpenseSheetDetailsResponse>(
      API_ENDPOINTS.EXPENSE_SHEET_DETAILS(id)
    );
  }

  async createExpenseSheet(formId: number, data: FormData): Promise<any> {
    const token = await this.getToken();
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), API_TIMEOUT);

    try {
      const headers: HeadersInit = {
        'Accept': 'application/json',
      };

      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }

      const response = await fetch(
        `${this.baseURL}${API_ENDPOINTS.EXPENSE_SHEET_CREATE(formId)}`,
        {
          method: 'POST',
          headers,
          body: data,
          signal: controller.signal,
        }
      );

      clearTimeout(timeoutId);

      if (!response.ok) {
        const error: ApiError = await response.json().catch(() => ({
          message: `HTTP Error ${response.status}`,
        }));
        throw new Error(error.message || `HTTP Error ${response.status}`);
      }

      return await response.json();
    } catch (error) {
      clearTimeout(timeoutId);
      if (error instanceof Error) {
        if (error.name === 'AbortError') {
          throw new Error('Request timeout');
        }
        throw error;
      }
      throw new Error('Unknown error occurred');
    }
  }
}

// Export singleton instance
export const api = new ApiService();
