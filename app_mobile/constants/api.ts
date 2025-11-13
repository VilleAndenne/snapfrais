/**
 * API Configuration for SnapFrais Mobile App
 */

import Constants from 'expo-constants';

// Base URL for API
export const API_BASE_URL = process.env.EXPO_PUBLIC_API_URL || 'http://127.0.0.1:8000/api';

// Project ID from Expo configuration (app.json)
export const PROJECT_ID = Constants.expoConfig?.extra?.eas?.projectId || 'e2ed1a25-93e6-4f34-a0da-f09b883c3ae9';

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
  EXPENSE_SHEET_APPROVE: (id: number) => `/expense-sheets/${id}/approve`,
} as const;

// Storage Keys
export const STORAGE_KEYS = {
  AUTH_TOKEN: 'authToken',
  USER: 'user',
} as const;

// Request timeout in milliseconds
export const API_TIMEOUT = 30000; // 30 seconds
