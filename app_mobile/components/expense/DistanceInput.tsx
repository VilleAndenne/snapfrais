import { useState, useEffect, useCallback } from 'react';
import { StyleSheet, View, TextInput, ScrollView, TouchableOpacity, ActivityIndicator } from 'react-native';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { IconSymbol } from '@/components/ui/icon-symbol';

interface Address {
  description: string;
  place_id: string;
}

interface Waypoint {
  id: string;
  address: string;
  suggestions: Address[];
  showSuggestions: boolean;
}

interface DistanceInputProps {
  isDark: boolean;
  onDistanceCalculated: (distance: number, route: any[]) => void;
  rate?: number; // Taux de remboursement par km
  initialOrigin?: string;
  initialDestination?: string;
  initialSteps?: string[];
}

export function DistanceInput({ isDark, onDistanceCalculated, rate = 0, initialOrigin = '', initialDestination = '', initialSteps = [] }: DistanceInputProps) {
  console.log('DistanceInput - Component rendered with rate:', rate);

  const [origin, setOrigin] = useState(initialOrigin);
  const [destination, setDestination] = useState(initialDestination);
  const [waypoints, setWaypoints] = useState<Waypoint[]>(() => {
    // Initialiser les waypoints avec les étapes existantes
    return initialSteps.map((step, index) => ({
      id: `initial-${index}-${Date.now()}`,
      address: step,
      suggestions: [],
      showSuggestions: false,
    }));
  });
  const [originSuggestions, setOriginSuggestions] = useState<Address[]>([]);
  const [destinationSuggestions, setDestinationSuggestions] = useState<Address[]>([]);
  const [showOriginSuggestions, setShowOriginSuggestions] = useState(false);
  const [showDestinationSuggestions, setShowDestinationSuggestions] = useState(false);
  const [calculating, setCalculating] = useState(false);
  const [calculatedDistance, setCalculatedDistance] = useState<number | null>(null);

  // Fonction pour rechercher des adresses avec l'API Google Places
  const searchAddresses = async (input: string): Promise<Address[]> => {
    if (input.length < 3) return [];

    try {
      const apiKey = process.env.EXPO_PUBLIC_GOOGLE_MAPS_API_KEY;
      if (!apiKey) {
        console.warn('Google Maps API key not configured');
        return [];
      }

      const response = await fetch(
        `https://maps.googleapis.com/maps/api/place/autocomplete/json?input=${encodeURIComponent(
          input
        )}&key=${apiKey}&language=fr&components=country:fr|country:be`
      );

      const data = await response.json();

      if (data.status === 'OK') {
        return data.predictions.map((prediction: any) => ({
          description: prediction.description,
          place_id: prediction.place_id,
        }));
      }

      return [];
    } catch (error) {
      console.error('Error fetching addresses:', error);
      return [];
    }
  };

  // Fonction pour calculer la distance entre deux points
  const calculateSegmentDistance = async (from: string, to: string): Promise<number> => {
    const apiKey = process.env.EXPO_PUBLIC_GOOGLE_MAPS_API_KEY;
    if (!apiKey) {
      throw new Error('API key not configured');
    }

    const response = await fetch(
      `https://maps.googleapis.com/maps/api/distancematrix/json?origins=${encodeURIComponent(
        from
      )}&destinations=${encodeURIComponent(to)}&key=${apiKey}&language=fr&units=metric`
    );

    const data = await response.json();

    if (data.status === 'OK' && data.rows[0]?.elements[0]?.status === 'OK') {
      return data.rows[0].elements[0].distance.value; // en mètres
    }

    throw new Error(`Distance calculation failed: ${data.status}`);
  };

  // Fonction pour calculer la distance totale avec waypoints
  const calculateDistance = async () => {
    console.log('calculateDistance called', { origin, destination });

    if (!origin || !destination) {
      console.log('calculateDistance aborted: missing origin or destination');
      return;
    }

    setCalculating(true);
    console.log('calculateDistance: calculating...');

    try {
      const apiKey = process.env.EXPO_PUBLIC_GOOGLE_MAPS_API_KEY;
      if (!apiKey) {
        console.warn('Google Maps API key not configured');
        setCalculating(false);
        return;
      }
      console.log('calculateDistance: API key found');

      const validWaypoints = waypoints.filter(w => w.address.trim().length > 0);

      // Créer la liste des points à parcourir
      const points = [origin, ...validWaypoints.map(w => w.address), destination];

      console.log('Calculating distance for points:', points);

      // Calculer la distance pour chaque segment
      let totalDistanceInMeters = 0;
      for (let i = 0; i < points.length - 1; i++) {
        const segmentDistance = await calculateSegmentDistance(points[i], points[i + 1]);
        totalDistanceInMeters += segmentDistance;
        console.log(`Segment ${i + 1}: ${points[i]} -> ${points[i + 1]} = ${(segmentDistance / 1000).toFixed(2)} km`);
      }

      const distanceInKm = Math.round(totalDistanceInMeters / 1000 * 100) / 100;
      console.log('Total distance calculated:', distanceInKm, 'km');
      setCalculatedDistance(distanceInKm);

      // Créer un objet route pour le backend incluant les waypoints
      const routeData = [
        { address: origin, type: 'origin' },
        ...validWaypoints.map(w => ({ address: w.address, type: 'waypoint' })),
        { address: destination, type: 'destination' },
      ];

      onDistanceCalculated(distanceInKm, routeData);
    } catch (error) {
      console.error('Error calculating distance:', error);
    } finally {
      setCalculating(false);
    }
  };

  // Debounce pour les recherches d'adresses
  useEffect(() => {
    if (origin.length >= 3) {
      const timer = setTimeout(async () => {
        const suggestions = await searchAddresses(origin);
        setOriginSuggestions(suggestions);
        setShowOriginSuggestions(true);
      }, 300);
      return () => clearTimeout(timer);
    } else {
      setOriginSuggestions([]);
      setShowOriginSuggestions(false);
    }
  }, [origin]);

  useEffect(() => {
    if (destination.length >= 3) {
      const timer = setTimeout(async () => {
        const suggestions = await searchAddresses(destination);
        setDestinationSuggestions(suggestions);
        setShowDestinationSuggestions(true);
      }, 300);
      return () => clearTimeout(timer);
    } else {
      setDestinationSuggestions([]);
      setShowDestinationSuggestions(false);
    }
  }, [destination]);

  // Fonction pour mettre à jour les suggestions d'un waypoint
  const updateWaypointSuggestions = useCallback(async (waypointId: string, address: string) => {
    if (address.length >= 3) {
      const suggestions = await searchAddresses(address);
      setWaypoints(prev => prev.map(w =>
        w.id === waypointId ? { ...w, suggestions } : w
      ));
    } else {
      setWaypoints(prev => prev.map(w =>
        w.id === waypointId ? { ...w, suggestions: [] } : w
      ));
    }
  }, []);

  // Debounce pour chaque waypoint
  useEffect(() => {
    const timers: NodeJS.Timeout[] = [];

    waypoints.forEach((waypoint) => {
      if (waypoint.showSuggestions) {
        const timer = setTimeout(() => {
          updateWaypointSuggestions(waypoint.id, waypoint.address);
        }, 300);
        timers.push(timer);
      }
    });

    return () => {
      timers.forEach(timer => clearTimeout(timer));
    };
  }, [waypoints.map(w => `${w.id}-${w.address}-${w.showSuggestions}`).join(','), updateWaypointSuggestions]);

  // Recalculer la distance quand les adresses changent (avec debounce)
  useEffect(() => {
    console.log('DistanceInput - useEffect triggered', {
      origin,
      destination,
      originLength: origin.length,
      destinationLength: destination.length,
      waypointsCount: waypoints.length
    });

    // Vérifier que les adresses ont au moins 3 caractères
    if (origin.length >= 3 && destination.length >= 3) {
      // Vérifier que tous les waypoints (s'il y en a) ont aussi au moins 3 caractères
      const allWaypointsValid = waypoints.every(w => w.address.length === 0 || w.address.length >= 3);

      if (allWaypointsValid) {
        console.log('DistanceInput - Conditions met, will calculate distance after debounce');

        // Debounce de 1 seconde pour ne pas appeler l'API à chaque frappe
        const timer = setTimeout(() => {
          console.log('DistanceInput - Debounce finished, calling calculateDistance');
          calculateDistance();
        }, 1000);

        return () => clearTimeout(timer);
      }
    } else {
      console.log('DistanceInput - Not enough characters', {
        originLength: origin.length,
        destinationLength: destination.length
      });
    }
  }, [origin, destination, waypoints]);

  const selectOrigin = (address: Address) => {
    setOrigin(address.description);
    setShowOriginSuggestions(false);
  };

  const selectDestination = (address: Address) => {
    setDestination(address.description);
    setShowDestinationSuggestions(false);
  };

  const addWaypoint = () => {
    const newWaypoint: Waypoint = {
      id: Date.now().toString(),
      address: '',
      suggestions: [],
      showSuggestions: false,
    };
    setWaypoints([...waypoints, newWaypoint]);
  };

  const removeWaypoint = (id: string) => {
    setWaypoints(waypoints.filter(w => w.id !== id));
  };

  const updateWaypointAddress = (id: string, address: string) => {
    setWaypoints(waypoints.map(w =>
      w.id === id ? { ...w, address, showSuggestions: address.length >= 3 } : w
    ));
  };

  const selectWaypointAddress = (id: string, address: Address) => {
    setWaypoints(waypoints.map(w =>
      w.id === id ? { ...w, address: address.description, showSuggestions: false, suggestions: [] } : w
    ));
  };

  return (
    <View style={styles.container}>
      {/* Adresse de départ */}
      <View style={styles.field}>
        <ThemedText style={styles.fieldLabel}>Adresse de départ</ThemedText>
        <ThemedView style={[styles.input, isDark && styles.inputDark]}>
          <IconSymbol size={20} name="location" color={isDark ? '#999' : '#666'} />
          <TextInput
            value={origin}
            onChangeText={setOrigin}
            placeholder="Entrez l'adresse de départ"
            placeholderTextColor={isDark ? '#666' : '#999'}
            style={[styles.textInput, { color: isDark ? '#fff' : '#000' }]}
          />
        </ThemedView>

        {showOriginSuggestions && originSuggestions.length > 0 && (
          <ThemedView style={[styles.suggestionsContainer, isDark && styles.suggestionsContainerDark]}>
            <ScrollView
              style={styles.suggestionsList}
              nestedScrollEnabled={true}
              keyboardShouldPersistTaps="handled"
            >
              {originSuggestions.map((item) => (
                <TouchableOpacity
                  key={item.place_id}
                  style={styles.suggestionItem}
                  onPress={() => selectOrigin(item)}
                >
                  <IconSymbol size={16} name="location.fill" color="#007AFF" />
                  <ThemedText style={styles.suggestionText}>{item.description}</ThemedText>
                </TouchableOpacity>
              ))}
            </ScrollView>
          </ThemedView>
        )}
      </View>

      {/* Étapes intermédiaires */}
      {waypoints.map((waypoint, index) => (
        <View key={waypoint.id} style={styles.field}>
          <View style={styles.waypointHeader}>
            <ThemedText style={styles.fieldLabel}>Étape {index + 1}</ThemedText>
            <TouchableOpacity
              onPress={() => removeWaypoint(waypoint.id)}
              style={styles.removeButton}
            >
              <IconSymbol size={18} name="xmark.circle.fill" color="#FF3B30" />
            </TouchableOpacity>
          </View>

          <ThemedView style={[styles.input, isDark && styles.inputDark]}>
            <IconSymbol size={20} name="mappin" color={isDark ? '#999' : '#666'} />
            <TextInput
              value={waypoint.address}
              onChangeText={(text) => updateWaypointAddress(waypoint.id, text)}
              placeholder="Entrez une étape intermédiaire"
              placeholderTextColor={isDark ? '#666' : '#999'}
              style={[styles.textInput, { color: isDark ? '#fff' : '#000' }]}
            />
          </ThemedView>

          {waypoint.showSuggestions && waypoint.suggestions.length > 0 && (
            <ThemedView style={[styles.suggestionsContainer, isDark && styles.suggestionsContainerDark]}>
              <ScrollView
                style={styles.suggestionsList}
                nestedScrollEnabled={true}
                keyboardShouldPersistTaps="handled"
              >
                {waypoint.suggestions.map((item) => (
                  <TouchableOpacity
                    key={item.place_id}
                    style={styles.suggestionItem}
                    onPress={() => selectWaypointAddress(waypoint.id, item)}
                  >
                    <IconSymbol size={16} name="location.fill" color="#007AFF" />
                    <ThemedText style={styles.suggestionText}>{item.description}</ThemedText>
                  </TouchableOpacity>
                ))}
              </ScrollView>
            </ThemedView>
          )}
        </View>
      ))}

      {/* Bouton pour ajouter une étape */}
      <TouchableOpacity
        style={[styles.addWaypointButton, isDark && styles.addWaypointButtonDark]}
        onPress={addWaypoint}
      >
        <IconSymbol size={20} name="plus.circle.fill" color="#007AFF" />
        <ThemedText style={styles.addWaypointText}>Ajouter une étape</ThemedText>
      </TouchableOpacity>

      {/* Adresse d'arrivée */}
      <View style={styles.field}>
        <ThemedText style={styles.fieldLabel}>Adresse d'arrivée</ThemedText>
        <ThemedView style={[styles.input, isDark && styles.inputDark]}>
          <IconSymbol size={20} name="location" color={isDark ? '#999' : '#666'} />
          <TextInput
            value={destination}
            onChangeText={setDestination}
            placeholder="Entrez l'adresse d'arrivée"
            placeholderTextColor={isDark ? '#666' : '#999'}
            style={[styles.textInput, { color: isDark ? '#fff' : '#000' }]}
          />
        </ThemedView>

        {showDestinationSuggestions && destinationSuggestions.length > 0 && (
          <ThemedView style={[styles.suggestionsContainer, isDark && styles.suggestionsContainerDark]}>
            <ScrollView
              style={styles.suggestionsList}
              nestedScrollEnabled={true}
              keyboardShouldPersistTaps="handled"
            >
              {destinationSuggestions.map((item) => (
                <TouchableOpacity
                  key={item.place_id}
                  style={styles.suggestionItem}
                  onPress={() => selectDestination(item)}
                >
                  <IconSymbol size={16} name="location.fill" color="#007AFF" />
                  <ThemedText style={styles.suggestionText}>{item.description}</ThemedText>
                </TouchableOpacity>
              ))}
            </ScrollView>
          </ThemedView>
        )}
      </View>

      {/* Affichage du calcul */}
      {calculating && (
        <View style={styles.calculatingContainer}>
          <ActivityIndicator size="small" color="#007AFF" />
          <ThemedText style={styles.calculatingText}>Calcul en cours...</ThemedText>
        </View>
      )}

      {calculatedDistance !== null && !calculating && (
        <ThemedView style={[styles.resultContainer, isDark && styles.resultContainerDark]}>
          <IconSymbol size={24} name="arrow.triangle.swap" color="#34C759" />
          <View style={styles.resultTextContainer}>
            <View style={styles.resultRow}>
              <ThemedText style={styles.resultLabel}>Distance calculée:</ThemedText>
              <ThemedText style={styles.resultValue}>{calculatedDistance} km</ThemedText>
            </View>

            {rate > 0 && (
              <>
                <View style={styles.resultRow}>
                  <ThemedText style={styles.resultLabel}>Taux:</ThemedText>
                  <ThemedText style={styles.resultValue}>{rate.toFixed(4)} €/km</ThemedText>
                </View>

                <View style={[styles.resultRow, styles.resultTotalRow]}>
                  <ThemedText style={styles.resultTotalLabel}>Remboursement:</ThemedText>
                  <ThemedText style={styles.resultTotal}>{(calculatedDistance * rate).toFixed(2)} €</ThemedText>
                </View>
              </>
            )}
          </View>
        </ThemedView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    gap: 12,
  },
  field: {
    position: 'relative',
  },
  fieldLabel: {
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 6,
  },
  waypointHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 6,
  },
  removeButton: {
    padding: 4,
  },
  input: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 8,
    padding: 12,
    gap: 8,
    borderWidth: 1,
    borderColor: '#e0e0e0',
  },
  inputDark: {
    borderColor: '#333',
  },
  textInput: {
    flex: 1,
    fontSize: 14,
  },
  addWaypointButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 12,
    borderRadius: 8,
    backgroundColor: '#F0F0F0',
    gap: 8,
  },
  addWaypointButtonDark: {
    backgroundColor: '#2C2C2E',
  },
  addWaypointText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#007AFF',
  },
  suggestionsContainer: {
    position: 'absolute',
    top: '100%',
    left: 0,
    right: 0,
    maxHeight: 200,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#e0e0e0',
    marginTop: 4,
    zIndex: 1000,
    elevation: 5,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.25,
    shadowRadius: 3.84,
  },
  suggestionsContainerDark: {
    borderColor: '#333',
  },
  suggestionsList: {
    maxHeight: 200,
  },
  suggestionItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    gap: 8,
    borderBottomWidth: 1,
    borderBottomColor: '#f0f0f0',
  },
  suggestionText: {
    flex: 1,
    fontSize: 14,
  },
  calculatingContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 12,
    gap: 8,
  },
  calculatingText: {
    fontSize: 14,
    opacity: 0.7,
  },
  resultContainer: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    padding: 16,
    borderRadius: 12,
    backgroundColor: '#E8F5E9',
    gap: 12,
  },
  resultContainerDark: {
    backgroundColor: '#1B5E20',
  },
  resultTextContainer: {
    flex: 1,
    gap: 8,
  },
  resultRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  resultLabel: {
    fontSize: 14,
    fontWeight: '600',
    opacity: 0.8,
  },
  resultValue: {
    fontSize: 16,
    fontWeight: '600',
    color: '#34C759',
  },
  resultTotalRow: {
    marginTop: 8,
    paddingTop: 8,
    borderTopWidth: 1,
    borderTopColor: 'rgba(52, 199, 89, 0.3)',
  },
  resultTotalLabel: {
    fontSize: 15,
    fontWeight: 'bold',
    opacity: 0.9,
  },
  resultTotal: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#34C759',
  },
});