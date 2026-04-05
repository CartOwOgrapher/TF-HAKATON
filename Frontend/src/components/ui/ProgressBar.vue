<template>
  <div :class="['flex items-center gap-3', customClass]">
    <div :class="['flex-1 bg-muted rounded-full overflow-hidden', heights[size]]">
      <div
        :class="['h-full rounded-full transition-all duration-500 ease-out', color]"
        :style="{ width: clampedValue + '%' }"
      />
    </div>

    <span
      v-if="showLabel"
      class="text-sm font-medium text-muted-foreground w-12 text-right"
    >
      {{ Math.round(clampedValue) }}%
    </span>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  value: {
    type: Number,
    default: 0,
  },
  size: {
    type: String,
    default: "md",
  },
  showLabel: {
    type: Boolean,
    default: true,
  },
  customClass: {
    type: String,
    default: "",
  },
});

const clampedValue = computed(() =>
  Math.min(100, Math.max(0, props.value))
);

const heights = {
  sm: "h-1.5",
  md: "h-2.5",
  lg: "h-4",
};

const color = computed(() => {
  if (clampedValue.value >= 80) return "bg-emerald-500";
  if (clampedValue.value >= 40) return "bg-amber-500";
  return "bg-blue-500";
});
</script>