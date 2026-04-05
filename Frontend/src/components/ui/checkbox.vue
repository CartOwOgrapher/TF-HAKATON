<template>
  <button
    type="button"
    class="h-4 w-4 shrink-0 rounded-sm border border-primary shadow flex items-center justify-center transition"
    :class="checked ? 'bg-primary text-primary-foreground' : 'bg-transparent'"
    :disabled="disabled"
    @click="toggle"
  >
    <Check v-if="checked" class="h-3 w-3" />
  </button>
</template>

<script setup>
import { computed } from "vue"
import { Check } from "lucide-vue-next"

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(["update:modelValue"])

const checked = computed(() => props.modelValue)

function toggle() {
  if (props.disabled) return
  emit("update:modelValue", !props.modelValue)
}
</script>