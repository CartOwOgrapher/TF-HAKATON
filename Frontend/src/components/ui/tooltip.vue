<template>
  <div class="relative inline-block" @mouseenter="open = true" @mouseleave="open = false">
    <!-- trigger -->
    <slot name="trigger" />

    <!-- content -->
    <div
      v-if="open"
      class="absolute z-50 rounded-md bg-primary px-3 py-1.5 text-xs text-primary-foreground shadow"
      :style="styles"
    >
      <slot />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  side: {
    type: String,
    default: "top", // top | bottom | left | right
  },
  offset: {
    type: Number,
    default: 6,
  },
});

const open = ref(false);

// простое позиционирование без popper
const styles = computed(() => {
  const base = {};

  if (props.side === "top") {
    base.bottom = `calc(100% + ${props.offset}px)`;
    base.left = "50%";
    base.transform = "translateX(-50%)";
  }

  if (props.side === "bottom") {
    base.top = `calc(100% + ${props.offset}px)`;
    base.left = "50%";
    base.transform = "translateX(-50%)";
  }

  if (props.side === "left") {
    base.right = `calc(100% + ${props.offset}px)`;
    base.top = "50%";
    base.transform = "translateY(-50%)";
  }

  if (props.side === "right") {
    base.left = `calc(100% + ${props.offset}px)`;
    base.top = "50%";
    base.transform = "translateY(-50%)";
  }

  return base;
});
</script>