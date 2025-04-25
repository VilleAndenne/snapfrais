<template>
    <input
        type="text"
        ref="inputRef"
        :value="modelValue"
        @input="onInput"
        class="form-input w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
        placeholder="Commencez à taper une adresse..."
    />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { loadGoogleMaps } from '@/utils/googleMapsService'

const props = defineProps({
    modelValue: String
})

const emit = defineEmits(['update:modelValue'])

const inputRef = ref(null)
let autocomplete = null

const onInput = (e) => {
    emit('update:modelValue', e.target.value)
}

onMounted(async () => {
    const google = await loadGoogleMaps()

    autocomplete = new google.maps.places.Autocomplete(inputRef.value, {
        types: ['geocode'],
        componentRestrictions: { country: 'be' }
    })

    autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace()
        if (place && place.formatted_address) {
            emit('update:modelValue', place.formatted_address)
        }
    })
})
</script>
