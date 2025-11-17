import {
  DarkTheme,
  DefaultTheme,
  ThemeProvider,
} from '@react-navigation/native';
import { Stack, useRouter, useSegments } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { StatusBar } from 'expo-status-bar';
import { useEffect } from 'react';
import 'react-native-reanimated';

import { useColorScheme } from '@/hooks/use-color-scheme';
import { AuthProvider, useAuth } from '@/contexts/auth-context';

// Prevent the splash screen from auto-hiding before asset loading is complete.
SplashScreen.preventAutoHideAsync();

function RootLayoutNav() {
  const colorScheme = useColorScheme();
  const { isAuthenticated, isLoading } = useAuth();
  const segments = useSegments();
  const router = useRouter();

  useEffect(() => {
    if (isLoading) return;

    const inAuthGroup = segments[0] === '(tabs)';
    const inExpenseGroup = segments[0] === 'expense';
    const inLoginGroup = segments[0] === 'login' || !segments[0];

    if (!isAuthenticated && (inAuthGroup || inExpenseGroup)) {
      // Redirect to login if not authenticated and trying to access protected routes
      router.replace('/login');
    } else if (isAuthenticated && inLoginGroup) {
      // Redirect to tabs if authenticated and on login page
      router.replace('/(tabs)');
    }
    // Allow navigation between (tabs) and expense routes when authenticated
  }, [isAuthenticated, isLoading, segments]);

  useEffect(() => {
    // Hide splash screen when auth check is complete
    if (!isLoading) {
      SplashScreen.hideAsync();
    }
  }, [isLoading]);

  return (
    <ThemeProvider value={colorScheme === 'dark' ? DarkTheme : DefaultTheme}>
      <Stack>
        <Stack.Screen name="login" options={{ headerShown: false }} />
        <Stack.Screen name="(tabs)" options={{ headerShown: false, title: "Accueil" }} />
        <Stack.Screen name="expense/[id]" options={{ presentation: 'modal', title: 'Détail de la note' }} />
        <Stack.Screen name="expense/edit/[id]" options={{ presentation: 'modal', title: 'Modifier la note' }} />
        <Stack.Screen name="expense/create" options={{ title: 'Nouvelle note' }} />
      </Stack>
      <StatusBar style="auto" />
    </ThemeProvider>
  );
}

export default function RootLayout() {
  return (
    <AuthProvider>
      <RootLayoutNav />
    </AuthProvider>
  );
}
