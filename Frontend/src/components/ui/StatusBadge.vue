<template>
  <span :class="classes">
    <span :class="['w-1.5 h-1.5 rounded-full', config.dot]" />
    {{ status }}
  </span>
</template>

<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils.ts";

const props = defineProps({
  status: {
    type: String,
    default: "Планируется",
  },
});

const statusConfig = {
  "Планируется": {
    bg: "bg-blue-100",
    text: "text-blue-700",
    border: "border-blue-200",
    dot: "bg-blue-500",
  },
  "В процессе": {
    bg: "bg-amber-100",
    text: "text-amber-700",
    border: "border-amber-200",
    dot: "bg-amber-500",
  },
  "Завершено": {
    bg: "bg-emerald-100",
    text: "text-emerald-700",
    border: "border-emerald-200",
    dot: "bg-emerald-500",
  },
};

const config = computed(() =>
  statusConfig[props.status] || statusConfig["Планируется"]
);

const classes = computed(() =>
  cn(
    "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border",
    config.value.bg,
    config.value.text,
    config.value.border
  )
);
</script>