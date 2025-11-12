import { useRouter, Link, useLocalSearchParams } from 'expo-router';
import { StyleSheet, View, ScrollView, TouchableOpacity, Image, ActivityIndicator, Alert, Linking } from 'react-native';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { useState, useEffect } from 'react';
import { api } from '@/services/api';
import type { ExpenseSheet } from '@/types/api';

type StatusType = 'draft' | 'pending' | 'approved' | 'rejected';

function getStatusFromApproved(approved: boolean | null | undefined, isDraft: boolean): StatusType {
  if (isDraft) return 'draft';
  if (approved === 1) return 'approved';
  if (approved === 0) return 'rejected';
  return 'pending';
}

function getStatusColor(status: StatusType) {
  switch (status) {
    case 'draft':
      return '#8E8E93';
    case 'pending':
      return '#FF9500';
    case 'approved':
      return '#34C759';
    case 'rejected':
      return '#FF3B30';
    default:
      return '#8E8E93';
  }
}

function getStatusLabel(status: StatusType) {
  switch (status) {
    case 'draft':
      return 'Brouillon';
    case 'pending':
      return 'En attente de validation';
    case 'approved':
      return 'Approuvée';
    case 'rejected':
      return 'Rejetée';
    default:
      return status;
  }
}

export default function ExpenseDetailScreen() {
  const { id } = useLocalSearchParams();
  const router = useRouter();
  const colorScheme = useColorScheme() ?? 'light';
  const isDark = colorScheme === 'dark';

  const [expenseSheet, setExpenseSheet] = useState<ExpenseSheet | null>(null);
  const [canEdit, setCanEdit] = useState(false);
  const [canApprove, setCanApprove] = useState(false);
  const [canReject, setCanReject] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [isProcessing, setIsProcessing] = useState(false);

  const openUrl = async (url: string) => {
    try {
      const supported = await Linking.canOpenURL(url);
      if (supported) {
        await Linking.openURL(url);
      } else {
        Alert.alert('Erreur', `Impossible d'ouvrir l'URL: ${url}`);
      }
    } catch (error) {
      Alert.alert('Erreur', `Une erreur est survenue lors de l'ouverture du lien`);
      console.error('Error opening URL:', error);
    }
  };

  useEffect(() => {
    if (id) {
      fetchExpenseSheet();
    }
  }, [id]);

  const fetchExpenseSheet = async () => {
    try {
      setIsLoading(true);
      const response = await api.getExpenseSheetDetails(Number(id));
      setExpenseSheet(response.expenseSheet);
      setCanEdit(response.canEdit);
      setCanApprove(response.canApprove);
      setCanReject(response.canReject);
    } catch (error) {
      console.error('Error fetching expense sheet:', error);
      Alert.alert(
        'Erreur',
        'Impossible de charger la note de frais',
        [
          { text: 'Réessayer', onPress: fetchExpenseSheet },
          { text: 'Retour', onPress: () => router.back() },
        ]
      );
    } finally {
      setIsLoading(false);
    }
  };

  const handleApprove = async () => {
    if (!expenseSheet) return;

    Alert.alert(
      'Approuver la note de frais',
      `Voulez-vous approuver la note de frais de ${expenseSheet.user?.name} pour un montant de ${(Number(expenseSheet.total) || 0).toFixed(2)} € ?\n\nCette action est irréversible.`,
      [
        { text: 'Annuler', style: 'cancel' },
        {
          text: 'Approuver',
          style: 'default',
          onPress: async () => {
            try {
              setIsProcessing(true);
              await api.approveExpenseSheet(Number(id), true);
              Alert.alert(
                'Succès',
                'Note de frais approuvée avec succès',
                [{ text: 'OK', onPress: () => router.back() }]
              );
            } catch (error) {
              console.error('Error approving expense sheet:', error);
              Alert.alert(
                'Erreur',
                error instanceof Error ? error.message : 'Impossible d\'approuver la note de frais'
              );
            } finally {
              setIsProcessing(false);
            }
          },
        },
      ]
    );
  };

  const handleReject = async () => {
    if (!expenseSheet) return;

    Alert.prompt(
      'Rejeter la note de frais',
      `Veuillez indiquer la raison du rejet de la note de frais de ${expenseSheet.user?.name} :`,
      [
        { text: 'Annuler', style: 'cancel' },
        {
          text: 'Rejeter',
          style: 'destructive',
          onPress: async (reason?: string) => {
            if (!reason || reason.trim() === '') {
              Alert.alert('Erreur', 'Veuillez indiquer une raison pour le rejet');
              return;
            }
            try {
              setIsProcessing(true);
              await api.approveExpenseSheet(Number(id), false, reason);
              Alert.alert(
                'Succès',
                'Note de frais rejetée',
                [{ text: 'OK', onPress: () => router.back() }]
              );
            } catch (error) {
              console.error('Error rejecting expense sheet:', error);
              Alert.alert(
                'Erreur',
                error instanceof Error ? error.message : 'Impossible de rejeter la note de frais'
              );
            } finally {
              setIsProcessing(false);
            }
          },
        },
      ],
      'plain-text'
    );
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

  const status = getStatusFromApproved(expenseSheet.approved, expenseSheet.is_draft);
  const statusColor = getStatusColor(status);

  return (
    <ThemedView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.closeButton}>
          <IconSymbol size={28} name="xmark.circle.fill" color={isDark ? '#fff' : '#000'} />
        </TouchableOpacity>
        {canEdit && (
          <Link href={`/expense/edit/${expenseSheet.id}`} asChild>
            <TouchableOpacity style={styles.editButton}>
              <IconSymbol size={24} name="pencil" color={Colors[colorScheme].tint} />
            </TouchableOpacity>
          </Link>
        )}
      </View>

      <ScrollView style={styles.scrollView} showsVerticalScrollIndicator={false}>
        <View style={styles.content}>

          {/* Informations de validation/statut - EN HAUT */}
          {(status === 'approved' || status === 'rejected' || status === 'pending') && (
            <ThemedView style={[
              styles.card,
              status === 'rejected' ? styles.rejectionCard :
              status === 'approved' ? styles.validationCard :
              styles.pendingCard
            ]}>
              <View style={styles.validationHeader}>
                <IconSymbol
                  size={28}
                  name={
                    status === 'approved' ? 'checkmark.circle.fill' :
                    status === 'rejected' ? 'exclamationmark.triangle.fill' :
                    'clock.fill'
                  }
                  color={
                    status === 'approved' ? '#34C759' :
                    status === 'rejected' ? '#FF3B30' :
                    '#FF9500'
                  }
                />
                <ThemedText style={[
                  styles.validationHeaderTitle,
                  {
                    color: status === 'approved' ? '#34C759' :
                           status === 'rejected' ? '#FF3B30' :
                           '#FF9500'
                  }
                ]}>
                  {status === 'approved' ? 'Note approuvée' :
                   status === 'rejected' ? 'Note rejetée' :
                   'Demande en attente de validation'}
                </ThemedText>
              </View>

              {/* Informations du validateur (pour approved et rejected) */}
              {(status === 'approved' || status === 'rejected') && expenseSheet.validated_by && (
                <View style={styles.validationInfo}>
                  <View style={styles.validationRow}>
                    <IconSymbol size={20} name="person.fill" color={isDark ? '#999' : '#666'} />
                    <ThemedText style={styles.validationLabel}>
                      {status === 'approved' ? 'Approuvé par :' : 'Rejeté par :'}
                    </ThemedText>
                    <ThemedText style={styles.validationValue}>{expenseSheet.validated_by.name}</ThemedText>
                  </View>

                  {expenseSheet.validated_at && (
                    <View style={styles.validationRow}>
                      <IconSymbol size={20} name="calendar" color={isDark ? '#999' : '#666'} />
                      <ThemedText style={styles.validationLabel}>Date :</ThemedText>
                      <ThemedText style={styles.validationValue}>
                        {new Date(expenseSheet.validated_at).toLocaleDateString('fr-FR', {
                          day: 'numeric',
                          month: 'long',
                          year: 'numeric',
                          hour: '2-digit',
                          minute: '2-digit',
                        })}
                      </ThemedText>
                    </View>
                  )}
                </View>
              )}

              {/* Message pour pending */}
              {status === 'pending' && (
                <View style={styles.pendingInfo}>
                  <ThemedText style={styles.pendingMessage}>
                    Votre note de frais est en cours de traitement par votre responsable.
                  </ThemedText>
                </View>
              )}

              {/* Raison du rejet */}
              {status === 'rejected' && expenseSheet.refusal_reason && (
                <>
                  <View style={styles.rejectionDivider} />
                  <View style={styles.rejectionReasonSection}>
                    <View style={styles.rejectionReasonHeader}>
                      <IconSymbol size={20} name="exclamationmark.bubble.fill" color="#FF3B30" />
                      <ThemedText style={styles.rejectionReasonLabel}>Raison du rejet</ThemedText>
                    </View>
                    <View style={styles.rejectionReasonContent}>
                      <ThemedText style={styles.rejectionReason}>{expenseSheet.refusal_reason}</ThemedText>
                    </View>
                  </View>
                </>
              )}
            </ThemedView>
          )}

          {/* Montant */}
          <View style={styles.amountSection}>
            <ThemedText style={styles.amount}>{(Number(expenseSheet.total) || 0).toFixed(2)} €</ThemedText>
            <ThemedText style={styles.title}>{expenseSheet.form?.name}</ThemedText>
          </View>

          {/* Informations principales */}
          <ThemedView style={styles.card}>
            <View style={styles.infoRow}>
              <View style={styles.infoLabel}>
                <IconSymbol size={20} name="calendar" color={isDark ? '#999' : '#666'} />
                <ThemedText style={styles.labelText}>Date de création</ThemedText>
              </View>
              <ThemedText style={styles.valueText}>
                {new Date(expenseSheet.created_at).toLocaleDateString('fr-FR', {
                  weekday: 'long',
                  day: 'numeric',
                  month: 'long',
                  year: 'numeric',
                })}
              </ThemedText>
            </View>

            <View style={[styles.divider, { backgroundColor: isDark ? 'rgba(255, 255, 255, 0.1)' : '#e0e0e0' }]} />

            <View style={styles.infoRow}>
              <View style={styles.infoLabel}>
                <IconSymbol size={20} name="tag" color={isDark ? '#999' : '#666'} />
                <ThemedText style={styles.labelText}>Formulaire</ThemedText>
              </View>
              <ThemedText style={styles.valueText}>{expenseSheet.form?.name}</ThemedText>
            </View>

            {expenseSheet.user && (
              <>
                <View style={[styles.divider, { backgroundColor: isDark ? 'rgba(255, 255, 255, 0.1)' : '#e0e0e0' }]} />
                <View style={styles.infoRow}>
                  <View style={styles.infoLabel}>
                    <IconSymbol size={20} name="person.fill" color={isDark ? '#999' : '#666'} />
                    <ThemedText style={styles.labelText}>Demandeur</ThemedText>
                  </View>
                  <ThemedText style={styles.valueText}>{expenseSheet.user.name}</ThemedText>
                </View>
              </>
            )}

            {expenseSheet.department && (
              <>
                <View style={[styles.divider, { backgroundColor: isDark ? 'rgba(255, 255, 255, 0.1)' : '#e0e0e0' }]} />
                <View style={styles.infoRow}>
                  <View style={styles.infoLabel}>
                    <IconSymbol size={20} name="building.2" color={isDark ? '#999' : '#666'} />
                    <ThemedText style={styles.labelText}>Département</ThemedText>
                  </View>
                  <ThemedText style={styles.valueText}>{expenseSheet.department.name}</ThemedText>
                </View>
              </>
            )}

            {expenseSheet.distance && (
              <>
                <View style={[styles.divider, { backgroundColor: isDark ? 'rgba(255, 255, 255, 0.1)' : '#e0e0e0' }]} />
                <View style={styles.infoRow}>
                  <View style={styles.infoLabel}>
                    <IconSymbol size={20} name="car.fill" color={isDark ? '#999' : '#666'} />
                    <ThemedText style={styles.labelText}>Distance</ThemedText>
                  </View>
                  <ThemedText style={styles.valueText}>{expenseSheet.distance} km</ThemedText>
                </View>
              </>
            )}
          </ThemedView>

          {/* Détails des frais */}
          {expenseSheet.costs && expenseSheet.costs.length > 0 && (
            <View>
              <ThemedText style={styles.sectionTitle}>Détails des frais</ThemedText>
              {expenseSheet.costs.map((cost, index) => (
                <ThemedView key={cost.id} style={styles.card}>
                  {/* En-tête du coût */}
                  <View style={[styles.costHeader, { borderBottomColor: isDark ? 'rgba(255, 255, 255, 0.1)' : '#e0e0e0' }]}>
                    <View style={styles.costInfo}>
                      <ThemedText style={styles.costType}>{cost?.form_cost?.name || cost.type}</ThemedText>
                      <ThemedText style={styles.costTypeDetail}>{cost.type}</ThemedText>
                      <ThemedText style={styles.costDate}>
                        {new Date(cost.date).toLocaleDateString('fr-FR', {
                          day: 'numeric',
                          month: 'long',
                          year: 'numeric',
                        })}
                      </ThemedText>
                    </View>
                    <ThemedText style={styles.costAmount}>{(Number(cost.total) || 0).toFixed(2)} €</ThemedText>
                  </View>

                  {/* Détails supplémentaires */}
                  <View style={styles.costDetails}>
                    {/* Montant payé */}
                    {cost.amount != null && (
                      <View style={styles.costDetailRow}>
                        <IconSymbol size={16} name="creditcard" color={isDark ? '#999' : '#666'} />
                        <ThemedText style={styles.costDetailLabel}>Montant payé:</ThemedText>
                        <ThemedText style={styles.costDetailValue}>{(Number(cost.amount) || 0).toFixed(2)} €</ThemedText>
                      </View>
                    )}

                    {/* Trajet - Route */}
                    {(() => {
                      // Utiliser le trajet du coût ou de l'expense sheet
                      const routeData = cost.route || expenseSheet.route;
                      if (!routeData) return null;

                      // Format: { departure, steps: [{address}], arrival, google_km, manual_km }
                      const totalKm = routeData.google_km && routeData.manual_km
                        ? Math.round(routeData.google_km + routeData.manual_km)
                        : null;

                      return (
                        <View style={[styles.routeSection, { backgroundColor: isDark ? '#1C1C1E' : '#f5f5f5' }]}>
                          <View style={styles.routeHeader}>
                            <IconSymbol size={16} name="car.fill" color={Colors[colorScheme].tint} />
                            <ThemedText style={styles.routeTitle}>Route</ThemedText>
                          </View>

                          {/* Départ */}
                          {routeData.departure && (
                            <View style={styles.routePoint}>
                              <View style={[styles.routeMarker, { backgroundColor: '#34C759' }]}>
                                <ThemedText style={styles.routeMarkerText}>D</ThemedText>
                              </View>
                              <View style={styles.routeContent}>
                                <ThemedText style={styles.routeLabel}>Départ</ThemedText>
                                <ThemedText style={styles.routeAddress}>{routeData.departure}</ThemedText>
                              </View>
                            </View>
                          )}

                          {/* Étapes */}
                          {routeData.steps && Array.isArray(routeData.steps) && routeData.steps.length > 0 && (
                            <>
                              <ThemedText style={styles.routeStepsLabel}>Étapes:</ThemedText>
                              {routeData.steps.map((step: any, idx: number) => (
                                <View key={idx} style={styles.routePoint}>
                                  <View style={styles.routeMarker}>
                                    <ThemedText style={styles.routeMarkerText}>{idx + 1}</ThemedText>
                                  </View>
                                  <ThemedText style={styles.routeAddress}>
                                    {step.address || step.name || step}
                                  </ThemedText>
                                </View>
                              ))}
                            </>
                          )}

                          {/* Arrivée */}
                          {routeData.arrival && (
                            <View style={styles.routePoint}>
                              <View style={[styles.routeMarker, { backgroundColor: '#FF3B30' }]}>
                                <ThemedText style={styles.routeMarkerText}>A</ThemedText>
                              </View>
                              <View style={styles.routeContent}>
                                <ThemedText style={styles.routeLabel}>Arrivée</ThemedText>
                                <ThemedText style={styles.routeAddress}>{routeData.arrival}</ThemedText>
                              </View>
                            </View>
                          )}

                          {/* Total KM */}
                          {totalKm && (
                            <View style={[styles.routeTotalKm, { borderTopColor: isDark ? 'rgba(255, 255, 255, 0.1)' : '#e0e0e0' }]}>
                              <ThemedText style={styles.routeTotalKmLabel}>Total des KM:</ThemedText>
                              <ThemedText style={styles.routeTotalKmValue}>{totalKm} km</ThemedText>
                            </View>
                          )}
                        </View>
                      );
                    })()}

                    {/* Annexes / Requirements */}
                    {(() => {
                      // Utiliser requirements_with_urls qui contient les URLs complètes
                      const requirementsData = cost.requirements_with_urls || cost.requirements;
                      if (!requirementsData) return null;

                      // Parser les requirements (peuvent être une string JSON ou déjà un objet)
                      let reqData: Record<string, any> = {};
                      try {
                        reqData = typeof requirementsData === 'string'
                          ? JSON.parse(requirementsData)
                          : requirementsData;
                      } catch {
                        return null;
                      }

                      // Vérifier qu'on a des requirements
                      const reqKeys = Object.keys(reqData);
                      if (reqKeys.length === 0) return null;

                      return (
                        <View style={[styles.requirementsSection, { backgroundColor: isDark ? '#1C1C1E' : '#f5f5f5' }]}>
                          <View style={styles.requirementsHeader}>
                            <IconSymbol size={16} name="doc.text" color={Colors[colorScheme].tint} />
                            <ThemedText style={styles.requirementsTitle}>Annexes</ThemedText>
                          </View>
                          {reqKeys.map((key) => {
                            const req = reqData[key];
                            const hasValue = !!(req?.value || req?.file);

                            return (
                              <View key={key} style={styles.requirementItem}>
                                <IconSymbol
                                  size={16}
                                  name={hasValue ? 'checkmark.circle.fill' : 'xmark.circle.fill'}
                                  color={hasValue ? '#34C759' : '#8E8E93'}
                                />
                                <View style={styles.requirementContent}>
                                  <ThemedText style={styles.requirementName}>{key}:</ThemedText>
                                  {req?.file ? (
                                    <TouchableOpacity onPress={() => openUrl(req.file)}>
                                      <ThemedText style={styles.requirementLink}>
                                        Visualiser le fichier
                                      </ThemedText>
                                    </TouchableOpacity>
                                  ) : req?.value ? (
                                    // Vérifier si c'est une URL
                                    req.value.startsWith('http://') || req.value.startsWith('https://') ? (
                                      <TouchableOpacity onPress={() => openUrl(req.value)}>
                                        <ThemedText style={styles.requirementLink}>
                                          {req.value}
                                        </ThemedText>
                                      </TouchableOpacity>
                                    ) : (
                                      <ThemedText style={styles.requirementValue}>
                                        {req.value}
                                      </ThemedText>
                                    )
                                  ) : (
                                    <ThemedText style={styles.requirementEmpty}>
                                      Non fourni
                                    </ThemedText>
                                  )}
                                </View>
                              </View>
                            );
                          })}
                        </View>
                      );
                    })()}

                  </View>
                </ThemedView>
              ))}
            </View>
          )}

          {/* Actions de validation (pour les managers) */}
          {(canApprove || canReject) && status === 'pending' && (
            <View style={styles.validationActions}>
              {canReject && (
                <TouchableOpacity
                  style={[styles.validationButton, styles.rejectButton]}
                  onPress={handleReject}
                  disabled={isProcessing}
                >
                  {isProcessing ? (
                    <ActivityIndicator size="small" color="#fff" />
                  ) : (
                    <>
                      <IconSymbol size={20} name="xmark.circle.fill" color="#fff" />
                      <ThemedText style={styles.validationButtonText}>Rejeter</ThemedText>
                    </>
                  )}
                </TouchableOpacity>
              )}
              {canApprove && (
                <TouchableOpacity
                  style={[styles.validationButton, styles.approveButton]}
                  onPress={handleApprove}
                  disabled={isProcessing}
                >
                  {isProcessing ? (
                    <ActivityIndicator size="small" color="#fff" />
                  ) : (
                    <>
                      <IconSymbol size={20} name="checkmark.circle.fill" color="#fff" />
                      <ThemedText style={styles.validationButtonText}>Approuver</ThemedText>
                    </>
                  )}
                </TouchableOpacity>
              )}
            </View>
          )}
        </View>
      </ScrollView>

      {/* Actions de soumission (pour brouillons) */}
      {status === 'draft' && canEdit && (
        <View style={[styles.actions, { borderTopColor: isDark ? 'rgba(255, 255, 255, 0.1)' : '#e0e0e0' }]}>
          <TouchableOpacity
            style={[styles.actionButton, styles.deleteButton]}
            onPress={() => {
              Alert.alert(
                'Supprimer la note',
                'Voulez-vous vraiment supprimer cette note de frais ?',
                [
                  { text: 'Annuler', style: 'cancel' },
                  {
                    text: 'Supprimer',
                    style: 'destructive',
                    onPress: () => {
                      // TODO: Implement delete API call
                      router.back();
                    }
                  },
                ]
              );
            }}>
            <ThemedText style={styles.deleteButtonText}>Supprimer</ThemedText>
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.actionButton, styles.submitButton, { backgroundColor: Colors[colorScheme].tint }]}
            onPress={async () => {
              Alert.alert(
                'Soumettre la note',
                'Voulez-vous soumettre cette note de frais pour validation ?',
                [
                  { text: 'Annuler', style: 'cancel' },
                  {
                    text: 'Soumettre',
                    onPress: async () => {
                      try {
                        await api.updateExpenseSheet(Number(id), { is_draft: false });
                        Alert.alert(
                          'Succès',
                          'Note de frais soumise avec succès',
                          [{ text: 'OK', onPress: () => router.back() }]
                        );
                      } catch (error) {
                        console.error('Error submitting expense sheet:', error);
                        Alert.alert(
                          'Erreur',
                          error instanceof Error ? error.message : 'Une erreur est survenue'
                        );
                      }
                    }
                  },
                ]
              );
            }}>
            <ThemedText style={styles.submitButtonText}>Soumettre</ThemedText>
          </TouchableOpacity>
        </View>
      )}
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
  editButton: {
    padding: 8,
  },
  scrollView: {
    flex: 1,
  },
  content: {
    paddingHorizontal: 20,
    paddingBottom: 100,
  },
  statusBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    padding: 16,
    borderRadius: 12,
    marginBottom: 16,
  },
  statusText: {
    fontSize: 16,
    fontWeight: '600',
    flex: 1,
  },
  amountSection: {
    marginTop: 0,
    marginBottom: 24,
    paddingTop: 8,
  },
  amount: {
    fontSize: 48,
    fontWeight: 'bold',
    marginBottom: 8,
    lineHeight: 56,
  },
  title: {
    fontSize: 20,
    fontWeight: '600',
  },
  card: {
    borderRadius: 12,
    padding: 20,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 3,
    elevation: 2,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 8,
  },
  infoLabel: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  labelText: {
    fontSize: 16,
    opacity: 0.7,
  },
  valueText: {
    fontSize: 16,
    fontWeight: '500',
    textAlign: 'right',
    flex: 1,
    marginLeft: 16,
  },
  divider: {
    height: 1,
    marginVertical: 8,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '600',
    marginBottom: 12,
    paddingHorizontal: 20,
  },
  description: {
    fontSize: 16,
    lineHeight: 24,
    opacity: 0.8,
  },
  costHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 16,
    paddingBottom: 16,
    borderBottomWidth: 1,
  },
  costInfo: {
    flex: 1,
  },
  costType: {
    fontSize: 18,
    fontWeight: '600',
    marginBottom: 2,
  },
  costTypeDetail: {
    fontSize: 13,
    opacity: 0.6,
    fontStyle: 'italic',
    marginBottom: 4,
  },
  costDate: {
    fontSize: 14,
    opacity: 0.7,
  },
  costAmount: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#34C759',
  },
  costDetails: {
    gap: 12,
  },
  costDetailRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  costDetailLabel: {
    fontSize: 14,
    opacity: 0.7,
  },
  costDetailValue: {
    fontSize: 14,
    fontWeight: '500',
  },
  routeSection: {
    marginTop: 12,
    padding: 12,
    borderRadius: 8,
  },
  routeHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 12,
  },
  routeTitle: {
    fontSize: 15,
    fontWeight: '600',
  },
  routePoint: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
    marginVertical: 8,
  },
  routeMarker: {
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: '#007AFF',
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: 2,
  },
  routeMarkerText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '700',
  },
  routeContent: {
    flex: 1,
  },
  routeLabel: {
    fontSize: 13,
    fontWeight: '600',
    opacity: 0.7,
    marginBottom: 2,
  },
  routeAddress: {
    flex: 1,
    fontSize: 14,
    lineHeight: 20,
  },
  routeStepsLabel: {
    fontSize: 13,
    fontWeight: '600',
    opacity: 0.7,
    marginTop: 8,
    marginBottom: 4,
  },
  routeTotalKm: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 12,
    paddingTop: 12,
    borderTopWidth: 1,
  },
  routeTotalKmLabel: {
    fontSize: 14,
    fontWeight: '600',
  },
  routeTotalKmValue: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#007AFF',
  },
  requirementsSection: {
    marginTop: 12,
    padding: 12,
    borderRadius: 8,
  },
  requirementsHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 12,
  },
  requirementsTitle: {
    fontSize: 15,
    fontWeight: '600',
  },
  requirementItem: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 8,
    marginVertical: 6,
  },
  requirementContent: {
    flex: 1,
  },
  requirementName: {
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 2,
  },
  requirementValue: {
    fontSize: 14,
    lineHeight: 20,
    opacity: 0.9,
  },
  requirementLink: {
    fontSize: 14,
    color: '#007AFF',
    textDecorationLine: 'underline',
  },
  requirementEmpty: {
    fontSize: 14,
    opacity: 0.5,
    fontStyle: 'italic',
  },
  costDescriptionSection: {
    marginTop: 12,
    padding: 12,
    backgroundColor: '#f9f9f9',
    borderRadius: 8,
  },
  costDescription: {
    fontSize: 13,
    lineHeight: 18,
    opacity: 0.7,
    fontStyle: 'italic',
  },
  receiptContainer: {
    marginRight: 12,
  },
  receipt: {
    width: 150,
    height: 200,
    borderRadius: 8,
    backgroundColor: '#f0f0f0',
  },
  validationActions: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 16,
  },
  validationButton: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 14,
    borderRadius: 12,
    gap: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  approveButton: {
    backgroundColor: '#34C759',
  },
  rejectButton: {
    backgroundColor: '#FF3B30',
  },
  validationButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  validationCard: {
    backgroundColor: '#34C75915',
    borderLeftWidth: 4,
    borderLeftColor: '#34C759',
  },
  rejectionCard: {
    backgroundColor: '#FF3B3015',
    borderLeftWidth: 4,
    borderLeftColor: '#FF3B30',
  },
  pendingCard: {
    backgroundColor: '#FF950015',
    borderLeftWidth: 4,
    borderLeftColor: '#FF9500',
  },
  validationHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 16,
  },
  validationHeaderTitle: {
    fontSize: 19,
    fontWeight: '700',
    flex: 1,
  },
  validationInfo: {
    gap: 14,
    paddingTop: 4,
  },
  validationRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  validationLabel: {
    fontSize: 15,
    opacity: 0.7,
    fontWeight: '600',
    minWidth: 110,
  },
  validationValue: {
    fontSize: 15,
    fontWeight: '600',
    flex: 1,
  },
  pendingInfo: {
    paddingTop: 4,
  },
  pendingMessage: {
    fontSize: 15,
    lineHeight: 22,
    opacity: 0.8,
    fontStyle: 'italic',
  },
  rejectionDivider: {
    height: 1,
    backgroundColor: '#FF3B3040',
    marginVertical: 16,
  },
  rejectionReasonSection: {
    gap: 12,
  },
  rejectionReasonHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  rejectionReasonLabel: {
    fontSize: 16,
    fontWeight: '700',
    color: '#FF3B30',
  },
  rejectionReasonContent: {
    backgroundColor: '#FF3B3008',
    borderRadius: 8,
    padding: 12,
    borderLeftWidth: 3,
    borderLeftColor: '#FF3B30',
  },
  rejectionReason: {
    fontSize: 15,
    lineHeight: 22,
    color: '#1a1a1a',
  },
  rejectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 12,
  },
  rejectionTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#FF3B30',
  },
  actions: {
    flexDirection: 'row',
    gap: 12,
    padding: 20,
    borderTopWidth: 1,
  },
  actionButton: {
    flex: 1,
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  deleteButton: {
    backgroundColor: '#FF3B3020',
  },
  deleteButtonText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#FF3B30',
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
});
