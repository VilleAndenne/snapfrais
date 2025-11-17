import { StyleSheet, Platform, View } from 'react-native';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { GlassView, isLiquidGlassAvailable } from 'expo-glass-effect';

export default function TabBarBackground() {
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';

  // Use native iOS 26 Liquid Glass effect
  if (Platform.OS === 'ios' && isLiquidGlassAvailable()) {
    return (
      <GlassView
        style={StyleSheet.absoluteFill}
        glassEffectStyle="regular"
        isInteractive
        tintColor={isDark ? '#1C1C1E66' : '#F2F2F766'}
      />
    );
  }

  // Fallback for other platforms or iOS < 26
  return (
    <View
      style={[
        StyleSheet.absoluteFill,
        {
          backgroundColor: isDark ? 'rgba(28, 28, 30, 0.92)' : 'rgba(242, 242, 247, 0.92)',
        },
      ]}
    />
  );
}
