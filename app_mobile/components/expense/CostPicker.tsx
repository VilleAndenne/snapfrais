import { StyleSheet, View, TouchableOpacity, ScrollView } from 'react-native';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import type { FormCost } from '@/types/api';

interface CostPickerProps {
  availableCosts: FormCost[];
  selectedCosts: any[];
  onAddCost: (cost: FormCost) => void;
  isDark: boolean;
  colorScheme: 'light' | 'dark';
}

export function CostPicker({
  availableCosts,
  selectedCosts,
  onAddCost,
  isDark,
  colorScheme,
}: CostPickerProps) {
  const getCostIcon = (type: string): any => {
    switch (type) {
      case 'km':
        return 'car.fill';
      case 'fixed':
        return 'eurosign.circle.fill';
      case 'percentage':
        return 'percent';
      default:
        return 'doc.text';
    }
  };

  const getCostTypeLabel = (type: string): string => {
    switch (type) {
      case 'km':
        return 'Kilométrique';
      case 'fixed':
        return 'Montant fixe';
      case 'percentage':
        return 'Pourcentage';
      default:
        return type;
    }
  };

  return (
    <View style={styles.container}>
      {availableCosts.map((cost) => (
        <TouchableOpacity
          key={cost.id}
          style={[
            styles.costItem,
            isDark && styles.costItemDark,
          ]}
          onPress={() => onAddCost(cost)}
        >
          <View style={styles.costIcon}>
            <IconSymbol
              size={24}
              name={getCostIcon(cost.type)}
              color={Colors[colorScheme].tint}
            />
          </View>

          <View style={styles.costContent}>
            <ThemedText style={styles.costName}>{cost.name}</ThemedText>
            <View style={styles.costMeta}>
              <ThemedText style={styles.costType}>{getCostTypeLabel(cost.type)}</ThemedText>
              {cost.processing_department && (
                <>
                  <ThemedText style={styles.costMetaSeparator}>•</ThemedText>
                  <ThemedText style={styles.costDepartment}>{cost.processing_department}</ThemedText>
                </>
              )}
            </View>
            {cost.description && (
              <ThemedText style={styles.costDescription} numberOfLines={2}>
                {cost.description}
              </ThemedText>
            )}
          </View>

          <IconSymbol
            size={20}
            name="plus.circle.fill"
            color={Colors[colorScheme].tint}
          />
        </TouchableOpacity>
      ))}

      {availableCosts.length === 0 && (
        <ThemedView style={styles.emptyState}>
          <IconSymbol size={48} name="tray" color={isDark ? '#666' : '#999'} />
          <ThemedText style={styles.emptyText}>Aucun coût disponible</ThemedText>
        </ThemedView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    gap: 12,
  },
  costItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    padding: 16,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#e0e0e0',
    backgroundColor: '#fff',
  },
  costItemDark: {
    borderColor: '#333',
    backgroundColor: '#1a1a1a',
  },
  costIcon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#f5f5f5',
    justifyContent: 'center',
    alignItems: 'center',
  },
  costContent: {
    flex: 1,
    gap: 4,
  },
  costName: {
    fontSize: 16,
    fontWeight: '600',
  },
  costMeta: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  costType: {
    fontSize: 13,
    opacity: 0.7,
    fontWeight: '500',
  },
  costMetaSeparator: {
    fontSize: 13,
    opacity: 0.5,
  },
  costDepartment: {
    fontSize: 13,
    opacity: 0.6,
  },
  costDescription: {
    fontSize: 13,
    opacity: 0.6,
    lineHeight: 18,
  },
  emptyState: {
    alignItems: 'center',
    justifyContent: 'center',
    padding: 48,
    gap: 16,
  },
  emptyText: {
    fontSize: 16,
    opacity: 0.5,
  },
});
