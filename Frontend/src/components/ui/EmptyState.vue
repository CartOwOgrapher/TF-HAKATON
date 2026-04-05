<template>
  <div :class="classes">
    <div v-if="icon" class="w-16 h-16 rounded-2xl bg-muted flex items-center justify-center mb-4">
      <component :is="icon" class="w-8 h-8 text-muted-foreground" />
    </div>

    <h3 class="text-lg font-semibold mb-1">
      {{ title }}
    </h3>

    <p v-if="description" class="text-sm text-muted-foreground max-w-sm">
      {{ description }}
    </p>

    <div v-if="$slots.default" class="mt-4">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils.ts";

const props = defineProps({
  icon: {
    type: [Object, Function],
    default: null,
  },
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: "",
  },
  class: {
    type: String,
    default: "",
  },
});

const classes = computed(() =>
  cn(
    "flex flex-col items-center justify-center py-16 text-center",
    props.class
  )
);
</script>