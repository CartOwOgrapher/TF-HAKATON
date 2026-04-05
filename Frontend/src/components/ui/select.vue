<template>
  <div class="relative w-full">
    <!-- Trigger -->
    <button
      type="button"
      class="flex w-full items-center justify-between rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
      @click="open = !open"
    >
      <span>
        {{ selectedLabel || placeholder }}
      </span>

      <svg
        class="h-4 w-4 opacity-60"
        viewBox="0 0 20 20"
        fill="currentColor"
      >
        <path
          fill-rule="evenodd"
          d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z"
          clip-rule="evenodd"
        />
      </svg>
    </button>

    <!-- Dropdown -->
    <div
      v-if="open"
      class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-white shadow-lg"
    >
      <div
        v-for="item in options"
        :key="item.value"
        class="cursor-pointer px-3 py-2 text-sm hover:bg-gray-100"
        :class="{ 'bg-gray-100': item.value === modelValue }"
        @click="selectItem(item.value)"
      >
        {{ item.label }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";

type Option = {
  label: string;
  value: string | number;
};

const props = defineProps<{
  modelValue: string | number | null;
  options: Option[];
  placeholder?: string;
}>();

const emit = defineEmits(["update:modelValue"]);

const open = ref(false);

const selectedLabel = computed(() => {
  return props.options.find(o => o.value === props.modelValue)?.label;
});

function selectItem(value: string | number) {
  emit("update:modelValue", value);
  open.value = false;
}
</script>