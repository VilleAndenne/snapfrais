<template>
    <div class="relative w-full">
        <input
            type="text"
            ref="inputRef"
            :value="modelValue"
            @input="onInput"
            @focus="onFocus"
            @blur="onBlur"
            class="form-input w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
            placeholder="Commencez à taper une adresse..."
        />

        <!-- Liste déroulante des adresses récentes -->
        <div
            v-if="showSuggestions && filteredHistory.length > 0"
            class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-y-auto"
        >
            <div
                v-for="(address, index) in filteredHistory"
                :key="index"
                @mousedown.prevent="selectAddress(address)"
                class="px-4 py-2 cursor-pointer hover:bg-indigo-50 dark:hover:bg-gray-700 flex items-center justify-between group"
            >
                <div class="flex items-center flex-1 min-w-0">
                    <svg class="w-4 h-4 mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ address }}</span>
                </div>
                <button
                    @click.stop="removeAddress(address)"
                    class="ml-2 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0"
                    title="Supprimer de l'historique"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { loadGoogleMaps } from '@/utils/googleMapsService'
import {
    getAddressHistory,
    saveAddressToHistory,
    removeAddressFromHistory,
    filterAddressHistory
} from '@/utils/addressHistoryService'

const props = defineProps({
    modelValue: String
})

const emit = defineEmits(['update:modelValue'])

const inputRef = ref(null)
const showSuggestions = ref(false)
const addressHistory = ref([])
let autocomplete = null

const filteredHistory = computed(() => {
    if (!props.modelValue || props.modelValue.trim() === '') {
        return addressHistory.value
    }
    return filterAddressHistory(props.modelValue)
})

const onInput = (e) => {
    emit('update:modelValue', e.target.value)
}

const onFocus = () => {
    addressHistory.value = getAddressHistory()
    showSuggestions.value = true
}

const onBlur = () => {
    // Délai pour permettre le clic sur une suggestion
    setTimeout(() => {
        showSuggestions.value = false
    }, 200)
}

const selectAddress = (address) => {
    emit('update:modelValue', address)
    showSuggestions.value = false
    inputRef.value?.blur()
}

const removeAddress = (address) => {
    removeAddressFromHistory(address)
    addressHistory.value = getAddressHistory()
}

onMounted(async () => {
    const google = await loadGoogleMaps()

    // Charger l'historique initial
    addressHistory.value = getAddressHistory()

    autocomplete = new google.maps.places.Autocomplete(inputRef.value, {
        types: ['geocode'],
        componentRestrictions: { country: 'be' }
    })

    autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace()
        if (place && place.formatted_address) {
            emit('update:modelValue', place.formatted_address)
            // Sauvegarder l'adresse dans l'historique
            saveAddressToHistory(place.formatted_address)
            addressHistory.value = getAddressHistory()
        }
    })
})
</script>
