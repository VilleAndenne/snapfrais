import { useState } from 'react';
import { StyleSheet, TouchableOpacity, Platform, View, Modal, FlatList, Pressable } from 'react-native';
import { ActionSheetIOS } from 'react-native';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { IconSymbol } from '@/components/ui/icon-symbol';

interface SelectOption {
  id: number;
  name: string;
}

interface SelectPickerProps {
  options: SelectOption[];
  selectedId: number | null;
  onSelect: (id: number) => void;
  placeholder?: string;
  isDark: boolean;
  hasError?: boolean;
  title?: string;
}

export function SelectPicker({
  options,
  selectedId,
  onSelect,
  placeholder = 'Sélectionner',
  isDark,
  hasError,
  title = 'Sélectionner une option',
}: SelectPickerProps) {
  const [showModal, setShowModal] = useState(false);

  const selectedOption = options.find(o => o.id === selectedId);

  const handlePress = () => {
    if (options.length === 0) {
      return;
    }

    if (Platform.OS === 'ios') {
      const optionNames = ['Annuler', ...options.map(o => o.name)];
      ActionSheetIOS.showActionSheetWithOptions(
        {
          options: optionNames,
          cancelButtonIndex: 0,
        },
        (buttonIndex) => {
          if (buttonIndex > 0) {
            onSelect(options[buttonIndex - 1].id);
          }
        }
      );
    } else {
      setShowModal(true);
    }
  };

  const handleSelect = (id: number) => {
    onSelect(id);
    setShowModal(false);
  };

  return (
    <View>
      <TouchableOpacity onPress={handlePress} activeOpacity={0.7}>
        <ThemedView
          style={[
            styles.selectButton,
            isDark && styles.selectButtonDark,
            hasError && styles.selectButtonError,
          ]}
        >
          <ThemedText
            style={[
              styles.selectButtonText,
              !selectedOption && styles.placeholderText,
            ]}
          >
            {selectedOption?.name || placeholder}
          </ThemedText>
          <IconSymbol size={20} name="chevron.down" color={isDark ? '#999' : '#666'} />
        </ThemedView>
      </TouchableOpacity>

      {/* Modal pour Android */}
      <Modal
        visible={showModal}
        transparent
        animationType="fade"
        onRequestClose={() => setShowModal(false)}
      >
        <Pressable
          style={styles.modalOverlay}
          onPress={() => setShowModal(false)}
        >
          <View style={[styles.modalContent, isDark && styles.modalContentDark]}>
            <View style={[styles.modalHeader, isDark && styles.modalHeaderDark]}>
              <ThemedText style={styles.modalTitle}>{title}</ThemedText>
              <TouchableOpacity onPress={() => setShowModal(false)}>
                <IconSymbol size={24} name="xmark" color={isDark ? '#fff' : '#000'} />
              </TouchableOpacity>
            </View>

            <FlatList
              data={options}
              keyExtractor={(item) => item.id.toString()}
              renderItem={({ item }) => (
                <TouchableOpacity
                  style={[
                    styles.optionItem,
                    isDark && styles.optionItemDark,
                    item.id === selectedId && styles.optionItemSelected,
                    item.id === selectedId && isDark && styles.optionItemSelectedDark,
                  ]}
                  onPress={() => handleSelect(item.id)}
                >
                  <ThemedText
                    style={[
                      styles.optionText,
                      item.id === selectedId && styles.optionTextSelected,
                    ]}
                  >
                    {item.name}
                  </ThemedText>
                  {item.id === selectedId && (
                    <IconSymbol size={20} name="checkmark" color="#007AFF" />
                  )}
                </TouchableOpacity>
              )}
              ItemSeparatorComponent={() => (
                <View style={[styles.separator, isDark && styles.separatorDark]} />
              )}
              style={styles.optionsList}
            />
          </View>
        </Pressable>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
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
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  modalContent: {
    backgroundColor: '#fff',
    borderRadius: 16,
    width: '100%',
    maxHeight: '70%',
    overflow: 'hidden',
  },
  modalContentDark: {
    backgroundColor: '#1C1C1E',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#e0e0e0',
  },
  modalHeaderDark: {
    borderBottomColor: '#333',
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: '600',
  },
  optionsList: {
    maxHeight: 400,
  },
  optionItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    backgroundColor: '#fff',
  },
  optionItemDark: {
    backgroundColor: '#1C1C1E',
  },
  optionItemSelected: {
    backgroundColor: '#E3F2FD',
  },
  optionItemSelectedDark: {
    backgroundColor: '#0D47A1',
  },
  optionText: {
    fontSize: 16,
    flex: 1,
  },
  optionTextSelected: {
    fontWeight: '600',
    color: '#007AFF',
  },
  separator: {
    height: 1,
    backgroundColor: '#e0e0e0',
  },
  separatorDark: {
    backgroundColor: '#333',
  },
});
