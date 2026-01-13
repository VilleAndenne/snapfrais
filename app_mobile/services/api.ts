/**
 * API Service Layer for SnapFrais
 */

import * as SecureStore from 'expo-secure-store';
import { API_BASE_URL, API_ENDPOINTS, API_TIMEOUT, STORAGE_KEYS, PROJECT_ID } from '@/constants/api';
import type {
    LoginResponse,
    VerifyResponse,
    FormsResponse,
    FormDetailsResponse,
    ExpenseSheetsResponse,
    ExpenseSheetDetailsResponse,
    ApiError,
} from '@/types/api';
import * as Notifications from 'expo-notifications';
import { Platform } from 'react-native';

class ApiService {
    private baseURL: string;
    private projectId: string;

    constructor() {
        this.baseURL = API_BASE_URL;
        this.projectId = PROJECT_ID;

        // Debug logs pour identifier le problème
        console.log('=== API Service Initialization ===');
        console.log('API_BASE_URL from env:', process.env.EXPO_PUBLIC_API_URL);
        console.log('API_BASE_URL used:', this.baseURL);
        console.log('PROJECT_ID:', this.projectId);
        console.log('==================================');
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
            console.log(`[API Request] ${options.method || 'GET'} ${this.baseURL}${endpoint}`);

            const headers: Record<string, string> = {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...(options.headers as Record<string, string>),
            };

            if (token) {
                headers.Authorization = `Bearer ${token}`;
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
     * Register for push notifications and return Expo push token
     */
    private async registerForPushNotifications(): Promise<string | null> {
        try {
            // Vérifie les permissions existantes
            const { status: existingStatus } = await Notifications.getPermissionsAsync();
            let finalStatus = existingStatus;

            // Si pas encore autorisé → on demande au moment du login
            if (existingStatus !== 'granted') {
                const { status } = await Notifications.requestPermissionsAsync();
                finalStatus = status;
            }

            // Si refus → on arrête là
            if (finalStatus !== 'granted') {
                console.log('Permission de notification refusée');
                return null;
            }

            // Android nécessite un channel
            if (Platform.OS === 'android') {
                await Notifications.setNotificationChannelAsync('default', {
                    name: 'default',
                    importance: Notifications.AndroidImportance.MAX,
                });
            }

            // Récupère le token Expo
            const tokenResponse = await Notifications.getExpoPushTokenAsync({
                projectId: this.projectId,
            });

            return tokenResponse.data;
        } catch (error) {
            console.error('Erreur lors de la récupération du token de notification :', error);
            // On ne bloque pas le login si les notifs plantent
            return null;
        }
    }

    /**
     * Authentication API
     */
    async login(email: string, password: string): Promise<LoginResponse> {
        // Demande permission + token au moment du login
        const pushTokenString = await this.registerForPushNotifications();

        const response = await this.request<LoginResponse>(API_ENDPOINTS.LOGIN, {
            method: 'POST',
            body: JSON.stringify({
                email,
                password,
                push_token: pushTokenString, // peut être null si refusé
                platform: Platform.OS,
            }),
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
        console.log('========== createExpenseSheet START ==========');
        console.log('createExpenseSheet - formId:', formId);

        const token = await this.getToken();
        console.log(
            'createExpenseSheet - Token retrieved:',
            token ? `Present (length: ${token.length}, first 20 chars: ${token.substring(0, 20)}...)` : 'MISSING!!!'
        );

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), API_TIMEOUT);

        try {
            const url = `${this.baseURL}${API_ENDPOINTS.EXPENSE_SHEET_CREATE(formId)}`;
            console.log('createExpenseSheet - Full URL:', url);

            const headers: HeadersInit = {
                Accept: 'application/json',
            };

            if (token) {
                headers.Authorization = `Bearer ${token}`;
                console.log('createExpenseSheet - Authorization header SET successfully');
            } else {
                console.error('createExpenseSheet - ERROR: No token found! Cannot authenticate!');
            }

            console.log('createExpenseSheet - Headers keys:', Object.keys(headers));
            console.log('createExpenseSheet - Complete headers object:', JSON.stringify(headers, null, 2));

            const response = await fetch(
                `${this.baseURL}${API_ENDPOINTS.EXPENSE_SHEET_CREATE(formId)}`,
                {
                    method: 'POST',
                    headers,
                    body: data,
                    signal: controller.signal,
                }
            );

            console.log('createExpenseSheet - Request sent, waiting for response...');

            clearTimeout(timeoutId);

            console.log('createExpenseSheet - Response status:', response.status);

            if (!response.ok) {
                const errorText = await response.text();
                console.error('createExpenseSheet - Error response:', errorText);

                let error: ApiError;
                try {
                    error = JSON.parse(errorText);
                } catch {
                    error = { message: `HTTP Error ${response.status}: ${errorText}` };
                }
                throw new Error(error.message || `HTTP Error ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            clearTimeout(timeoutId);
            console.error('createExpenseSheet - Exception:', error);
            if (error instanceof Error) {
                if (error.name === 'AbortError') {
                    throw new Error('Request timeout');
                }
                throw error;
            }
            throw new Error('Unknown error occurred');
        }
    }

    async approveExpenseSheet(id: number, approval: boolean, reason?: string): Promise<any> {
        return this.request(API_ENDPOINTS.EXPENSE_SHEET_APPROVE(id), {
            method: 'POST',
            body: JSON.stringify({ approval, reason }),
        });
    }

    async updateExpenseSheet(id: number, data: { is_draft: boolean }): Promise<any> {
        return this.request(API_ENDPOINTS.EXPENSE_SHEET_DETAILS(id), {
            method: 'PATCH',
            body: JSON.stringify(data),
        });
    }

    async updateExpenseSheetFull(id: number, data: FormData): Promise<any> {
        console.log('========== updateExpenseSheetFull START ==========');
        console.log('updateExpenseSheetFull - id:', id);

        const token = await this.getToken();
        console.log(
            'updateExpenseSheetFull - Token retrieved:',
            token ? `Present (length: ${token.length})` : 'MISSING!!!'
        );

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), API_TIMEOUT);

        try {
            const url = `${this.baseURL}${API_ENDPOINTS.EXPENSE_SHEET_DETAILS(id)}`;
            console.log('updateExpenseSheetFull - Full URL:', url);

            const headers: HeadersInit = {
                Accept: 'application/json',
            };

            if (token) {
                headers.Authorization = `Bearer ${token}`;
                console.log('updateExpenseSheetFull - Authorization header SET successfully');
            } else {
                console.error('updateExpenseSheetFull - ERROR: No token found! Cannot authenticate!');
            }

            // Ajouter _method pour simuler PUT via POST (Laravel)
            data.append('_method', 'PUT');

            const response = await fetch(url, {
                method: 'POST', // Utilise POST avec _method=PUT pour supporter FormData
                headers,
                body: data,
                signal: controller.signal,
            });

            console.log('updateExpenseSheetFull - Request sent, waiting for response...');

            clearTimeout(timeoutId);

            console.log('updateExpenseSheetFull - Response status:', response.status);

            if (!response.ok) {
                const errorText = await response.text();
                console.error('updateExpenseSheetFull - Error response:', errorText);

                let error: ApiError;
                try {
                    error = JSON.parse(errorText);
                } catch {
                    error = { message: `HTTP Error ${response.status}: ${errorText}` };
                }
                throw new Error(error.message || `HTTP Error ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            clearTimeout(timeoutId);
            console.error('updateExpenseSheetFull - Exception:', error);
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
