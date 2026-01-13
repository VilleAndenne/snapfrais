import { useRouter, useLocalSearchParams } from 'expo-router';
import { StyleSheet, View, ScrollView, TouchableOpacity, ActivityIndicator, Alert } from 'react-native';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { useState, useEffect } from 'react';
import { api } from '@/services/api';
import type { FormCost, Department, ExpenseSheet, ExpenseSheetCost } from '@/types/api';
import { CostCard } from '@/components/expense/CostCard';
import { CostPicker } from '@/components/expense/CostPicker';
import { SelectPicker } from '@/components/ui/select-picker';

interface SelectedCost extends FormCost {
  _index: number;
  _originalCostId?: number; // ID du coût existant dans la base
}

interface CostData {
  date: string;
  kmData?: {
    departure?: string;
    arrival?: string;
    steps?: string[];
    googleKm?: number;
    manualKm?: number;
    totalKm?: number;
  };
  percentageData?: {
    paidAmount?: number;
    percentage?: number;
    reimbursedAmount?: number;
  };
  fixedAmount?: number;
  reimbursementAmount?: number;
  requirements?: Record<string, any>;
}

export default function EditExpenseScreen() {
  const { id } = useLocalSearchParams();
  const router = useRouter();
  const colorScheme = useColorScheme() ?? 'light';
  const isDark = colorScheme === 'dark';

  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isDraftSubmit, setIsDraftSubmit] = useState(false);

  const [expenseSheet, setExpenseSheet] = useState<ExpenseSheet | null>(null);
  const [canEdit, setCanEdit] = useState(false);
  const [availableCosts, setAvailableCosts] = useState<FormCost[]>([]);
  const [departments, setDepartments] = useState<Department[]>([]);

  const [selectedDepartmentId, setSelectedDepartmentId] = useState<number | null>(null);
  const [selectedCosts, setSelectedCosts] = useState<SelectedCost[]>([]);
  const [costData, setCostData] = useState<CostData[]>([]);
  const [submitted, setSubmitted] = useState(false);

  useEffect(() => {
    if (id) {
      fetchExpenseSheetData();
    }
  }, [id]);

  const fetchExpenseSheetData = async () => {
    try {
      setIsLoading(true);

      // Charger les détails de la note de frais
      const response = await api.getExpenseSheetDetails(Number(id));

      if (!response.canEdit) {
        Alert.alert(
          'Non autorisé',
          'Vous n\'avez pas les permissions pour modifier cette note de frais.',
          [{ text: 'OK', onPress: () => router.back() }]
        );
        return;
      }

      setExpenseSheet(response.expenseSheet);
      setCanEdit(response.canEdit);
      setSelectedDepartmentId(response.expenseSheet.department_id || null);

      // Charger les détails du formulaire pour avoir les coûts disponibles
      if (response.expenseSheet.form_id) {
        const formResponse = await api.getFormDetails(response.expenseSheet.form_id);

        // Transformer les données si nécessaire
        const transformedCosts = formResponse.form.costs?.map((cost: any) => {
          const transformedCost = { ...cost };
          if (cost.reimbursement_rates && !cost.reimbursementRates) {
            transformedCost.reimbursementRates = cost.reimbursement_rates;
          }
          if (cost.requirements && Array.isArray(cost.requirements)) {
            transformedCost.requirements = cost.requirements.map((req: any) => ({
              ...req,
              requirement_type: req.requirement_type || req.requirementType,
            }));
          }
          return transformedCost;
        }) || [];

        setAvailableCosts(transformedCosts);
        setDepartments(formResponse.departments);

        // Pré-remplir les coûts existants
        if (response.expenseSheet.costs && response.expenseSheet.costs.length > 0) {
          const existingCosts: SelectedCost[] = [];
          const existingCostData: CostData[] = [];

          response.expenseSheet.costs.forEach((cost: ExpenseSheetCost, index: number) => {
            // Trouver le FormCost correspondant
            const formCost = transformedCosts.find((fc: FormCost) => fc.id === cost.form_cost_id);

            if (formCost) {
              existingCosts.push({
                ...formCost,
                _index: Date.now() + index,
                _originalCostId: cost.id,
              });

              // Reconstruire les données du coût
              const costDataItem: CostData = {
                date: cost.date ? cost.date.split('T')[0] : new Date().toISOString().split('T')[0],
                requirements: {},
              };

              // Gérer les différents types de coûts
              if (formCost.type === 'km') {
                const route = (cost.route as any) || {};
                // Extraire les adresses des étapes (peuvent être des objets {address, order} ou des strings)
                const stepsArray = route.steps || [];
                const stepsAddresses = stepsArray.map((step: any) =>
                  typeof step === 'string' ? step : (step.address || step.name || '')
                );
                costDataItem.kmData = {
                  departure: route.departure || '',
                  arrival: route.arrival || '',
                  steps: stepsAddresses,
                  googleKm: route.google_km || cost.google_distance || 0,
                  manualKm: route.manual_km || 0,
                  totalKm: (route.google_km || cost.google_distance || 0) + (route.manual_km || 0),
                };
                costDataItem.reimbursementAmount = cost.total || 0;
              } else if (formCost.type === 'percentage') {
                const paidAmount = cost.amount || 0;
                costDataItem.percentageData = {
                  paidAmount: paidAmount,
                  paidAmountText: paidAmount > 0 ? paidAmount.toString().replace('.', ',') : '',
                  percentage: getActiveRate(formCost, costDataItem.date),
                  reimbursedAmount: cost.total || 0,
                };
              } else {
                costDataItem.fixedAmount = cost.amount || 0;
              }

              // Charger les requirements existants
              if (cost.requirements) {
                try {
                  const reqData = typeof cost.requirements === 'string'
                    ? JSON.parse(cost.requirements)
                    : cost.requirements;

                  // Mapper les requirements par leur ID
                  if (formCost.requirements) {
                    formCost.requirements.forEach((req: any) => {
                      const existingValue = reqData[req.name] || reqData[req.id];
                      if (existingValue) {
                        if (existingValue.value) {
                          costDataItem.requirements![req.id] = existingValue.value;
                        } else if (existingValue.file) {
                          // Pour les fichiers, on garde juste l'information qu'il existe
                          costDataItem.requirements![req.id] = { existing: true, url: existingValue.file };
                        }
                      }
                    });
                  }
                } catch (e) {
                  console.error('Error parsing requirements:', e);
                }
              }

              existingCostData.push(costDataItem);
            }
          });

          setSelectedCosts(existingCosts);
          setCostData(existingCostData);
        }
      }
    } catch (error) {
      console.error('Error fetching expense sheet:', error);
      Alert.alert(
        'Erreur',
        'Impossible de charger la note de frais',
        [
          { text: 'Réessayer', onPress: fetchExpenseSheetData },
          { text: 'Retour', onPress: () => router.back() },
        ]
      );
    } finally {
      setIsLoading(false);
    }
  };

  const addCost = (cost: FormCost) => {
    if (selectedCosts.length >= 30) {
      Alert.alert('Limite atteinte', 'Vous avez atteint le maximum de 30 coûts.');
      return;
    }

    const today = new Date().toISOString().split('T')[0];
    const newCost = { ...cost, _index: Date.now() };

    setSelectedCosts([...selectedCosts, newCost]);
    setCostData([...costData, {
      date: today,
      kmData: {},
      percentageData: {
        paidAmount: 0,
        percentage: getActiveRate(cost, today),
        reimbursedAmount: 0,
      },
      fixedAmount: getActiveRate(cost, today),
      requirements: {},
    }]);
  };

  const removeCost = (index: number) => {
    setSelectedCosts(selectedCosts.filter((_, i) => i !== index));
    setCostData(costData.filter((_, i) => i !== index));
  };

  const duplicateCost = (index: number, count: number) => {
    if (selectedCosts.length + count > 30) {
      Alert.alert('Limite atteinte', `Vous ne pouvez pas ajouter ${count} coûts. Maximum 30 coûts au total.`);
      return;
    }

    const originalCost = selectedCosts[index];
    const originalData = costData[index];
    const newCosts: SelectedCost[] = [];
    const newData: CostData[] = [];

    for (let i = 0; i < count; i++) {
      newCosts.push({ ...originalCost, _index: Date.now() + i, _originalCostId: undefined });
      newData.push({
        date: originalData.date,
        kmData: { ...originalData.kmData },
        percentageData: { ...originalData.percentageData },
        fixedAmount: originalData.fixedAmount,
        requirements: {},
      });
    }

    setSelectedCosts([...selectedCosts, ...newCosts]);
    setCostData([...costData, ...newData]);
  };

  const updateCostData = (index: number, data: Partial<CostData>) => {
    const newCostData = [...costData];
    newCostData[index] = { ...newCostData[index], ...data };
    setCostData(newCostData);
  };

  const getActiveRate = (cost: FormCost, date: string): number => {
    const rates = cost.reimbursementRates || [];
    const activeRates = rates.filter(r =>
      r.start_date <= date && (!r.end_date || r.end_date >= date)
    );
    activeRates.sort((a, b) => (a.start_date > b.start_date ? -1 : 1));
    return activeRates[0]?.value ?? 0;
  };

  const handleSaveDraft = () => {
    setIsDraftSubmit(true);
    handleSubmit(true);
  };

  const handleSubmitForm = () => {
    setSubmitted(true);
    setIsDraftSubmit(false);
    handleSubmit(false);
  };

  const handleSubmit = async (isDraft: boolean) => {
    if (!selectedDepartmentId) {
      Alert.alert('Champ requis', 'Veuillez sélectionner un département.');
      return;
    }

    if (selectedCosts.length === 0) {
      Alert.alert('Aucun coût', 'Veuillez ajouter au moins un coût à votre note de frais.');
      return;
    }

    // Valider les requirements obligatoires (seulement si ce n'est pas un brouillon)
    if (!isDraft) {
      for (let i = 0; i < selectedCosts.length; i++) {
        const cost = selectedCosts[i];
        const data = costData[i];

        if (cost.requirements && cost.requirements.length > 0) {
          for (const requirement of cost.requirements) {
            if (requirement.required) {
              const value = data.requirements?.[requirement.id];
              // Vérifier si c'est un fichier existant ou une nouvelle valeur
              const hasValue = value && (
                typeof value === 'string' ||
                value.uri ||
                value.existing
              );
              if (!hasValue) {
                Alert.alert(
                  'Prérequis manquants',
                  `Le coût "${cost.name}" nécessite: ${requirement.requirement_type}.\nVeuillez compléter tous les champs obligatoires avant de soumettre.`
                );
                return;
              }
            }
          }
        }
      }
    }

    try {
      setIsSubmitting(true);

      // Prepare FormData
      const formData = new FormData();
      formData.append('department_id', selectedDepartmentId.toString());
      formData.append('is_draft', isDraft ? '1' : '0');

      // Add costs
      selectedCosts.forEach((cost, index) => {
        const data = cost.type === 'km'
          ? costData[index].kmData || {}
          : cost.type === 'percentage'
          ? costData[index].percentageData || {}
          : { amount: costData[index].fixedAmount };

        formData.append(`costs[${index}][cost_id]`, cost.id.toString());
        formData.append(`costs[${index}][date]`, costData[index].date);

        // Inclure l'ID du coût existant si disponible (pour la mise à jour)
        if (cost._originalCostId) {
          formData.append(`costs[${index}][id]`, cost._originalCostId.toString());
        }

        // Add cost data
        Object.entries(data).forEach(([key, value]) => {
          if (value !== null && value !== undefined) {
            if (Array.isArray(value)) {
              value.forEach((item, itemIndex) => {
                formData.append(`costs[${index}][data][${key}][${itemIndex}]`, item.toString());
              });
            } else {
              formData.append(`costs[${index}][data][${key}]`, value.toString());
            }
          }
        });

        // Add requirements
        if (costData[index].requirements) {
          Object.entries(costData[index].requirements).forEach(([reqId, req]) => {
            if (req?.uri) {
              // Handle new file uploads
              const file: any = {
                uri: req.uri,
                name: req.name || `file_${reqId}`,
                type: req.type || 'application/octet-stream',
              };
              formData.append(`costs[${index}][requirements][${reqId}][file]`, file);
            } else if (req?.existing) {
              // Marquer comme fichier existant à conserver
              formData.append(`costs[${index}][requirements][${reqId}][keep_existing]`, '1');
            } else if (req !== null && req !== undefined && req !== '') {
              // Handle text values
              formData.append(`costs[${index}][requirements][${reqId}][value]`, req.toString());
            }
          });
        }
      });

      // Call API pour mettre à jour
      await api.updateExpenseSheetFull(Number(id), formData);

      Alert.alert(
        'Succès',
        isDraft ? 'Brouillon mis à jour avec succès' : 'Note de frais mise à jour et soumise avec succès',
        [{ text: 'OK', onPress: () => router.back() }]
      );
    } catch (error) {
      console.error('Error updating expense sheet:', error);
      Alert.alert('Erreur', error instanceof Error ? error.message : 'Une erreur est survenue lors de la mise à jour');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (isLoading || !expenseSheet) {
    return (
      <ThemedView style={styles.container}>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={Colors[colorScheme].tint} />
          <ThemedText style={styles.loadingText}>Chargement...</ThemedText>
        </View>
      </ThemedView>
    );
  }

  return (
    <ThemedView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.closeButton}>
          <IconSymbol size={28} name="xmark.circle.fill" color={isDark ? '#fff' : '#000'} />
        </TouchableOpacity>
        <ThemedText type="title" style={styles.headerTitle}>
          Modifier la note
        </ThemedText>
        <View style={styles.headerSpacer} />
      </View>

      <ScrollView style={styles.scrollView} showsVerticalScrollIndicator={false}>
        <View style={styles.content}>
          {/* Form name */}
          <View style={styles.formHeader}>
            <IconSymbol size={24} name="doc.text.fill" color={Colors[colorScheme].tint} />
            <ThemedText style={styles.formName}>{expenseSheet.form?.name}</ThemedText>
          </View>

          {expenseSheet.form?.description && (
            <ThemedText style={styles.formDescription}>{expenseSheet.form.description}</ThemedText>
          )}

          {/* Department selection */}
          <View style={styles.section}>
            <ThemedText style={styles.sectionLabel}>
              Département <ThemedText style={styles.required}>*</ThemedText>
            </ThemedText>
            <SelectPicker
              options={departments}
              selectedId={selectedDepartmentId}
              onSelect={(id) => setSelectedDepartmentId(id)}
              placeholder="Sélectionner un département"
              isDark={isDark}
              hasError={submitted && !selectedDepartmentId}
              title="Sélectionner un département"
            />
            {submitted && !selectedDepartmentId && (
              <ThemedText style={styles.errorText}>Veuillez sélectionner un département</ThemedText>
            )}
          </View>

          {/* Selected costs */}
          {selectedCosts.length > 0 && (
            <View style={styles.section}>
              <ThemedText style={styles.sectionTitle}>Votre demande</ThemedText>
              {selectedCosts.map((cost, index) => (
                <CostCard
                  key={cost._index}
                  cost={cost}
                  costData={costData[index]}
                  index={index}
                  isDark={isDark}
                  colorScheme={colorScheme}
                  submitted={submitted}
                  onRemove={() => removeCost(index)}
                  onDuplicate={(count) => duplicateCost(index, count)}
                  onUpdateData={(data) => updateCostData(index, data)}
                  getActiveRate={(date) => getActiveRate(cost, date)}
                />
              ))}

              {/* Summary of KM costs */}
              {(() => {
                const kmCosts = selectedCosts
                  .map((cost, index) => ({ cost, data: costData[index], index }))
                  .filter(item => item.cost.type === 'km' && item.data?.kmData?.totalKm && item.data.kmData.totalKm > 0);

                if (kmCosts.length === 0) return null;

                const totalKm = kmCosts.reduce((sum, item) => sum + (Number(item.data.kmData?.totalKm) || 0), 0);
                const totalAmount = kmCosts.reduce((sum, item) => sum + (Number(item.data.reimbursementAmount) || 0), 0);

                const weightedRateSum = kmCosts.reduce((sum, item) => {
                  const rate = getActiveRate(item.cost, item.data.date || new Date().toISOString().split('T')[0]);
                  const km = Number(item.data.kmData?.totalKm) || 0;
                  return sum + (rate * km);
                }, 0);
                const averageRate = totalKm > 0 ? weightedRateSum / totalKm : 0;

                return (
                  <ThemedView style={[styles.kmSummary, isDark && styles.kmSummaryDark]}>
                    <View style={styles.kmSummaryHeader}>
                      <IconSymbol size={24} name="car.fill" color={Colors[colorScheme].tint} />
                      <ThemedText style={styles.kmSummaryTitle}>Récapitulatif kilométrique</ThemedText>
                    </View>

                    <View style={styles.kmSummaryContent}>
                      <View style={styles.kmSummaryRow}>
                        <ThemedText style={styles.kmSummaryLabel}>Nombre de trajets:</ThemedText>
                        <ThemedText style={styles.kmSummaryValue}>{kmCosts.length}</ThemedText>
                      </View>

                      <View style={styles.kmSummaryRow}>
                        <ThemedText style={styles.kmSummaryLabel}>Distance totale:</ThemedText>
                        <ThemedText style={styles.kmSummaryValue}>{totalKm.toFixed(2)} km</ThemedText>
                      </View>

                      <View style={styles.kmSummaryRow}>
                        <ThemedText style={styles.kmSummaryLabel}>Taux moyen:</ThemedText>
                        <ThemedText style={styles.kmSummaryValue}>{averageRate.toFixed(4)} €/km</ThemedText>
                      </View>

                      <View style={[styles.kmSummaryRow, styles.kmSummaryTotal]}>
                        <ThemedText style={styles.kmSummaryTotalLabel}>Remboursement total:</ThemedText>
                        <ThemedText style={[styles.kmSummaryTotalValue, { color: Colors[colorScheme].tint }]}>
                          {totalAmount.toFixed(2)} €
                        </ThemedText>
                      </View>
                    </View>
                  </ThemedView>
                );
              })()}
            </View>
          )}

          {/* Available costs */}
          <View style={styles.section}>
            <ThemedText style={styles.sectionTitle}>Types de coûts disponibles</ThemedText>
            <ThemedText style={styles.costCount}>
              Coûts ajoutés : {selectedCosts.length}/30
            </ThemedText>
            <CostPicker
              availableCosts={availableCosts}
              selectedCosts={selectedCosts}
              onAddCost={addCost}
              isDark={isDark}
              colorScheme={colorScheme}
            />
          </View>
        </View>
      </ScrollView>

      {/* Actions */}
      <View style={[styles.actions, isDark && styles.actionsDark]}>
        <TouchableOpacity
          style={[
            styles.actionButton,
            styles.draftButton,
            isDark && styles.draftButtonDark,
            (isSubmitting || selectedCosts.length === 0) && styles.buttonDisabled,
          ]}
          onPress={handleSaveDraft}
          disabled={isSubmitting || selectedCosts.length === 0}
        >
          {isSubmitting && isDraftSubmit ? (
            <ActivityIndicator size="small" color={isDark ? '#fff' : '#666'} />
          ) : (
            <ThemedText style={[styles.draftButtonText, isDark && styles.draftButtonTextDark]}>Enregistrer</ThemedText>
          )}
        </TouchableOpacity>
        <TouchableOpacity
          style={[
            styles.actionButton,
            styles.submitButton,
            { backgroundColor: '#34C759' },
            (isSubmitting || selectedCosts.length === 0) && styles.buttonDisabled,
          ]}
          onPress={handleSubmitForm}
          disabled={isSubmitting || selectedCosts.length === 0}
        >
          {isSubmitting && !isDraftSubmit ? (
            <ActivityIndicator size="small" color="#fff" />
          ) : (
            <ThemedText style={styles.submitButtonText}>Soumettre</ThemedText>
          )}
        </TouchableOpacity>
      </View>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    gap: 16,
  },
  loadingText: {
    fontSize: 16,
    opacity: 0.7,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingTop: 20,
    paddingBottom: 10,
  },
  closeButton: {
    padding: 4,
  },
  headerTitle: {
    fontSize: 20,
    fontWeight: 'bold',
  },
  headerSpacer: {
    width: 36,
  },
  scrollView: {
    flex: 1,
  },
  content: {
    paddingHorizontal: 20,
    paddingTop: 10,
    paddingBottom: 120,
  },
  formHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 8,
  },
  formName: {
    fontSize: 24,
    fontWeight: 'bold',
  },
  formDescription: {
    fontSize: 14,
    opacity: 0.7,
    marginBottom: 24,
  },
  section: {
    marginBottom: 24,
  },
  sectionLabel: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 8,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '600',
    marginBottom: 12,
  },
  required: {
    color: '#FF3B30',
  },
  selectButton: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#e0e0e0',
    padding: 16,
    minHeight: 56,
  },
  selectButtonDark: {
    borderColor: '#333',
  },
  selectButtonError: {
    borderColor: '#FF3B30',
    borderWidth: 2,
  },
  selectButtonText: {
    fontSize: 16,
    flex: 1,
  },
  placeholderText: {
    opacity: 0.5,
  },
  errorText: {
    fontSize: 12,
    color: '#FF3B30',
    marginTop: 4,
  },
  costCount: {
    fontSize: 14,
    opacity: 0.7,
    marginBottom: 12,
  },
  actions: {
    flexDirection: 'row',
    gap: 12,
    padding: 20,
    borderTopWidth: 1,
    borderTopColor: '#e0e0e0',
  },
  actionsDark: {
    borderTopColor: '#333',
  },
  actionButton: {
    flex: 1,
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  draftButton: {
    backgroundColor: '#e0e0e0',
  },
  draftButtonDark: {
    backgroundColor: '#2C2C2E',
  },
  draftButtonText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#666',
  },
  draftButtonTextDark: {
    color: '#fff',
  },
  submitButton: {
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  submitButtonText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#fff',
  },
  buttonDisabled: {
    opacity: 0.5,
  },
  kmSummary: {
    marginTop: 20,
    borderRadius: 16,
    padding: 20,
    borderWidth: 2,
    borderColor: '#e0e0e0',
    backgroundColor: '#f9f9f9',
  },
  kmSummaryDark: {
    borderColor: '#333',
    backgroundColor: '#1C1C1E',
  },
  kmSummaryHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 16,
    paddingBottom: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#e0e0e0',
  },
  kmSummaryTitle: {
    fontSize: 18,
    fontWeight: 'bold',
  },
  kmSummaryContent: {
    gap: 12,
  },
  kmSummaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  kmSummaryLabel: {
    fontSize: 15,
    opacity: 0.8,
  },
  kmSummaryValue: {
    fontSize: 15,
    fontWeight: '600',
  },
  kmSummaryTotal: {
    marginTop: 8,
    paddingTop: 12,
    borderTopWidth: 2,
    borderTopColor: '#e0e0e0',
  },
  kmSummaryTotalLabel: {
    fontSize: 17,
    fontWeight: 'bold',
  },
  kmSummaryTotalValue: {
    fontSize: 22,
    fontWeight: 'bold',
  },
});
