<template>
    <div class="space-y-6">
        <!-- Départ -->
        <div>
            <label class="block font-medium text-sm mb-1">Adresse de départ</label>
            <GoogleAddressInput v-model="departure" />
        </div>

        <!-- Étapes -->
        <div>
            <label class="block font-medium text-sm mb-1">Étapes intermédiaires</label>
            <div class="space-y-2">
                <div v-for="(step, index) in steps" :key="index" class="flex items-center gap-2">
                    <GoogleAddressInput v-model="steps[index]" class="flex-1" />
                    <Button variant="ghost" size="icon" @click="removeStep(index)">
                        <Trash2Icon class="w-4 h-4 text-destructive" />
                    </Button>
                </div>
            </div>
            <Button variant="outline" size="sm" class="mt-2" @click="addStep">
                <PlusIcon class="w-4 h-4 mr-1" />
                Ajouter une étape
            </Button>
        </div>

        <!-- Arrivée -->
        <div>
            <label class="block font-medium text-sm mb-1">Adresse d’arrivée</label>
            <GoogleAddressInput v-model="arrival" />
        </div>

        <!-- Km Google -->
        <div>
            <label class="block font-medium text-sm mb-1">Distance Google Maps</label>
            <div>{{ Math.round(googleKm.toFixed(1)) }} km</div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { Trash2Icon, PlusIcon } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import GoogleAddressInput from './GoogleAddressInput.vue';
import { Button } from '@/components/ui/button';
import { loadGoogleMaps } from '@/utils/googleMapsService';

const props = defineProps({
    modelValue: Object,
    travel_mode: { type: String, default: 'car' } // ← injecté par le parent
});
const emit = defineEmits(['update:modelValue']);

const departure = ref('');
const arrival = ref('');
const steps = ref([]);

const googleKm = ref(0);
const manualKm = ref(0);
const justification = ref('');

const totalKm = computed(() => googleKm.value + manualKm.value);

const addStep = () => steps.value.push('');
const removeStep = (i) => steps.value.splice(i, 1);

let directionsService = null;

onMounted(async () => {
    const google = await loadGoogleMaps();
    directionsService = new google.maps.DirectionsService();
});

// Empêche le champ manuelKm d'être vidé
const onManualKmInput = (e) => {
    if (e.target.value === '') {
        manualKm.value = 0;
    }
};

const onManualKmBlur = () => {
    if (manualKm.value === null || manualKm.value === '') {
        manualKm.value = 0;
    }
};

watch([departure, arrival, steps], async () => {
    if (departure.value && arrival.value) {
        await calculateDistance();
    }
}, { deep: true });

watch([googleKm, manualKm, justification, departure, arrival, steps], () => {
    emit('update:modelValue', {
        departure: departure.value,
        arrival: arrival.value,
        steps: steps.value.filter(e => e),
        googleKm: Math.round(googleKm.value),
        manualKm: Math.round(manualKm.value),
        justification: manualKm.value !== 0 ? justification.value : null,
        totalKm: totalKm.value
    });
}, { deep: true });
const calculateDistance = async () => {
    if (!directionsService) return;

    const waypoints = steps.value
        .filter(s => s)
        .map(address => ({ location: address, stopover: true }));

    // Déterminer le mode Google Maps
    let travelMode = google.maps.TravelMode.DRIVING;
    if (props.travel_mode === 'bike') {
        travelMode = google.maps.TravelMode.BICYCLING;
    } else if (props.travel_mode === 'other') {
        // Pas de mode direct "other" → on peut rester en DRIVING ou mettre WALKING
        travelMode = google.maps.TravelMode.WALKING;
    }

    directionsService.route(
        {
            origin: departure.value,
            destination: arrival.value,
            waypoints,
            travelMode
        },
        (result, status) => {
            if (status === 'OK') {
                let distanceMeters = 0;
                result.routes[0].legs.forEach(leg => {
                    distanceMeters += leg.distance.value;
                });
                googleKm.value = distanceMeters / 1000;
            } else {
                googleKm.value = 0;
                console.warn('Erreur Google Maps API :', status);
            }
        }
    );
};
</script>
