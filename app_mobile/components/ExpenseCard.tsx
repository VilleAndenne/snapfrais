import { StyleSheet, View, TouchableOpacity } from 'react-native';
import { Link } from 'expo-router';
import { ThemedText } from './themed-text';
import { ThemedView } from './themed-view';
import { IconSymbol } from './ui/icon-symbol';
import { useColorScheme } from '@/hooks/use-color-scheme';

interface ExpenseCardProps {
  id: string;
  title: string;
  amount: number;
  date: string;
  category: string;
  status: 'draft' | 'pending' | 'approved' | 'rejected';
}

function getStatusColor(status: ExpenseCardProps['status'], colorScheme: 'light' | 'dark') {
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

function getStatusLabel(status: ExpenseCardProps['status']) {
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

export function ExpenseCard({ id, title, amount, date, category, status }: ExpenseCardProps) {
  const colorScheme = useColorScheme() ?? 'light';
  const statusColor = getStatusColor(status, colorScheme);
  const isDark = colorScheme === 'dark';

  return (
    <Link href={`/expense/${id}`} asChild>
      <TouchableOpacity>
        <ThemedView style={styles.card}>
          <View style={styles.header}>
            <ThemedText style={styles.title} numberOfLines={1}>
              {title}
            </ThemedText>
            <View style={[styles.statusBadge, { backgroundColor: statusColor + '20' }]}>
              <ThemedText style={[styles.statusText, { color: statusColor }]}>
                {getStatusLabel(status)}
              </ThemedText>
            </View>
          </View>
          <View style={styles.details}>
            <View style={styles.detailItem}>
              <IconSymbol
                size={16}
                name="calendar"
                color={isDark ? '#999' : '#666'}
                style={styles.icon}
              />
              <ThemedText style={styles.detailText}>
                {new Date(date).toLocaleDateString('fr-FR', {
                  day: '2-digit',
                  month: 'short',
                  year: 'numeric',
                })}
              </ThemedText>
            </View>
            <View style={styles.detailItem}>
              <IconSymbol
                size={16}
                name="tag"
                color={isDark ? '#999' : '#666'}
                style={styles.icon}
              />
              <ThemedText style={styles.detailText}>{category}</ThemedText>
            </View>
          </View>
          <ThemedText style={styles.amount}>{amount.toFixed(2)} €</ThemedText>
        </ThemedView>
      </TouchableOpacity>
    </Link>
  );
}

const styles = StyleSheet.create({
  card: {
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 3,
    elevation: 2,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  title: {
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
  details: {
    flexDirection: 'row',
    gap: 16,
    marginBottom: 12,
  },
  detailItem: {
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
