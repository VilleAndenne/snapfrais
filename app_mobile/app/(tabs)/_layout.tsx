import { Tabs } from 'expo-router';
import React from 'react';
import { Platform } from 'react-native';

import { HapticTab } from '@/components/haptic-tab';
import { IconSymbol } from '@/components/ui/icon-symbol';
import TabBarBackground from '@/components/ui/TabBarBackground';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { NativeTabs, Icon, Label, VectorIcon } from 'expo-router/unstable-native-tabs';
import MaterialIcons from '@expo/vector-icons/MaterialIcons';


export default function TabLayout() {
  const colorScheme = useColorScheme();
  const colors = Colors[colorScheme ?? 'light'];

  return (
      <NativeTabs>
          <NativeTabs.Trigger name="index">
              <Label>Accueil</Label>
              <Icon
                sf="house.fill"
                src={<VectorIcon family={MaterialIcons} name="home" />}
                selectedColor={colors.tabIconSelected}
              />
          </NativeTabs.Trigger>
          <NativeTabs.Trigger name="expenses">
              <Icon
                sf="list.bullet.clipboard"
                src={<VectorIcon family={MaterialIcons} name="assignment" />}
                selectedColor={colors.tabIconSelected}
              />
              <Label>Notes de frais</Label>
          </NativeTabs.Trigger>
      </NativeTabs>
  );
}
