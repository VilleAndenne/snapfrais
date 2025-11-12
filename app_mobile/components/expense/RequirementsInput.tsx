import { useState } from 'react';
import { StyleSheet, View, TextInput, TouchableOpacity, Alert, Platform } from 'react-native';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { IconSymbol } from '@/components/ui/icon-symbol';
import * as DocumentPicker from 'expo-document-picker';
import * as ImagePicker from 'expo-image-picker';
import type { FormCostRequirement } from '@/types/api';

interface RequirementsInputProps {
  requirements: FormCostRequirement[];
  values: Record<string, any>;
  isDark: boolean;
  submitted: boolean;
  onUpdate: (requirementId: number, value: any) => void;
}

export function RequirementsInput({
  requirements,
  values,
  isDark,
  submitted,
  onUpdate,
}: RequirementsInputProps) {
  const [uploadingReqId, setUploadingReqId] = useState<number | null>(null);

  // Debug: voir ce que contient requirements
  console.log('RequirementsInput - requirements:', JSON.stringify(requirements, null, 2));

  const pickDocument = async (requirement: FormCostRequirement) => {
    try {
      setUploadingReqId(requirement.id);

      let result;

      // Pour tous les fichiers, proposer : Prendre une photo, Choisir un fichier
      const action = await new Promise<'camera' | 'file' | null>((resolve) => {
        Alert.alert(
          'Ajouter un document',
          'Comment souhaitez-vous ajouter votre document ?',
          [
            { text: 'Annuler', style: 'cancel', onPress: () => resolve(null) },
            { text: 'Prendre une photo', onPress: () => resolve('camera') },
            { text: 'Choisir un fichier', onPress: () => resolve('file') },
          ]
        );
      });

      if (!action) {
        setUploadingReqId(null);
        return;
      }

      if (action === 'camera') {
        // Prendre une photo avec la caméra
        const { status } = await ImagePicker.requestCameraPermissionsAsync();
        if (status !== 'granted') {
          Alert.alert('Permission refusée', 'Nous avons besoin de votre permission pour accéder à la caméra.');
          setUploadingReqId(null);
          return;
        }

        const imageResult = await ImagePicker.launchCameraAsync({
          mediaTypes: ImagePicker.MediaTypeOptions.Images,
          quality: 0.8,
          allowsEditing: true,
        });

        if (!imageResult.canceled) {
          result = {
            canceled: false,
            assets: imageResult.assets,
          };
        }
      } else {
        // Choisir un fichier (document ou image depuis la galerie)
        result = await DocumentPicker.getDocumentAsync({
          type: '*/*',
          copyToCacheDirectory: true,
        });
      }

      if (result && !result.canceled && result.assets && result.assets.length > 0) {
        const file = result.assets[0];

        // Generate a name for camera photos (they don't have a name property)
        let fileName = 'document';
        if ('name' in file && file.name) {
          fileName = file.name;
        } else if (action === 'camera') {
          // Generate a name for camera photos
          const timestamp = new Date().getTime();
          fileName = `photo_${timestamp}.jpg`;
        }

        onUpdate(requirement.id, {
          uri: file.uri,
          name: fileName,
          type: file.mimeType || 'image/jpeg',
          size: (file as any).size || 0,
        });
      }
    } catch (error) {
      console.error('Error picking document:', error);
      Alert.alert('Erreur', 'Impossible de sélectionner le fichier');
    } finally {
      setUploadingReqId(null);
    }
  };

  const getRequirementLabel = (type: string | undefined): string => {
    if (!type) return 'Champ requis';
    const labels: Record<string, string> = {
      receipt: 'Reçu',
      photo: 'Photo',
      invoice: 'Facture',
      document: 'Document',
      justification: 'Justificatif',
      note: 'Note',
      comment: 'Commentaire',
    };
    return labels[type.toLowerCase()] || type;
  };

  const isFileType = (requirement: FormCostRequirement): boolean => {
    // Si le type est "text", c'est un champ texte, sinon c'est un fichier
    return requirement.type !== 'text';
  };

  if (requirements.length === 0) {
    return null;
  }

  return (
    <View style={styles.container}>
      <ThemedText style={styles.title}>Prérequis</ThemedText>

      {requirements.map((requirement) => {
        const value = values[requirement.id];
        const isFile = isFileType(requirement);
        const hasError = submitted && requirement.required && !value;

        return (
          <View key={requirement.id} style={styles.requirementItem}>
            <View style={styles.requirementHeader}>
              <ThemedText style={styles.requirementLabel}>
                  {requirement.name}
                {requirement.required && <ThemedText style={styles.required}> *</ThemedText>}
              </ThemedText>
            </View>

            {isFile ? (
              <View>
                <TouchableOpacity
                  style={[
                    styles.uploadButton,
                    isDark && styles.uploadButtonDark,
                    hasError && styles.uploadButtonError,
                  ]}
                  onPress={() => pickDocument(requirement)}
                  disabled={uploadingReqId === requirement.id}
                >
                  <IconSymbol
                    size={20}
                    name={value ? 'checkmark.circle.fill' : 'photo'}
                    color={value ? '#34C759' : isDark ? '#999' : '#666'}
                  />
                  <ThemedText style={styles.uploadButtonText}>
                    {uploadingReqId === requirement.id
                      ? 'Chargement...'
                      : value
                      ? `${value.name || 'Fichier sélectionné'}`
                      : 'Ajouter un fichier'}
                  </ThemedText>
                </TouchableOpacity>

                {value && (
                  <TouchableOpacity
                    style={styles.removeFileButton}
                    onPress={() => onUpdate(requirement.id, null)}
                  >
                    <IconSymbol size={16} name="xmark.circle.fill" color="#FF3B30" />
                    <ThemedText style={styles.removeFileText}>Supprimer</ThemedText>
                  </TouchableOpacity>
                )}
              </View>
            ) : (
              <ThemedView style={[styles.input, isDark && styles.inputDark, hasError && styles.inputError]}>
                <TextInput
                  value={value || ''}
                  onChangeText={(text) => onUpdate(requirement.id, text)}
                  placeholder={`Entrez ${getRequirementLabel(requirement.requirement_type).toLowerCase()}`}
                  placeholderTextColor={isDark ? '#666' : '#999'}
                  style={[styles.textInput, { color: isDark ? '#fff' : '#000' }]}
                  multiline={requirement.requirement_type?.toLowerCase() === 'comment' || requirement.requirement_type?.toLowerCase() === 'note'}
                  numberOfLines={4}
                />
              </ThemedView>
            )}

            {hasError && (
              <ThemedText style={styles.errorText}>Ce champ est obligatoire</ThemedText>
            )}
          </View>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    marginTop: 12,
    padding: 12,
    backgroundColor: '#f5f5f5',
    borderRadius: 8,
  },
  title: {
    fontSize: 15,
    fontWeight: '600',
    marginBottom: 12,
  },
  requirementItem: {
    marginBottom: 12,
  },
  requirementHeader: {
    marginBottom: 6,
  },
  requirementLabel: {
    fontSize: 14,
    fontWeight: '600',
  },
  required: {
    color: '#FF3B30',
  },
  input: {
    borderRadius: 8,
    padding: 12,
    borderWidth: 1,
    borderColor: '#e0e0e0',
  },
  inputDark: {
    borderColor: '#333',
  },
  inputError: {
    borderColor: '#FF3B30',
  },
  textInput: {
    fontSize: 14,
    minHeight: 20,
  },
  uploadButton: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#e0e0e0',
    borderStyle: 'dashed',
    gap: 8,
  },
  uploadButtonDark: {
    borderColor: '#333',
  },
  uploadButtonError: {
    borderColor: '#FF3B30',
  },
  uploadButtonText: {
    fontSize: 14,
    flex: 1,
  },
  removeFileButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: 8,
    padding: 8,
  },
  removeFileText: {
    fontSize: 13,
    color: '#FF3B30',
  },
  errorText: {
    fontSize: 12,
    color: '#FF3B30',
    marginTop: 4,
  },
});
