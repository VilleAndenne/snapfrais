<template>
  <Input
    ref="inputWrapper"
    v-model="internalValue"
    class="w-full"
    type="text"
    placeholder="Commencez à taper une adresse..."
  />
</template>

<script setup>
import { ref, watch, onMounted } from "vue";
import { loadGoogleMaps } from "@/utils/googleMapsService";
import Input from "../ui/input/Input.vue";

const inputWrapper = ref(null);

const props = defineProps({
  modelValue: String,
});

const emit = defineEmits(["update:modelValue"]);

// Internal mirror of modelValue
const internalValue = ref(props.modelValue || "");

// Sync vers parent
watch(internalValue, (val) => {
  emit("update:modelValue", val);
});

// Si le parent change la valeur externement
watch(() => props.modelValue, (val) => {
  internalValue.value = val;
});

onMounted(async () => {
  const google = await loadGoogleMaps();

  const inputElement = inputWrapper.value?.$el;
  if (!inputElement) return;

  const autocomplete = new google.maps.places.Autocomplete(inputElement, {
    types: ["geocode"],
    componentRestrictions: { country: "be" },
  });

  autocomplete.addListener("place_changed", () => {
    const place = autocomplete.getPlace();
    if (place && place.formatted_address) {
      internalValue.value = place.formatted_address;
    }
  });
});
</script>
