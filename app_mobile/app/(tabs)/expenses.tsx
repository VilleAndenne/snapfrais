import { Link } from 'expo-router';
import { StyleSheet, FlatList, View, Text, TouchableOpacity, Platform } from 'react-native';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';

// Types pour les notes de frais
interface Expense {
  id: string;
  title: string;
  amount: number;
  date: string;
  category: string;
  status: 'draft' | 'pending' | 'approved' | 'rejected';
}

// Données de démonstration
const DEMO_EXPENSES: Expense[] = [
  {
    id: '1',
    title: 'Repas client - Restaurant Le Gourmet',
    amount: 85.50,
    date: '2025-11-05',
    category: 'Repas',
    status: 'pending',
  },
  {
    id: '2',
    title: 'Train Paris-Lyon',
    amount: 125.00,
    date: '2025-11-04',
    category: 'Transport',
    status: 'approved',
  },
  {
    id: '3',
    title: 'Hôtel Mercure - 2 nuits',
    amount: 240.00,
    date: '2025-11-03',
    category: 'Hébergement',
    status: 'pending',
  },
  {
    id: '4',
    title: 'Fournitures bureau',
    amount: 45.80,
    date: '2025-11-02',
    category: 'Fournitures',
    status: 'draft',
  },
  {
    id: '5',
    title: 'Taxi aéroport',
    amount: 35.00,
    date: '2025-11-01',
    category: 'Transport',
    status: 'rejected',
  },
];

function getStatusColor(status: Expense['status'], colorScheme: 'light' | 'dark') {
  const isDark = colorScheme === 'dark';
  switch (status) {
    case 'draft':
      return isDark ? '#666' : '#999';
    case 'pending':
      return '#FF9500';
    case 'approved':
      return '#34C759';
    case 'rejected':
      return '#FF3B30';
    default:
      return isDark ? '#666' : '#999';
  }
}

function getStatusLabel(status: Expense['status']) {
  switch (status) {
    case 'draft':
      return 'Brouillon';
    case 'pending':
      return 'En attente';
    case 'approved':
      return 'Approuvée';
    case 'rejected':
      return 'Rejetée';
    default:
      return status;
  }
}

export default function ExpenseListScreen() {
  const colorScheme = useColorScheme() ?? 'light';

  const renderExpenseItem = ({ item }: { item: Expense }) => {
    const statusColor = getStatusColor(item.status, colorScheme);
    const isDark = colorScheme === 'dark';

    return (
      <Link href={`/expense/${item.id}`} asChild>
        <TouchableOpacity>
          <ThemedView style={styles.expenseCard}>
            <View style={styles.expenseHeader}>
              <ThemedText style={styles.expenseTitle} numberOfLines={1}>
                {item.title}
              </ThemedText>
              <View style={[styles.statusBadge, { backgroundColor: statusColor + '20' }]}>
                <Text style={[styles.statusText, { color: statusColor }]}>
                  {getStatusLabel(item.status)}
                </Text>
              </View>
            </View>
            <View style={styles.expenseDetails}>
              <View style={styles.expenseInfo}>
                <IconSymbol
                  size={16}
                  name="calendar"
                  color={isDark ? '#999' : '#666'}
                  style={styles.icon}
                />
                <ThemedText style={styles.detailText}>
                  {new Date(item.date).toLocaleDateString('fr-FR', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                  })}
                </ThemedText>
              </View>
              <View style={styles.expenseInfo}>
                <IconSymbol
                  size={16}
                  name="tag"
                  color={isDark ? '#999' : '#666'}
                  style={styles.icon}
                />
                <ThemedText style={styles.detailText}>{item.category}</ThemedText>
              </View>
            </View>
            <ThemedText style={styles.amount}>{item.amount.toFixed(2)} €</ThemedText>
          </ThemedView>
        </TouchableOpacity>
      </Link>
    );
  };

  return (
    <ThemedView style={styles.container}>
      <View style={styles.header}>
        <ThemedText type="title" style={styles.headerTitle}>
          Mes Notes de Frais
        </ThemedText>
        <Link href="/expense/new" asChild>
          <TouchableOpacity style={[styles.addButton, { backgroundColor: Colors[colorScheme].tint }]}>
            <IconSymbol size={24} name="plus" color="#fff" />
          </TouchableOpacity>
        </Link>
      </View>

      <FlatList
        data={DEMO_EXPENSES}
        renderItem={renderExpenseItem}
        keyExtractor={(item) => item.id}
        contentContainerStyle={styles.listContainer}
        showsVerticalScrollIndicator={false}
      />
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    paddingTop: Platform.OS === 'ios' ? 60 : 40,
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
  addButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  listContainer: {
    paddingHorizontal: 20,
    paddingBottom: 100,
  },
  expenseCard: {
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 3,
    elevation: 2,
  },
  expenseHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  expenseTitle: {
    fontSize: 16,
    fontWeight: '600',
    flex: 1,
    marginRight: 12,
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
  expenseDetails: {
    flexDirection: 'row',
    gap: 16,
    marginBottom: 12,
  },
  expenseInfo: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  icon: {
    marginRight: 4,
  },
  detailText: {
    fontSize: 14,
    opacity: 0.7,
  },
  amount: {
    fontSize: 22,
    fontWeight: 'bold',
  },
});
