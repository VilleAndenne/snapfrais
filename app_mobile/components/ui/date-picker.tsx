import { useState } from 'react';
import { StyleSheet, TouchableOpacity, Platform, View } from 'react-native';
import DateTimePicker, { DateTimePickerEvent } from '@react-native-community/datetimepicker';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { IconSymbol } from '@/components/ui/icon-symbol';

interface DatePickerProps {
  value: string; // Format: YYYY-MM-DD
  onChange: (date: string) => void;
  isDark: boolean;
  hasError?: boolean;
}

export function DatePicker({ value, onChange, isDark, hasError }: DatePickerProps) {
  const [showPicker, setShowPicker] = useState(false);

  // Convert YYYY-MM-DD string to Date object
  const dateValue = value ? new Date(value + 'T00:00:00') : new Date();

  // Format date for display
  const formatDisplayDate = (dateStr: string): string => {
    if (!dateStr) return 'Sélectionner une date';

    const date = new Date(dateStr + 'T00:00:00');
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    });
  };

  const handleChange = (event: DateTimePickerEvent, selectedDate?: Date) => {
    if (Platform.OS === 'android') {
      setShowPicker(false);
    }

    if (event.type === 'set' && selectedDate) {
      // Convert Date to YYYY-MM-DD format
      const year = selectedDate.getFullYear();
      const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
      const day = String(selectedDate.getDate()).padStart(2, '0');
      const formattedDate = `${year}-${month}-${day}`;
      onChange(formattedDate);
    }

    if (event.type === 'dismissed') {
      setShowPicker(false);
    }
  };

  return (
    <View style={styles.container}>
      <TouchableOpacity
        onPress={() => setShowPicker(true)}
        activeOpacity={0.7}
      >
        <ThemedView
          style={[
            styles.input,
            isDark && styles.inputDark,
            hasError && styles.inputError,
          ]}
        >
          <IconSymbol size={20} name="calendar" color={isDark ? '#999' : '#666'} />
          <ThemedText
            style={[
              styles.dateText,
              !value && styles.placeholderText,
            ]}
          >
            {formatDisplayDate(value)}
          </ThemedText>
          <IconSymbol size={16} name="chevron.down" color={isDark ? '#999' : '#666'} />
        </ThemedView>
      </TouchableOpacity>

      {showPicker && (
        <>
          {Platform.OS === 'ios' && (
            <View style={[
              styles.iosPickerContainer,
              isDark && styles.iosPickerContainerDark
            ]}>
              <View style={[
                styles.iosPickerHeader,
                isDark && styles.iosPickerHeaderDark
              ]}>
                <TouchableOpacity onPress={() => setShowPicker(false)}>
                  <ThemedText style={styles.iosDoneButton}>Terminé</ThemedText>
                </TouchableOpacity>
              </View>
              <DateTimePicker
                value={dateValue}
                mode="date"
                display="spinner"
                onChange={handleChange}
                locale="fr-FR"
                maximumDate={new Date()}
                themeVariant={isDark ? 'dark' : 'light'}
                style={[styles.iosPicker, isDark && styles.iosPickerDark]}
              />
            </View>
          )}

          {Platform.OS === 'android' && (
            <DateTimePicker
              value={dateValue}
              mode="date"
              display="default"
              onChange={handleChange}
              locale="fr-FR"
              maximumDate={new Date()}
              themeVariant={isDark ? 'dark' : 'light'}
            />
          )}
        </>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    width: '100%',
  },
  input: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 8,
    padding: 12,
    gap: 8,
    borderWidth: 1,
    borderColor: '#e0e0e0',
    minHeight: 48,
  },
  inputDark: {
    borderColor: '#333',
  },
  inputError: {
    borderColor: '#FF3B30',
    borderWidth: 2,
  },
  dateText: {
    flex: 1,
    fontSize: 14,
  },
  placeholderText: {
    opacity: 0.5,
  },
  iosPickerContainer: {
    backgroundColor: '#fff',
    borderTopWidth: 1,
    borderTopColor: '#e0e0e0',
    marginTop: 8,
    borderRadius: 12,
    overflow: 'hidden',
  },
  iosPickerContainerDark: {
    backgroundColor: '#1C1C1E',
    borderTopColor: '#333',
  },
  iosPickerHeader: {
    flexDirection: 'row',
    justifyContent: 'flex-end',
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#e0e0e0',
    backgroundColor: '#f8f8f8',
  },
  iosPickerHeaderDark: {
    backgroundColor: '#2C2C2E',
    borderBottomColor: '#333',
  },
  iosDoneButton: {
    fontSize: 16,
    fontWeight: '600',
    color: '#007AFF',
  },
  iosPicker: {
    backgroundColor: '#fff',
  },
  iosPickerDark: {
    backgroundColor: '#1C1C1E',
  },
});
