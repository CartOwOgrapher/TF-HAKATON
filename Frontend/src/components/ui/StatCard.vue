<template>
  <div :class="classes">
    <div class="flex items-start justify-between">
      <div class="space-y-1">
        <p class="text-sm text-muted-foreground font-medium">
          {{ title }}
        </p>

        <p class="text-3xl font-bold tracking-tight">
          {{ value }}
        </p>

        <p v-if="subtitle" class="text-xs text-muted-foreground">
          {{ subtitle }}
        </p>
      </div>

      <div
        v-if="icon"
        class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center"
      >
        <component :is="icon" class="w-5 h-5 text-primary" />
      </div>
    </div>

    <div v-if="trend !== undefined" class="mt-3 flex items-center gap-1.5">
      <span
        :class="[
          'text-xs font-medium',
          trend >= 0 ? 'text-emerald-600' : 'text-red-500'
        ]"
      >
        {{ trend >= 0 ? '+' : '' }}{{ trend }}%
      </span>

      <span class="text-xs text-muted-foreground">
        за месяц
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";

const props = defineProps({
  title: String,
  value: [String, Number],
  subtitle: String,
  icon: [Object, Function],
  trend: Number,
  class: {
    type: String,
    default: "",
  },
});

const classes = computed(() =>
  cn(
    "bg-card rounded-2xl border border-border p-6 transition-all hover:shadow-md",
    props.class
  )
);
</script>