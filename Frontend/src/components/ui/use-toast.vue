<template>
  <div class="fixed top-4 right-4 flex flex-col gap-2 z-[9999]">
    <transition-group name="toast">
      <div
        v-for="t in state.toasts"
        :key="t.id"
        :class="[
          'rounded-md shadow-lg p-4 w-80 border transition-all',
          t.variant === 'destructive'
            ? 'bg-destructive text-destructive-foreground border-destructive'
            : 'bg-background text-foreground border-border'
        ]"
      >
        <div class="flex justify-between items-start">
          <div>
            <p class="font-medium text-sm">{{ t.title }}</p>
            <p
              v-if="t.description"
              :class="[
                'text-xs mt-1',
                t.variant === 'destructive'
                  ? 'text-destructive-foreground/80'
                  : 'text-muted-foreground'
              ]"
            >
              {{ t.description }}
            </p>
          </div>

          <button
            :class="[
              'text-sm ml-2 shrink-0',
              t.variant === 'destructive'
                ? 'text-destructive-foreground/70 hover:text-destructive-foreground'
                : 'text-muted-foreground hover:text-foreground'
            ]"
            @click="dismiss(t.id)"
          >
            ✕
          </button>
        </div>
      </div>
    </transition-group>
  </div>
</template>

<script setup>
import { state, dismiss } from "@/composables/use-toast";
</script>

<style scoped>
.toast-enter-active {
  transition: all 0.3s ease-out;
}
.toast-leave-active {
  transition: all 0.2s ease-in;
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}
</style>