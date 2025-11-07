import { BlurView } from 'expo-blur';
import { StyleSheet } from 'react-native';
import { useColorScheme } from '@/hooks/use-color-scheme';

export default function TabBarBackground() {
  const colorScheme = useColorScheme();

  return (
    <BlurView
      intensity={100}
      tint={colorScheme === 'dark' ? 'dark' : 'light'}
      style={StyleSheet.absoluteFill}
    />
  );
}
