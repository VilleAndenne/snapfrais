import { useRouter } from 'expo-router';
import { useState } from 'react';
import {
  StyleSheet,
  View,
  ScrollView,
  TouchableOpacity,
  TextInput,
  Alert,
} from 'react-native';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';

const CATEGORIES = [
  { id: 'repas', label: 'Repas', icon: 'fork.knife' },
  { id: 'transport', label: 'Transport', icon: 'car.fill' },
  { id: 'hebergement', label: 'Hébergement', icon: 'bed.double.fill' },
  { id: 'fournitures', label: 'Fournitures', icon: 'cart.fill' },
  { id: 'autre', label: 'Autre', icon: 'ellipsis.circle.fill' },
];

export default function EditExpenseScreen() {
  // const { id } = useLocalSearchParams(); // TODO: Use this to fetch data from API
  const router = useRouter();
  const colorScheme = useColorScheme() ?? 'light';
  const isDark = colorScheme === 'dark';

  // En production, charger les données depuis une API
  const [title, setTitle] = useState('Repas client - Restaurant Le Gourmet');
  const [amount, setAmount] = useState('85.50');
  const [date, setDate] = useState('2025-11-05');
  const [category, setCategory] = useState('repas');
  const [merchant, setMerchant] = useState('Restaurant Le Gourmet');
  const [description, setDescription] = useState('Déjeuner d\'affaires avec le client Acme Corp');

  const handleSave = () => {
    if (!title || !amount || !category) {
      Alert.alert('Champs manquants', 'Veuillez remplir tous les champs obligatoires.');
      return;
    }

    // Logique pour mettre à jour la note de frais
    Alert.alert('Note modifiée', 'Votre note de frais a été mise à jour.', [
      { text: 'OK', onPress: () => router.back() },
    ]);
  };

  return (
    <ThemedView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.closeButton}>
          <IconSymbol size={28} name="xmark.circle.fill" color={isDark ? '#fff' : '#000'} />
        </TouchableOpacity>
        <ThemedText type="title" style={styles.headerTitle}>
          Modifier
        </ThemedText>
        <TouchableOpacity onPress={handleSave} style={styles.saveButton}>
          <ThemedText style={[styles.saveText, { color: Colors[colorScheme].tint }]}>
            Enregistrer
          </ThemedText>
        </TouchableOpacity>
      </View>

      <ScrollView style={styles.scrollView} showsVerticalScrollIndicator={false}>
        <View style={styles.content}>
          {/* Titre */}
          <View style={styles.field}>
            <ThemedText style={styles.label}>
              Titre <ThemedText style={styles.required}>*</ThemedText>
            </ThemedText>
            <ThemedView style={[styles.input, isDark && styles.inputDark]}>
              <TextInput
                value={title}
                onChangeText={setTitle}
                placeholder="Ex: Repas client"
                placeholderTextColor={isDark ? '#666' : '#999'}
                style={[styles.textInput, { color: isDark ? '#fff' : '#000' }]}
              />
            </ThemedView>
          </View>

          {/* Montant */}
          <View style={styles.field}>
            <ThemedText style={styles.label}>
              Montant <ThemedText style={styles.required}>*</ThemedText>
            </ThemedText>
            <ThemedView style={[styles.input, isDark && styles.inputDark]}>
              <TextInput
                value={amount}
                onChangeText={setAmount}
                placeholder="0.00"
                placeholderTextColor={isDark ? '#666' : '#999'}
                keyboardType="decimal-pad"
                style={[styles.textInput, { color: isDark ? '#fff' : '#000' }]}
              />
              <ThemedText style={styles.currency}>€</ThemedText>
            </ThemedView>
          </View>

          {/* Date */}
          <View style={styles.field}>
            <ThemedText style={styles.label}>Date</ThemedText>
            <ThemedView style={[styles.input, isDark && styles.inputDark]}>
              <IconSymbol size={20} name="calendar" color={isDark ? '#999' : '#666'} />
              <TextInput
                value={date}
                onChangeText={setDate}
                placeholder="YYYY-MM-DD"
                placeholderTextColor={isDark ? '#666' : '#999'}
                style={[styles.textInput, styles.dateInput, { color: isDark ? '#fff' : '#000' }]}
              />
            </ThemedView>
          </View>

          {/* Catégorie */}
          <View style={styles.field}>
            <ThemedText style={styles.label}>
              Catégorie <ThemedText style={styles.required}>*</ThemedText>
            </ThemedText>
            <View style={styles.categoriesGrid}>
              {CATEGORIES.map((cat) => (
                <TouchableOpacity
                  key={cat.id}
                  style={[
                    styles.categoryButton,
                    category === cat.id && {
                      backgroundColor: Colors[colorScheme].tint + '20',
                      borderColor: Colors[colorScheme].tint,
                    },
                  ]}
                  onPress={() => setCategory(cat.id)}>
                  <IconSymbol
                    size={24}
                    name={cat.icon}
                    color={
                      category === cat.id ? Colors[colorScheme].tint : isDark ? '#999' : '#666'
                    }
                  />
                  <ThemedText
                    style={[
                      styles.categoryLabel,
                      category === cat.id && { color: Colors[colorScheme].tint },
                    ]}>
                    {cat.label}
                  </ThemedText>
                </TouchableOpacity>
              ))}
            </View>
          </View>

          {/* Commerçant */}
          <View style={styles.field}>
            <ThemedText style={styles.label}>Commerçant</ThemedText>
            <ThemedView style={[styles.input, isDark && styles.inputDark]}>
              <IconSymbol size={20} name="storefront" color={isDark ? '#999' : '#666'} />
              <TextInput
                value={merchant}
                onChangeText={setMerchant}
                placeholder="Nom du commerçant"
                placeholderTextColor={isDark ? '#666' : '#999'}
                style={[styles.textInput, styles.merchantInput, { color: isDark ? '#fff' : '#000' }]}
              />
            </ThemedView>
          </View>

          {/* Description */}
          <View style={styles.field}>
            <ThemedText style={styles.label}>Description</ThemedText>
            <ThemedView style={[styles.input, styles.textArea, isDark && styles.inputDark]}>
              <TextInput
                value={description}
                onChangeText={setDescription}
                placeholder="Ajoutez des détails sur cette dépense..."
                placeholderTextColor={isDark ? '#666' : '#999'}
                multiline
                numberOfLines={4}
                style={[styles.textInput, styles.textAreaInput, { color: isDark ? '#fff' : '#000' }]}
              />
            </ThemedView>
          </View>

          {/* Justificatifs */}
          <View style={styles.field}>
            <ThemedText style={styles.label}>Justificatifs</ThemedText>
            <TouchableOpacity style={styles.uploadButton}>
              <IconSymbol size={32} name="camera.fill" color={Colors[colorScheme].tint} />
              <ThemedText style={[styles.uploadText, { color: Colors[colorScheme].tint }]}>
                Prendre une photo
              </ThemedText>
            </TouchableOpacity>
            <TouchableOpacity style={[styles.uploadButton, styles.uploadButtonSecondary]}>
              <IconSymbol size={32} name="photo.fill" color={isDark ? '#999' : '#666'} />
              <ThemedText style={styles.uploadText}>Choisir depuis la galerie</ThemedText>
            </TouchableOpacity>
          </View>
        </View>
      </ScrollView>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
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
  saveButton: {
    padding: 8,
  },
  saveText: {
    fontSize: 16,
    fontWeight: '600',
  },
  scrollView: {
    flex: 1,
  },
  content: {
    paddingHorizontal: 20,
    paddingBottom: 100,
  },
  field: {
    marginBottom: 24,
  },
  label: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 8,
  },
  required: {
    color: '#FF3B30',
  },
  input: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 12,
    padding: 16,
    gap: 12,
    borderWidth: 1,
    borderColor: '#e0e0e0',
  },
  inputDark: {
    borderColor: '#333',
  },
  textInput: {
    flex: 1,
    fontSize: 16,
  },
  dateInput: {
    marginLeft: 0,
  },
  merchantInput: {
    marginLeft: 0,
  },
  currency: {
    fontSize: 18,
    fontWeight: '600',
  },
  textArea: {
    minHeight: 120,
    alignItems: 'flex-start',
  },
  textAreaInput: {
    textAlignVertical: 'top',
    paddingTop: 0,
  },
  categoriesGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  categoryButton: {
    flex: 1,
    minWidth: '30%',
    flexDirection: 'column',
    alignItems: 'center',
    padding: 16,
    borderRadius: 12,
    borderWidth: 2,
    borderColor: '#e0e0e0',
    gap: 8,
  },
  categoryLabel: {
    fontSize: 14,
    fontWeight: '500',
  },
  uploadButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 20,
    borderRadius: 12,
    borderWidth: 2,
    borderColor: '#e0e0e0',
    borderStyle: 'dashed',
    gap: 12,
    marginBottom: 12,
  },
  uploadButtonSecondary: {
    marginBottom: 0,
  },
  uploadText: {
    fontSize: 16,
    fontWeight: '500',
  },
});
