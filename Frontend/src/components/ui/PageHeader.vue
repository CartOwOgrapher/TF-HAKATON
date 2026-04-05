<template>
  <div class="flex items-start justify-between mb-8">
    <div>
      <h1 class="text-2xl font-bold tracking-tight">{{ title }}</h1>

      <p v-if="description" class="text-muted-foreground mt-1">
        {{ description }}
      </p>
    </div>

    <div v-if="$slots.default" class="flex items-center gap-3">
      <slot />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: "",
  },
});
</script>