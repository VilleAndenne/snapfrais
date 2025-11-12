import { useRouter, Link, useFocusEffect } from 'expo-router';
import { StyleSheet, ScrollView, View, Text, TouchableOpacity, Platform, TextInput, ActivityIndicator, RefreshControl, Alert } from 'react-native';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { useState, useMemo, useCallback } from 'react';
import { useAuth } from '@/contexts/auth-context';
import { api } from '@/services/api';
import type { ExpenseSheet } from '@/types/api';

type StatusType = 'draft' | 'pending' | 'approved' | 'rejected';

function getStatusFromApproved(approved: boolean | null, isDraft: boolean): StatusType {
  if (isDraft) return 'draft';
  if (approved == 1) return 'approved';
  if (approved == 0) return 'rejected';
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

export default function ExpenseListScreen() {
  const colorScheme = useColorScheme() ?? 'light';
  const isDark = colorScheme === 'dark';
  const { user } = useAuth();
  const router = useRouter();

  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState<'all' | StatusType>('all');
  const [expenseSheets, setExpenseSheets] = useState<ExpenseSheet[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [hasInitiallyLoaded, setHasInitiallyLoaded] = useState(false);

  const fetchData = async () => {
    try {
      setIsLoading(true);
      // Charger toutes les notes de frais auxquelles l'utilisateur a accès
      const response = await api.getAllExpenseSheets();
      console.log('Expenses - Loaded', response.expenseSheets.length, 'expense sheets');
      setExpenseSheets(response.expenseSheets);
    } catch (error) {
      console.error('Error fetching expense sheets:', error);
      Alert.alert(
        'Erreur',
        'Impossible de charger les notes de frais',
        [{ text: 'Réessayer', onPress: fetchData }]
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

  // Refresh when screen comes into focus
  useFocusEffect(
    useCallback(() => {
      console.log('Expenses - Screen focused');
      if (hasInitiallyLoaded) {
        fetchData();
      } else {
        // First load
        fetchData();
      }
    }, [hasInitiallyLoaded])
  );

  // Filtrer les notes de frais
  const filteredExpenseSheets = useMemo(() => {
    return expenseSheets.filter(sheet => {
      // Filtrer par recherche (nom de l'utilisateur, formulaire, département)
      const matchesSearch =
        sheet.user?.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        sheet.form?.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        sheet.department?.name.toLowerCase().includes(searchQuery.toLowerCase());

      // Filtrer par statut
      const status = getStatusFromApproved(sheet.approved ?? null, sheet.is_draft);
      const matchesStatus = statusFilter === 'all' || status === statusFilter;

      return matchesSearch && matchesStatus;
    });
  }, [expenseSheets, searchQuery, statusFilter]);

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
  };

  if (isLoading && !hasInitiallyLoaded) {
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
        <ThemedText type="title" style={styles.headerTitle}>
          Notes de frais
        </ThemedText>
      </View>

      <ScrollView
        style={styles.scrollView}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={isRefreshing}
            onRefresh={onRefresh}
            tintColor={Colors[colorScheme].tint}
          />
        }
      >
        <View style={styles.content}>
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
                placeholder="Rechercher..."
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
                  const filters: Array<'all' | StatusType> = ['all', 'pending', 'approved', 'rejected', 'draft'];
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

          {/* Compteur */}
          <View style={styles.countSection}>
            <ThemedText style={styles.countText}>
              {filteredExpenseSheets.length} note(s) de frais
            </ThemedText>
          </View>

          {/* Liste des notes de frais */}
          {filteredExpenseSheets.length > 0 ? (
            <View style={styles.expensesContainer}>
              {filteredExpenseSheets.map((sheet) => {
                const status = getStatusFromApproved(sheet.approved ?? null, sheet.is_draft);
                const statusColor = getStatusColor(status);
                return (
                  <TouchableOpacity
                    key={sheet.id}
                    onPress={() => router.push(`/expense/${sheet.id}`)}
                    activeOpacity={0.7}
                  >
                    <ThemedView style={styles.expenseCard}>
                      {/* Header */}
                      <View style={styles.expenseCardHeader}>
                        <View style={styles.expenseCardTitle}>
                          <View style={styles.expenseTextContainer}>
                            <ThemedText style={styles.expenseFormName} numberOfLines={1}>
                              {sheet.form?.name || 'Sans titre'}
                            </ThemedText>
                            <View style={styles.userInfo}>
                              <IconSymbol size={14} name="person.fill" color={isDark ? '#8E8E93' : '#8E8E93'} />
                              <ThemedText style={styles.expenseUserName} numberOfLines={1}>
                                {sheet.user?.name || 'Inconnu'}
                              </ThemedText>
                            </View>
                          </View>
                        </View>
                        <View style={[styles.statusBadge, { backgroundColor: statusColor + '20' }]}>
                          <Text style={[styles.statusText, { color: statusColor }]}>
                            {getStatusLabel(status)}
                          </Text>
                        </View>
                      </View>

                      {/* Details */}
                      <View style={styles.expenseCardDetails}>
                        <View style={styles.expenseDetailRow}>
                          <View style={styles.expenseDetailItem}>
                            <IconSymbol size={14} name="building.2" color={isDark ? '#8E8E93' : '#8E8E93'} />
                            <ThemedText style={styles.expenseDetailLabel} numberOfLines={1}>
                              {sheet.department?.name || 'N/A'}
                            </ThemedText>
                          </View>
                          <View style={styles.expenseDetailItem}>
                            <IconSymbol size={14} name="calendar" color={isDark ? '#8E8E93' : '#8E8E93'} />
                            <ThemedText style={styles.expenseDetailLabel}>
                              {formatDate(sheet.created_at)}
                            </ThemedText>
                          </View>
                        </View>
                      </View>

                      {/* Footer with amount */}
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
                Aucune note de frais ne correspond à vos critères de recherche.
              </ThemedText>
            </ThemedView>
          )}
        </View>
      </ScrollView>
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
    paddingHorizontal: 20,
    paddingTop: Platform.OS === 'ios' ? 60 : 40,
    paddingBottom: 20,
  },
  headerTitle: {
    fontSize: 28,
    fontWeight: 'bold',
  },
  scrollView: {
    flex: 1,
  },
  content: {
    paddingBottom: 100,
  },
  filtersSection: {
    paddingHorizontal: 20,
    marginBottom: 16,
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
  countSection: {
    paddingHorizontal: 20,
    marginBottom: 12,
  },
  countText: {
    fontSize: 14,
    opacity: 0.7,
  },
  expensesContainer: {
    paddingHorizontal: 20,
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
  expenseTextContainer: {
    flex: 1,
  },
  expenseFormName: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 4,
  },
  userInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  expenseUserName: {
    fontSize: 13,
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
  expenseCardDetails: {
    marginBottom: 12,
  },
  expenseDetailRow: {
    flexDirection: 'row',
    gap: 16,
  },
  expenseDetailItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    flex: 1,
  },
  expenseDetailLabel: {
    fontSize: 13,
    opacity: 0.7,
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
  emptyState: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 48,
    paddingHorizontal: 24,
    marginHorizontal: 20,
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
