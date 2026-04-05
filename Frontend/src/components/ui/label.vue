<template>
  <label :class="classes" v-bind="$attrs">
    <slot />
  </label>
</template>

<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils.ts";

const props = defineProps({
  class: {
    type: String,
    default: "",
  },
});

const classes = computed(() =>
  cn(
    "text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70",
    props.class
  )
);
</script>