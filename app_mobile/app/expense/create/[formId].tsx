import { useRouter, useLocalSearchParams } from 'expo-router';
import { StyleSheet, View, ScrollView, TouchableOpacity, ActivityIndicator, Alert, Platform, ActionSheetIOS } from 'react-native';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { useState, useEffect } from 'react';
import { api } from '@/services/api';
import type { FormDetailsResponse, FormCost, Department } from '@/types/api';
import { CostCard } from '@/components/expense/CostCard';
import { CostPicker } from '@/components/expense/CostPicker';

interface SelectedCost extends FormCost {
  _index: number;
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
  reimbursementAmount?: number; // Montant total du remboursement (pour km)
  requirements?: Record<string, any>;
}

export default function CreateExpenseScreen() {
  const { formId } = useLocalSearchParams();
  const router = useRouter();
  const colorScheme = useColorScheme() ?? 'light';
  const isDark = colorScheme === 'dark';

  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isDraftSubmit, setIsDraftSubmit] = useState(false);

  const [formData, setFormData] = useState<FormDetailsResponse | null>(null);
  const [availableCosts, setAvailableCosts] = useState<FormCost[]>([]);
  const [departments, setDepartments] = useState<Department[]>([]);

  const [selectedDepartmentId, setSelectedDepartmentId] = useState<number | null>(null);
  const [selectedCosts, setSelectedCosts] = useState<SelectedCost[]>([]);
  const [costData, setCostData] = useState<CostData[]>([]);
  const [submitted, setSubmitted] = useState(false);

  useEffect(() => {
    if (formId) {
      fetchFormDetails();
    }
  }, [formId]);

  const fetchFormDetails = async () => {
    try {
      setIsLoading(true);
      const response = await api.getFormDetails(Number(formId));

      // Debug - Vérifier le format des données
      console.log('RAW API Response first cost:', JSON.stringify(response.form.costs?.[0], null, 2));

      // Transformer les données si elles sont en snake_case
      const transformedCosts = response.form.costs?.map((cost: any) => {
        const transformedCost = { ...cost };

        // Transformer reimbursement_rates en camelCase
        if (cost.reimbursement_rates && !cost.reimbursementRates) {
          console.log('Transforming reimbursement_rates to camelCase for cost:', cost.name);
          transformedCost.reimbursementRates = cost.reimbursement_rates;
        }

        // Transformer requirements (requirement_type en camelCase)
        if (cost.requirements && Array.isArray(cost.requirements)) {
          transformedCost.requirements = cost.requirements.map((req: any) => ({
            ...req,
            requirement_type: req.requirement_type || req.requirementType,
          }));
        }

        return transformedCost;
      }) || [];

      console.log('Transformed costs:', transformedCosts.map((c: any) => ({
        name: c.name,
        hasReimbursementRates: !!c.reimbursementRates,
        ratesCount: c.reimbursementRates?.length
      })));

      setFormData({
        ...response,
        form: {
          ...response.form,
          costs: transformedCosts,
        },
      });
      setAvailableCosts(transformedCosts);
      setDepartments(response.departments);

      console.log('Departments loaded:', response.departments);
      console.log('Number of departments:', response.departments.length);
    } catch (error) {
      console.error('Error fetching form details:', error);
      Alert.alert(
        'Erreur',
        'Impossible de charger le formulaire',
        [
          { text: 'Réessayer', onPress: fetchFormDetails },
          { text: 'Retour', onPress: () => router.back() },
        ]
      );
    } finally {
      setIsLoading(false);
    }
  };

  const selectedDepartment = departments.find(d => d.id === selectedDepartmentId);

  const showDepartmentPicker = () => {
    if (departments.length === 0) {
      Alert.alert('Aucun département', 'Aucun département disponible');
      return;
    }

    if (Platform.OS === 'ios') {
      const options = ['Annuler', ...departments.map(d => d.name)];
      ActionSheetIOS.showActionSheetWithOptions(
        {
          options,
          cancelButtonIndex: 0,
        },
        (buttonIndex) => {
          if (buttonIndex > 0) {
            setSelectedDepartmentId(departments[buttonIndex - 1].id);
          }
        }
      );
    } else {
      // Android: Use Alert with buttons
      Alert.alert(
        'Sélectionner un département',
        '',
        [
          { text: 'Annuler', style: 'cancel' },
          ...departments.map(dept => ({
            text: dept.name,
            onPress: () => setSelectedDepartmentId(dept.id),
          })),
        ],
        { cancelable: true }
      );
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
      newCosts.push({ ...originalCost, _index: Date.now() + i });
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
              if (!value) {
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

        // Add cost data
        Object.entries(data).forEach(([key, value]) => {
          if (value !== null && value !== undefined) {
            formData.append(`costs[${index}][data][${key}]`, value.toString());
          }
        });

        // Add requirements
        if (costData[index].requirements) {
          Object.entries(costData[index].requirements).forEach(([reqId, req]) => {
            if (req?.uri) {
              // Handle file uploads (React Native)
              const file: any = {
                uri: req.uri,
                name: req.name || `file_${reqId}`,
                type: req.type || 'application/octet-stream',
              };
              formData.append(`costs[${index}][requirements][${reqId}][file]`, file);
            } else if (req !== null && req !== undefined && req !== '') {
              // Handle text values
              formData.append(`costs[${index}][requirements][${reqId}][value]`, req.toString());
            }
          });
        }
      });

      // Call API
      await api.createExpenseSheet(Number(formId), formData);

      Alert.alert(
        'Succès',
        isDraft ? 'Brouillon enregistré avec succès' : 'Note de frais soumise avec succès',
        [{ text: 'OK', onPress: () => router.back() }]
      );
    } catch (error) {
      console.error('Error submitting expense sheet:', error);
      Alert.alert('Erreur', error instanceof Error ? error.message : 'Une erreur est survenue lors de l\'envoi');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (isLoading || !formData) {
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
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.closeButton}>
          <IconSymbol size={28} name="xmark.circle.fill" color={isDark ? '#fff' : '#000'} />
        </TouchableOpacity>
        <ThemedText style={styles.headerTitle}>Créer une note de frais</ThemedText>
        <View style={styles.placeholder} />
      </View>

      <ScrollView style={styles.scrollView} showsVerticalScrollIndicator={false}>
        <View style={styles.content}>
          {/* Form name */}
          <View style={styles.formHeader}>
            <IconSymbol size={24} name="doc.text.fill" color={Colors[colorScheme].tint} />
            <ThemedText style={styles.formName}>{formData.form.name}</ThemedText>
          </View>

          {formData.form.description && (
            <ThemedText style={styles.formDescription}>{formData.form.description}</ThemedText>
          )}

          {/* Department selection */}
          <View style={styles.section}>
            <ThemedText style={styles.sectionLabel}>
              Département <ThemedText style={styles.required}>*</ThemedText>
            </ThemedText>
            <TouchableOpacity onPress={showDepartmentPicker}>
              <ThemedView style={[styles.selectButton, isDark && styles.selectButtonDark]}>
                <ThemedText style={[styles.selectButtonText, !selectedDepartmentId && styles.placeholderText]}>
                  {selectedDepartment?.name || 'Sélectionner un département'}
                </ThemedText>
                <IconSymbol size={20} name="chevron.down" color={isDark ? '#999' : '#666'} />
              </ThemedView>
            </TouchableOpacity>
            {departments.length > 0 && (
              <ThemedText style={styles.helperText}>
                {departments.length} département(s) disponible(s)
              </ThemedText>
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
                  .filter(item => item.cost.type === 'km' && item.data.kmData?.totalKm);

                if (kmCosts.length === 0) return null;

                console.log('KM Summary - kmCosts:', kmCosts.map(item => ({
                  costName: item.cost.name,
                  date: item.data.date,
                  km: item.data.kmData?.totalKm,
                  reimbursement: item.data.reimbursementAmount,
                  rates: item.cost.reimbursementRates
                })));

                const totalKm = kmCosts.reduce((sum, item) => sum + (item.data.kmData?.totalKm || 0), 0);
                const totalAmount = kmCosts.reduce((sum, item) => sum + (item.data.reimbursementAmount || 0), 0);

                // Calculate average rate (weighted by km)
                const weightedRateSum = kmCosts.reduce((sum, item) => {
                  const rate = getActiveRate(item.cost, item.data.date || new Date().toISOString().split('T')[0]);
                  const km = item.data.kmData?.totalKm || 0;
                  console.log('KM Summary - Rate calculation:', {
                    costName: item.cost.name,
                    date: item.data.date,
                    rate,
                    km,
                    weighted: rate * km
                  });
                  return sum + (rate * km);
                }, 0);
                const averageRate = totalKm > 0 ? weightedRateSum / totalKm : 0;

                console.log('KM Summary - Final:', {
                  totalKm,
                  totalAmount,
                  weightedRateSum,
                  averageRate
                });

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
      <View style={styles.actions}>
        <TouchableOpacity
          style={[styles.actionButton, styles.draftButton]}
          onPress={handleSaveDraft}
          disabled={isSubmitting || selectedCosts.length === 0}
        >
          {isSubmitting && isDraftSubmit ? (
            <ActivityIndicator size="small" color="#666" />
          ) : (
            <ThemedText style={styles.draftButtonText}>Brouillon</ThemedText>
          )}
        </TouchableOpacity>
        <TouchableOpacity
          style={[
            styles.actionButton,
            styles.submitButton,
            { backgroundColor: Colors[colorScheme].tint },
            (isSubmitting || selectedCosts.length === 0) && styles.buttonDisabled,
          ]}
          onPress={handleSubmitForm}
          disabled={isSubmitting || selectedCosts.length === 0}
        >
          {isSubmitting && !isDraftSubmit ? (
            <ActivityIndicator size="small" color="#fff" />
          ) : (
            <ThemedText style={styles.submitButtonText}>Envoyer</ThemedText>
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
    fontSize: 18,
    fontWeight: '600',
  },
  placeholder: {
    width: 36,
  },
  scrollView: {
    flex: 1,
  },
  content: {
    paddingHorizontal: 20,
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
  selectButtonText: {
    fontSize: 16,
    flex: 1,
  },
  placeholderText: {
    opacity: 0.5,
  },
  helperText: {
    fontSize: 12,
    opacity: 0.6,
    marginTop: 6,
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
  draftButtonText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#666',
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
