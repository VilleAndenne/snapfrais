import { useRouter, Link, useFocusEffect } from 'expo-router';
import { StyleSheet, ScrollView, View, Text, TouchableOpacity, Platform, TextInput, ActivityIndicator, RefreshControl, Alert } from 'react-native';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { useState, useMemo, useEffect, useCallback } from 'react';
import { useAuth } from '@/contexts/auth-context';
import { api } from '@/services/api';
import type { Form, ExpenseSheet } from '@/types/api';

type StatusType = 'draft' | 'pending' | 'approved' | 'rejected';

function getStatusFromApproved(approved: boolean | null, isDraft: boolean): StatusType {
  if (isDraft) return 'draft';
  if (approved === 1) return 'approved';
  if (approved === 0) return 'rejected';
  return 'pending';
}

function getStatusColor(status: StatusType) {
  switch (status) {
    case 'pending':
      return '#FF9500';
    case 'approved':
      return '#34C759';
    case 'rejected':
      return '#FF3B30';
    case 'draft':
      return '#8E8E93';
    default:
      return '#8E8E93';
  }
}

function getStatusLabel(status: StatusType) {
  switch (status) {
    case 'pending':
      return 'En attente';
    case 'approved':
      return 'Approuvée';
    case 'rejected':
      return 'Rejetée';
    case 'draft':
      return 'Brouillon';
    default:
      return status;
  }
}

function getStatusIcon(status: StatusType): string {
  switch (status) {
    case 'approved':
      return 'checkmark.circle.fill';
    case 'pending':
      return 'clock.fill';
    case 'rejected':
      return 'exclamationmark.triangle.fill';
    case 'draft':
      return 'doc.text';
    default:
      return 'doc.text';
  }
}

export default function DashboardScreen() {
  const colorScheme = useColorScheme() ?? 'light';
  const isDark = colorScheme === 'dark';
  const { user, logout, refreshUser } = useAuth();
  const router = useRouter();

  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState<'all' | StatusType>('all');
  const [forms, setForms] = useState<Form[]>([]);
  const [expenseSheets, setExpenseSheets] = useState<ExpenseSheet[]>([]);
  const [toValidate, setToValidate] = useState<ExpenseSheet[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [hasInitiallyLoaded, setHasInitiallyLoaded] = useState(false);

  // Refresh user data on mount
  useEffect(() => {
    const init = async () => {
      try {
        await refreshUser(); // Refresh user data from API
        console.log('Dashboard - User refreshed');
      } catch (error) {
        console.error('Error refreshing user:', error);
      }
    };
    init();
  }, []);

  // Fetch data when user is available or changes
  useEffect(() => {
    if (user) {
      console.log('Dashboard - User available, fetching data. is_head:', user.is_head);
      fetchData();
    }
  }, [user?.is_head]);

  // Refresh data when screen comes into focus (after submitting/validating expense sheets)
  useFocusEffect(
    useCallback(() => {
      console.log('Dashboard - Screen focused, refreshing data');
      // Only refresh if we've already loaded data once (avoid double loading on mount)
      if (user && hasInitiallyLoaded) {
        fetchData();
      }
    }, [user, hasInitiallyLoaded])
  );

  const fetchData = async () => {
    try {
      setIsLoading(true);

      // Fetch forms and expense sheets in parallel
      const [formsResponse, expenseSheetsResponse, toValidateResponse] = await Promise.all([
        api.getForms().catch(() => ({ forms: [] })),
        api.getExpenseSheets().catch(() => ({ expenseSheets: [] })),
        user?.is_head ? api.getExpenseSheetsToValidate().catch(() => ({ expenseSheets: [] })) : Promise.resolve({ expenseSheets: [] }),
      ]);

      console.log('Dashboard - Expense sheets received:', expenseSheetsResponse.expenseSheets.length);
      console.log('Dashboard - Expense sheets statuses:', expenseSheetsResponse.expenseSheets.map(s => ({
        id: s.id,
        is_draft: s.is_draft,
        approved: s.approved,
        status: getStatusFromApproved(s.approved ?? null, s.is_draft)
      })));

      console.log('Dashboard - TO VALIDATE received:', toValidateResponse.expenseSheets.length);
      console.log('Dashboard - TO VALIDATE details:', toValidateResponse.expenseSheets.map(s => ({
        id: s.id,
        user_id: s.user_id,
        user_name: s.user?.name,
        is_draft: s.is_draft,
        approved: s.approved,
        form_name: s.form?.name,
        total: s.total
      })));
      console.log('Dashboard - Current user:', {
        id: user?.id,
        name: user?.name,
        is_head: user?.is_head,
        is_admin: user?.is_admin
      });
      console.log('Dashboard - ToValidate response:', toValidateResponse.expenseSheets.length, 'sheets');

      setForms(formsResponse.forms);
      setExpenseSheets(expenseSheetsResponse.expenseSheets);
      if (user?.is_head) {
        console.log('Dashboard - Setting toValidate with', toValidateResponse.expenseSheets.length, 'sheets');
        setToValidate(toValidateResponse.expenseSheets);
      } else {
        console.log('Dashboard - User is NOT head, skipping toValidate');
      }
    } catch (error) {
      console.error('Error fetching dashboard data:', error);
      Alert.alert(
        'Erreur',
        'Impossible de charger les données. Veuillez réessayer.',
        [
          { text: 'Réessayer', onPress: fetchData },
          { text: 'Déconnexion', onPress: logout, style: 'destructive' },
        ]
      );
    } finally {
      setIsLoading(false);
      setHasInitiallyLoaded(true);
    }
  };

  const onRefresh = useCallback(async () => {
    setIsRefreshing(true);
    await fetchData();
    setIsRefreshing(false);
  }, []);

  // Filtrer les notes de frais du mois en cours
  const filteredExpenseSheets = useMemo(() => {
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();

    const filtered = expenseSheets.filter(sheet => {
      // Filtrer par mois en cours
      const sheetDate = new Date(sheet.created_at);
      const isCurrentMonth = sheetDate.getMonth() === currentMonth &&
                             sheetDate.getFullYear() === currentYear;

      // Filtrer par recherche
      const matchesSearch = sheet.form?.name.toLowerCase().includes(searchQuery.toLowerCase());

      // Filtrer par statut
      const status = getStatusFromApproved(sheet.approved ?? null, sheet.is_draft);
      const matchesStatus = statusFilter === 'all' || status === statusFilter;

      const shouldInclude = isCurrentMonth && matchesSearch && matchesStatus;

      console.log('Dashboard - Filtering sheet:', {
        id: sheet.id,
        created_at: sheet.created_at,
        isCurrentMonth,
        matchesSearch,
        status,
        statusFilter,
        matchesStatus,
        shouldInclude
      });

      return shouldInclude;
    });

    console.log('Dashboard - Filtered result:', {
      total: expenseSheets.length,
      filtered: filtered.length,
      statuses: filtered.map(s => getStatusFromApproved(s.approved ?? null, s.is_draft))
    });

    return filtered;
  }, [expenseSheets, searchQuery, statusFilter]);

  const filteredToValidate = useMemo(() => {
    // Afficher toutes les notes à valider sans filtre de date
    // (contrairement à "Mes notes de frais" qui filtre par mois en cours)
    return toValidate;
  }, [toValidate]);

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
  };

  const handleApprove = async (sheetId: number) => {
    // Trouver la note de frais pour afficher les détails
    const sheet = toValidate.find(s => s.id === sheetId);
    const userName = sheet?.user?.name || 'Utilisateur inconnu';
    const amount = sheet?.total ? `${sheet.total.toFixed(2)} €` : '0.00 €';

    Alert.alert(
      'Valider la note de frais',
      `Voulez-vous valider la note de frais de ${userName} pour un montant de ${amount} ?\n\nCette action est irréversible.`,
      [
        { text: 'Annuler', style: 'cancel' },
        {
          text: 'Valider',
          style: 'default',
          onPress: async () => {
            try {
              await api.approveExpenseSheet(sheetId, true);
              Alert.alert('Succès', 'Note de frais validée avec succès');
              await fetchData();
            } catch (error) {
              console.error('Error approving expense sheet:', error);
              Alert.alert('Erreur', 'Impossible de valider la note de frais');
            }
          },
        },
      ]
    );
  };

  const handleReject = async (sheetId: number) => {
    Alert.prompt(
      'Rejeter la note de frais',
      'Veuillez indiquer la raison du rejet :',
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
              await api.approveExpenseSheet(sheetId, false, reason);
              Alert.alert('Succès', 'Note de frais rejetée');
              await fetchData();
            } catch (error) {
              console.error('Error rejecting expense sheet:', error);
              Alert.alert('Erreur', 'Impossible de rejeter la note de frais');
            }
          },
        },
      ],
      'plain-text'
    );
  };

  // Loading state
  if (isLoading) {
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
      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={isRefreshing}
            onRefresh={onRefresh}
            tintColor={Colors[colorScheme].tint}
          />
        }
      >
        {/* Header */}
        <View style={styles.header}>
          <View>
            <ThemedText type="title" style={styles.headerTitle}>
              Tableau de bord
            </ThemedText>
            <ThemedText style={styles.welcomeText}>
              Bonjour, {user?.name}
            </ThemedText>
          </View>
          <TouchableOpacity
            style={[styles.logoutButton, { backgroundColor: isDark ? '#2C2C2E' : '#E5E5EA' }]}
            onPress={logout}
          >
            <IconSymbol size={20} name="arrow.right.square" color={Colors[colorScheme].tint} />
          </TouchableOpacity>
        </View>

        {/* Formulaires disponibles */}
        <View style={styles.section}>
          <ThemedText type="subtitle" style={styles.sectionTitle}>
            Formulaires disponibles
          </ThemedText>
          <View style={styles.formsGrid}>
            {forms.map((form) => (
              <TouchableOpacity
                key={form.id}
                onPress={() => {
                  router.push(`/expense/create/${form.id}`);
                }}
              >
                <ThemedView style={styles.formCard}>
                  <ThemedText style={styles.formName}>{form.name}</ThemedText>
                  <ThemedText style={styles.formDescription}>{form.description}</ThemedText>
                  <View style={[styles.formArrow, { backgroundColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 122, 255, 0.1)' }]}>
                    <IconSymbol
                      size={20}
                      name="chevron.right"
                      color={Colors[colorScheme].tint}
                    />
                  </View>
                </ThemedView>
              </TouchableOpacity>
            ))}
          </View>
        </View>

        {/* Filtres */}
        <View style={styles.filtersSection}>
          <View style={[styles.searchContainer, { backgroundColor: isDark ? '#1C1C1E' : '#F2F2F7' }]}>
            <IconSymbol
              size={16}
              name="magnifyingglass"
              color={isDark ? '#8E8E93' : '#8E8E93'}
              style={styles.searchIcon}
            />
            <TextInput
              style={[styles.searchInput, { color: isDark ? '#FFFFFF' : '#000000' }]}
              placeholder="Rechercher une note de frais..."
              placeholderTextColor={isDark ? '#8E8E93' : '#8E8E93'}
              value={searchQuery}
              onChangeText={setSearchQuery}
            />
          </View>
          <View style={[styles.filterContainer, { backgroundColor: isDark ? '#1C1C1E' : '#F2F2F7' }]}>
            <IconSymbol
              size={16}
              name="line.3.horizontal.decrease.circle"
              color={isDark ? '#8E8E93' : '#8E8E93'}
              style={styles.filterIcon}
            />
            <TouchableOpacity
              style={styles.filterButton}
              onPress={() => {
                // Cycle through filters
                const filters: Array<'all' | StatusType> = ['all', 'pending', 'approved', 'rejected'];
                const currentIndex = filters.indexOf(statusFilter);
                const nextIndex = (currentIndex + 1) % filters.length;
                setStatusFilter(filters[nextIndex]);
              }}
            >
              <ThemedText style={styles.filterText}>
                {statusFilter === 'all' ? 'Tous les statuts' : getStatusLabel(statusFilter)}
              </ThemedText>
            </TouchableOpacity>
          </View>
        </View>

        {/* Mes notes de frais ce mois-ci */}
        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <ThemedText type="subtitle" style={styles.sectionTitle}>
              Mes notes de frais ce mois-ci
            </ThemedText>
            <View style={[styles.badge, { backgroundColor: isDark ? '#2C2C2E' : '#E5E5EA' }]}>
              <ThemedText style={styles.badgeText}>{filteredExpenseSheets.length} note(s)</ThemedText>
            </View>
          </View>

          {filteredExpenseSheets.length > 0 ? (
            <View style={styles.expensesContainer}>
              {filteredExpenseSheets.map((sheet) => {
                const status = getStatusFromApproved(sheet.approved ?? null, sheet.is_draft);
                const statusColor = getStatusColor(status);
                return (
                  <TouchableOpacity
                    key={sheet.id}
                    onPress={() => {
                      console.log('Navigating to expense:', sheet.id);
                      router.push(`/expense/${sheet.id}`);
                    }}
                  >
                    <ThemedView style={styles.expenseCard}>
                        <View style={styles.expenseCardHeader}>
                          <View style={styles.expenseCardTitle}>
                            <IconSymbol
                              size={20}
                              name={getStatusIcon(status)}
                              color={statusColor}
                              style={styles.expenseIcon}
                            />
                            <View style={styles.expenseTextContainer}>
                              <ThemedText style={styles.expenseFormName} numberOfLines={1}>
                                {sheet.form?.name || 'Sans titre'}
                              </ThemedText>
                              <ThemedText style={styles.expenseDate}>
                                {formatDate(sheet.created_at)}
                              </ThemedText>
                            </View>
                          </View>
                          <View style={[styles.statusBadge, { backgroundColor: statusColor + '20' }]}>
                            <Text style={[styles.statusText, { color: statusColor }]}>
                              {getStatusLabel(status)}
                            </Text>
                          </View>
                        </View>
                        <View style={[styles.expenseCardFooter, { borderTopColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(128, 128, 128, 0.2)' }]}>
                          <ThemedText style={styles.expenseLabel}>Montant</ThemedText>
                          <ThemedText style={styles.expenseAmount}>{(Number(sheet.total) || 0).toFixed(2)} €</ThemedText>
                        </View>
                      </ThemedView>
                  </TouchableOpacity>
                );
              })}
            </View>
          ) : (
            <ThemedView style={[styles.emptyState, { borderColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(128, 128, 128, 0.2)' }]}>
              <IconSymbol size={48} name="doc.text" color={isDark ? '#8E8E93' : '#8E8E93'} />
              <ThemedText style={styles.emptyTitle}>Aucune note de frais</ThemedText>
              <ThemedText style={styles.emptyDescription}>
                Vous n&apos;avez pas encore créé de note de frais ou aucune ne correspond à vos filtres.
              </ThemedText>
            </ThemedView>
          )}
        </View>

        {/* Notes de frais à valider */}
        {user?.is_head && (
          <View style={styles.section}>
            <View style={styles.sectionHeader}>
              <ThemedText type="subtitle" style={styles.sectionTitle}>
                Notes de frais à valider
              </ThemedText>
              <View style={[styles.badge, { backgroundColor: isDark ? '#2C2C2E' : '#E5E5EA' }]}>
                <ThemedText style={styles.badgeText}>{filteredToValidate.length} en attente</ThemedText>
              </View>
            </View>

            {filteredToValidate.length > 0 ? (
              <View style={styles.expensesContainer}>
                {filteredToValidate.map((sheet) => {
                  const status = getStatusFromApproved(sheet.approved ?? null, sheet.is_draft);
                  const statusColor = getStatusColor(status);
                  return (
                    <TouchableOpacity
                      key={sheet.id}
                      onPress={() => router.push(`/expense/${sheet.id}`)}
                      activeOpacity={0.7}
                    >
                      <ThemedView style={styles.expenseCard}>
                        <View style={styles.expenseCardHeader}>
                          <View style={styles.expenseCardTitle}>
                            <IconSymbol
                              size={20}
                              name={getStatusIcon(status)}
                              color={statusColor}
                              style={styles.expenseIcon}
                            />
                            <View style={styles.expenseTextContainer}>
                              <ThemedText style={styles.expenseFormName} numberOfLines={1}>
                                {sheet.form?.name || 'Sans titre'}
                              </ThemedText>
                              <ThemedText style={styles.expenseDate}>
                                {sheet.user?.name || 'Utilisateur inconnu'}
                              </ThemedText>
                            </View>
                          </View>
                          <View style={[styles.statusBadge, { backgroundColor: statusColor + '20' }]}>
                            <Text style={[styles.statusText, { color: statusColor }]}>
                              {getStatusLabel(status)}
                            </Text>
                          </View>
                        </View>
                        <View style={styles.expenseCardDetails}>
                          <View style={styles.expenseDetailRow}>
                            <ThemedText style={styles.expenseLabel}>Montant</ThemedText>
                            <ThemedText style={styles.expenseAmount}>{(sheet.total ?? 0).toFixed(2)} €</ThemedText>
                          </View>
                          <View style={styles.expenseDetailRow}>
                            <ThemedText style={styles.expenseLabel}>Date de création</ThemedText>
                            <View style={styles.dateContainer}>
                              <IconSymbol size={14} name="calendar" color={isDark ? '#8E8E93' : '#8E8E93'} />
                              <ThemedText style={styles.expenseDate}>
                                {formatDate(sheet.created_at)}
                              </ThemedText>
                            </View>
                          </View>
                        </View>
                        <View style={[styles.expenseCardActions, { borderTopColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(128, 128, 128, 0.2)' }]}>
                          <TouchableOpacity
                            style={[styles.actionButton, { backgroundColor: '#34C759' }]}
                            onPress={(e) => {
                              e.stopPropagation();
                              handleApprove(sheet.id);
                            }}
                          >
                            <IconSymbol size={16} name="checkmark.circle.fill" color="#fff" />
                            <Text style={{ color: '#fff', fontSize: 14, fontWeight: '600' }}>Valider</Text>
                          </TouchableOpacity>
                          <TouchableOpacity
                            style={[
                              styles.actionButton,
                              { backgroundColor: isDark ? '#2C2C2E' : '#E5E5EA' },
                            ]}
                            onPress={(e) => {
                              e.stopPropagation();
                              router.push(`/expense/${sheet.id}`);
                            }}
                          >
                            <IconSymbol
                              size={16}
                              name="eye"
                              color={isDark ? '#fff' : '#000'}
                            />
                            <ThemedText style={styles.actionButtonTextSecondary}>Voir détails</ThemedText>
                          </TouchableOpacity>
                        </View>
                      </ThemedView>
                    </TouchableOpacity>
                  );
                })}
              </View>
            ) : (
              <ThemedView style={[styles.emptyState, { borderColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(128, 128, 128, 0.2)' }]}>
                <IconSymbol size={48} name="checkmark.circle.fill" color="#34C759" />
                <ThemedText style={styles.emptyTitle}>Aucune note de frais à valider</ThemedText>
                <ThemedText style={styles.emptyDescription}>
                  Vous n&apos;avez pas de notes de frais en attente de validation ou aucune ne correspond à vos filtres.
                </ThemedText>
              </ThemedView>
            )}
          </View>
        )}
      </ScrollView>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  scrollContent: {
    paddingTop: Platform.OS === 'ios' ? 60 : 40,
    paddingBottom: 100,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingBottom: 20,
  },
  headerTitle: {
    fontSize: 28,
    fontWeight: 'bold',
  },
  welcomeText: {
    fontSize: 14,
    opacity: 0.7,
    marginTop: 4,
  },
  logoutButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    justifyContent: 'center',
    alignItems: 'center',
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
  section: {
    paddingHorizontal: 20,
    marginBottom: 24,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: '600',
    marginBottom: 12,
  },
  badge: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 12,
  },
  badgeText: {
    fontSize: 12,
    fontWeight: '600',
  },
  formsGrid: {
    gap: 12,
  },
  formCard: {
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 3,
    elevation: 2,
    position: 'relative',
  },
  formName: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 8,
  },
  formDescription: {
    fontSize: 14,
    opacity: 0.7,
    marginBottom: 12,
  },
  formArrow: {
    position: 'absolute',
    right: 16,
    bottom: 16,
    width: 32,
    height: 32,
    borderRadius: 16,
    justifyContent: 'center',
    alignItems: 'center',
  },
  filtersSection: {
    paddingHorizontal: 20,
    marginBottom: 24,
    gap: 12,
  },
  searchContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 10,
    paddingHorizontal: 12,
    height: 44,
  },
  searchIcon: {
    marginRight: 8,
  },
  searchInput: {
    flex: 1,
    fontSize: 16,
  },
  filterContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 10,
    paddingHorizontal: 12,
    height: 44,
  },
  filterIcon: {
    marginRight: 8,
  },
  filterButton: {
    flex: 1,
  },
  filterText: {
    fontSize: 16,
  },
  expensesContainer: {
    gap: 12,
  },
  expenseCard: {
    borderRadius: 12,
    padding: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 3,
    elevation: 2,
  },
  expenseCardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 12,
  },
  expenseCardTitle: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    flex: 1,
    marginRight: 8,
  },
  expenseIcon: {
    marginRight: 8,
    marginTop: 2,
  },
  expenseTextContainer: {
    flex: 1,
  },
  expenseFormName: {
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 4,
  },
  expenseDate: {
    fontSize: 12,
    opacity: 0.7,
  },
  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 6,
  },
  statusText: {
    fontSize: 12,
    fontWeight: '600',
  },
  expenseCardFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingTop: 12,
    borderTopWidth: 1,
  },
  expenseLabel: {
    fontSize: 12,
    opacity: 0.7,
  },
  expenseAmount: {
    fontSize: 18,
    fontWeight: 'bold',
  },
  expenseCardDetails: {
    gap: 8,
    marginBottom: 12,
  },
  expenseDetailRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  dateContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  expenseCardActions: {
    flexDirection: 'row',
    gap: 8,
    paddingTop: 12,
    borderTopWidth: 1,
  },
  actionButton: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 10,
    paddingHorizontal: 16,
    borderRadius: 8,
    gap: 6,
  },
  actionButtonSmall: {
    width: 44,
    height: 44,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
  },
  actionButtonText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: '600',
  },
  actionButtonTextSecondary: {
    fontSize: 14,
    fontWeight: '600',
  },
  emptyState: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 48,
    paddingHorizontal: 24,
    borderRadius: 12,
    borderWidth: 1,
    borderStyle: 'dashed',
  },
  emptyTitle: {
    fontSize: 16,
    fontWeight: '600',
    marginTop: 16,
    marginBottom: 8,
  },
  emptyDescription: {
    fontSize: 14,
    opacity: 0.7,
    textAlign: 'center',
  },
});
