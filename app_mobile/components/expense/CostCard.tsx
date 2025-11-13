import { StyleSheet, View, TouchableOpacity, TextInput, Alert } from 'react-native';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { DistanceInput } from './DistanceInput';
import { RequirementsInput } from './RequirementsInput';
import { DatePicker } from '@/components/ui/date-picker';
import type { FormCost } from '@/types/api';

interface CostCardProps {
  cost: FormCost;
  costData: any;
  index: number;
  isDark: boolean;
  colorScheme: 'light' | 'dark';
  submitted: boolean;
  onRemove: () => void;
  onDuplicate: (count: number) => void;
  onUpdateData: (data: any) => void;
  getActiveRate: (date: string) => number;
}

export function CostCard({
  cost,
  costData,
  index,
  isDark,
  colorScheme,
  submitted,
  onRemove,
  onDuplicate,
  onUpdateData,
  getActiveRate,
}: CostCardProps) {
  const handleDuplicate = () => {
    Alert.prompt(
      'Dupliquer le coût',
      `Combien de copies de "${cost.name}" voulez-vous créer ?`,
      [
        { text: 'Annuler', style: 'cancel' },
        {
          text: 'Dupliquer',
          onPress: (value) => {
            const count = parseInt(value || '1', 10);
            if (count > 0 && count <= 20) {
              onDuplicate(count);
            } else {
              Alert.alert('Erreur', 'Veuillez entrer un nombre entre 1 et 20');
            }
          },
        },
      ],
      'plain-text',
      '1'
    );
  };

  const handleDateChange = (date: string) => {
    onUpdateData({ date });

    // Update rate based on new date
    const rate = getActiveRate(date);
    if (cost.type === 'percentage') {
      onUpdateData({
        date,
        percentageData: {
          ...costData.percentageData,
          percentage: rate,
        },
      });
    } else if (cost.type === 'fixed') {
      onUpdateData({ date, fixedAmount: rate });
    }
  };

  return (
    <ThemedView style={styles.card}>
      {/* Action buttons */}
      <View style={styles.actions}>
        <TouchableOpacity onPress={handleDuplicate} style={styles.actionButton}>
          <IconSymbol size={20} name="doc.on.doc" color="#007AFF" />
        </TouchableOpacity>
        <TouchableOpacity onPress={onRemove} style={styles.actionButton}>
          <IconSymbol size={20} name="trash" color="#FF3B30" />
        </TouchableOpacity>
      </View>

      {/* Cost header */}
      <View style={styles.header}>
        <View style={styles.headerContent}>
          <ThemedText style={styles.costName}>{cost.name}</ThemedText>
          <ThemedText style={styles.costType}>{cost.type}</ThemedText>
        </View>
      </View>

      {cost.description && (
        <ThemedText style={styles.description}>{cost.description}</ThemedText>
      )}

      {/* Date field */}
      <View style={styles.field}>
        <ThemedText style={styles.fieldLabel}>Date du coût <ThemedText style={styles.required}>*</ThemedText></ThemedText>
        <DatePicker
          value={costData.date}
          onChange={handleDateChange}
          isDark={isDark}
          hasError={submitted && !costData.date}
        />
        {submitted && !costData.date && (
          <ThemedText style={styles.errorText}>Ce champ est obligatoire</ThemedText>
        )}
      </View>

      {/* Cost type specific inputs */}
      {cost.type === 'km' && (
        <ThemedView style={[styles.kmSection, isDark && styles.kmSectionDark]}>
          {(() => {
            const currentDate = costData.date || new Date().toISOString().split('T')[0];
            const currentRate = getActiveRate(currentDate);
            console.log('CostCard - KM type:', {
              costName: cost.name,
              date: currentDate,
              rate: currentRate,
              reimbursementRates: cost.reimbursementRates
            });
            return (
              <DistanceInput
                isDark={isDark}
                rate={currentRate}
                onDistanceCalculated={(distance, route) => {
                  const rate = getActiveRate(costData.date || new Date().toISOString().split('T')[0]);
                  const totalAmount = distance * rate;
                  console.log('CostCard - Distance calculated:', { distance, rate, totalAmount, route });

                  // Extract waypoints (all items except first and last)
                  const waypoints = route.slice(1, -1).map(w => w.address);

                  onUpdateData({
                    kmData: {
                      googleKm: distance,
                      totalKm: distance,
                      departure: route[0]?.address,
                      arrival: route[route.length - 1]?.address,
                      steps: waypoints,
                      manualKm: 0,
                    },
                    reimbursementAmount: totalAmount,
                  });
                }}
              />
            );
          })()}

          {/* Error message if distance not calculated */}
          {submitted && !costData.kmData?.totalKm && (
            <ThemedText style={styles.errorText}>Veuillez calculer la distance en entrant les adresses de départ et d'arrivée</ThemedText>
          )}

          {/* Display calculated amount */}
          {costData.kmData?.totalKm && (
            <ThemedView style={[styles.kmCalculation, isDark && styles.kmCalculationDark]}>
              <View style={styles.kmCalculationRow}>
                <ThemedText style={styles.kmLabel}>Distance:</ThemedText>
                <ThemedText style={styles.kmValue}>{costData.kmData.totalKm} km</ThemedText>
              </View>
              <View style={styles.kmCalculationRow}>
                <ThemedText style={styles.kmLabel}>Tarif:</ThemedText>
                <ThemedText style={styles.kmValue}>
                  {getActiveRate(costData.date || new Date().toISOString().split('T')[0]).toFixed(2)} €/km
                </ThemedText>
              </View>
              <View style={[styles.kmCalculationRow, styles.kmTotal]}>
                <ThemedText style={styles.kmTotalLabel}>Remboursement:</ThemedText>
                <ThemedText style={styles.kmTotalValue}>
                  {(costData.reimbursementAmount || 0).toFixed(2)} €
                </ThemedText>
              </View>
            </ThemedView>
          )}
        </ThemedView>
      )}

      {cost.type === 'fixed' && (
        <View style={styles.field}>
          <ThemedText style={styles.fieldLabel}>Montant fixe <ThemedText style={styles.required}>*</ThemedText></ThemedText>
          <ThemedView style={[
            styles.input,
            isDark && styles.inputDark,
            submitted && (!costData.fixedAmount || costData.fixedAmount <= 0) && styles.inputError
          ]}>
            <TextInput
              value={costData.fixedAmountText || ''}
              onChangeText={(value) => {
                // Accepter seulement chiffres, virgule et point
                const cleanValue = value.replace(/[^0-9.,]/g, '');
                const normalizedValue = cleanValue.replace(',', '.');
                onUpdateData({
                  fixedAmountText: cleanValue,
                  fixedAmount: parseFloat(normalizedValue) || 0
                });
              }}
              placeholder="0.00"
              placeholderTextColor={isDark ? '#666' : '#999'}
              keyboardType="numeric"
              style={[styles.textInput, { color: isDark ? '#fff' : '#000' }]}
            />
            <ThemedText style={styles.currency}>€</ThemedText>
          </ThemedView>
          {submitted && (!costData.fixedAmount || costData.fixedAmount <= 0) && (
            <ThemedText style={styles.errorText}>Veuillez entrer un montant valide</ThemedText>
          )}
        </View>
      )}

      {cost.type === 'percentage' && (
        <View style={styles.percentageSection}>
          <View style={styles.field}>
            <ThemedText style={styles.fieldLabel}>Montant payé <ThemedText style={styles.required}>*</ThemedText></ThemedText>
            <ThemedView style={[
              styles.input,
              isDark && styles.inputDark,
              submitted && (!costData.percentageData?.paidAmount || costData.percentageData.paidAmount <= 0) && styles.inputError
            ]}>
              <TextInput
                value={costData.percentageData?.paidAmountText || ''}
                onChangeText={(value) => {
                  // Accepter seulement chiffres, virgule et point
                  const cleanValue = value.replace(/[^0-9.,]/g, '');
                  // Remplacer la virgule par un point pour parseFloat
                  const normalizedValue = cleanValue.replace(',', '.');
                  const paidAmount = parseFloat(normalizedValue) || 0;
                  const percentage = costData.percentageData?.percentage || 0;
                  const reimbursedAmount = (paidAmount * percentage) / 100;
                  onUpdateData({
                    percentageData: {
                      ...costData.percentageData,
                      paidAmountText: cleanValue, // Garder la valeur brute avec virgule
                      paidAmount,
                      reimbursedAmount,
                    },
                  });
                }}
                placeholder="0.00"
                placeholderTextColor={isDark ? '#666' : '#999'}
                keyboardType="numeric"
                style={[styles.textInput, { color: isDark ? '#fff' : '#000' }]}
              />
              <ThemedText style={styles.currency}>€</ThemedText>
            </ThemedView>
            {submitted && (!costData.percentageData?.paidAmount || costData.percentageData.paidAmount <= 0) && (
              <ThemedText style={styles.errorText}>Veuillez entrer un montant valide</ThemedText>
            )}
          </View>

          <ThemedView style={[styles.percentageInfo, isDark && styles.percentageInfoDark]}>
            <ThemedText style={styles.percentageLabel}>
              Pourcentage: {costData.percentageData?.percentage || 0}%
            </ThemedText>
            <ThemedText style={styles.reimbursedAmount}>
              Remboursé: {(costData.percentageData?.reimbursedAmount || 0).toFixed(2)} €
            </ThemedText>
          </ThemedView>
        </View>
      )}

      {/* Requirements section */}
      {cost.requirements && cost.requirements.length > 0 && (
        <RequirementsInput
          requirements={cost.requirements}
          values={costData.requirements || {}}
          isDark={isDark}
          submitted={submitted}
          onUpdate={(requirementId, value) => {
            onUpdateData({
              requirements: {
                ...costData.requirements,
                [requirementId]: value,
              },
            });
          }}
        />
      )}
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  card: {
    borderRadius: 12,
    padding: 16,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#e0e0e0',
  },
  actions: {
    flexDirection: 'row',
    justifyContent: 'flex-end',
    gap: 8,
    marginBottom: 12,
  },
  actionButton: {
    padding: 8,
  },
  header: {
    marginBottom: 12,
  },
  headerContent: {
    gap: 4,
  },
  costName: {
    fontSize: 18,
    fontWeight: 'bold',
  },
  costType: {
    fontSize: 13,
    opacity: 0.6,
    fontStyle: 'italic',
  },
  description: {
    fontSize: 14,
    opacity: 0.7,
    marginBottom: 12,
  },
  field: {
    marginBottom: 12,
  },
  fieldLabel: {
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 6,
  },
  input: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 8,
    padding: 12,
    gap: 8,
    borderWidth: 1,
    borderColor: '#e0e0e0',
  },
  inputDark: {
    borderColor: '#333',
  },
  inputError: {
    borderColor: '#FF3B30',
    borderWidth: 2,
  },
  textInput: {
    flex: 1,
    fontSize: 14,
  },
  required: {
    color: '#FF3B30',
    fontWeight: 'bold',
  },
  errorText: {
    fontSize: 12,
    color: '#FF3B30',
    marginTop: 4,
  },
  currency: {
    fontSize: 16,
    fontWeight: '600',
  },
  kmSection: {
    padding: 12,
    borderRadius: 8,
    marginTop: 8,
    gap: 12,
    backgroundColor: '#f5f5f5',
  },
  kmSectionDark: {
    backgroundColor: '#1C1C1E',
  },
  kmPlaceholder: {
    fontSize: 14,
    opacity: 0.7,
    textAlign: 'center',
  },
  kmCalculation: {
    padding: 12,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#e0e0e0',
    backgroundColor: '#fff',
    gap: 8,
  },
  kmCalculationDark: {
    borderColor: '#333',
    backgroundColor: '#2C2C2E',
  },
  kmCalculationRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  kmLabel: {
    fontSize: 14,
    opacity: 0.7,
  },
  kmValue: {
    fontSize: 14,
    fontWeight: '600',
  },
  kmTotal: {
    marginTop: 8,
    paddingTop: 8,
    borderTopWidth: 1,
    borderTopColor: '#e0e0e0',
  },
  kmTotalLabel: {
    fontSize: 16,
    fontWeight: '600',
  },
  kmTotalValue: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#34C759',
  },
  percentageSection: {
    gap: 12,
  },
  percentageInfo: {
    padding: 12,
    borderRadius: 8,
    backgroundColor: '#f5f5f5',
    gap: 4,
  },
  percentageInfoDark: {
    backgroundColor: '#1C1C1E',
  },
  percentageLabel: {
    fontSize: 14,
    fontWeight: '600',
  },
  reimbursedAmount: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#34C759',
  },
});
