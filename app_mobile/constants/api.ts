/**
 * API Configuration for SnapFrais Mobile App
 */

// Base URL for API
export const API_BASE_URL = __DEV__
  ? 'http://127.0.0.1:8000/api'  // Development
  : 'https://your-production-domain.com/api';  // Production

// API Endpoints
export const API_ENDPOINTS = {
  // Authentication
  LOGIN: '/login',
  VERIFY: '/verify',

  // Forms
  FORMS: '/forms',
  FORM_DETAILS: (id: number) => `/forms/${id}`,

  // Expense Sheets
  EXPENSE_SHEETS: '/expense-sheets',
  EXPENSE_SHEETS_ALL: '/expense-sheets/all',
  EXPENSE_SHEETS_VALIDATE: '/expense-sheets/validate',
  EXPENSE_SHEET_DETAILS: (id: number) => `/expense-sheets/${id}`,
  EXPENSE_SHEET_CREATE: (formId: number) => `/expense-sheet/${formId}`,
} as const;

// Storage Keys
export const STORAGE_KEYS = {
  AUTH_TOKEN: 'authToken',
  USER: 'user',
} as const;

// Request timeout in milliseconds
export const API_TIMEOUT = 30000; // 30 seconds
