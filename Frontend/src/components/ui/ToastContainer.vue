<template>
  <div class="fixed top-4 right-4 flex flex-col gap-2 z-50">
    <div
      v-for="t in state.toasts"
      :key="t.id"
      class="bg-background border rounded-md shadow p-4 w-80"
    >
      <div class="flex justify-between items-start">
        <div>
          <p class="font-medium">{{ t.title }}</p>
          <p v-if="t.description" class="text-sm text-muted-foreground">
            {{ t.description }}
          </p>
        </div>
        <button class="text-sm ml-2" @click="dismiss(t.id)">✕</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { state, dismiss } from '@/composables/use-toast'
</script>