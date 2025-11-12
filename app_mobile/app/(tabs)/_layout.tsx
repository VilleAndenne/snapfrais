import { Tabs } from 'expo-router';
import React from 'react';
import { Platform } from 'react-native';

import { HapticTab } from '@/components/haptic-tab';
import { IconSymbol } from '@/components/ui/icon-symbol';
import TabBarBackground from '@/components/ui/TabBarBackground';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { NativeTabs, Icon, Label } from 'expo-router/unstable-native-tabs';


export default function TabLayout() {
  const colorScheme = useColorScheme();

  return (
      <NativeTabs>
          <NativeTabs.Trigger name="index">
              <Label>Accueil</Label>
              <Icon sf="house.fill" drawable="custom_android_drawable" />
          </NativeTabs.Trigger>
          <NativeTabs.Trigger name="expenses">
              <Icon sf="list.bullet.clipboard" drawable="custom_settings_drawable" />
              <Label>Notes de frais</Label>
          </NativeTabs.Trigger>
      </NativeTabs>
  );
}
