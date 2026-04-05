<template>
  <div
    ref="track"
    class="relative flex w-full touch-none select-none items-center"
    @mousedown="startDrag"
    @touchstart="startDrag"
  >
    <!-- track -->
    <div class="relative h-1.5 w-full grow overflow-hidden rounded-full bg-primary/20">
      <!-- range -->
      <div
        class="absolute h-full bg-primary"
        :style="{ width: value + '%' }"
      />
    </div>

    <!-- thumb -->
    <div
      class="absolute top-1/2 -translate-y-1/2 h-4 w-4 rounded-full border border-primary/50 bg-background shadow"
      :style="{ left: value + '%' }"
    />
  </div>
</template>

<script setup>
import { ref, watch } from "vue";

const props = defineProps({
  modelValue: {
    type: Number,
    default: 0,
  },
});

const emit = defineEmits(["update:modelValue"]);

const track = ref(null);
const value = ref(props.modelValue);

watch(
  () => props.modelValue,
  (v) => (value.value = v)
);

function setFromEvent(e) {
  const rect = track.value.getBoundingClientRect();
  const clientX = e.touches ? e.touches[0].clientX : e.clientX;

  let percent = ((clientX - rect.left) / rect.width) * 100;
  percent = Math.min(100, Math.max(0, percent));

  value.value = percent;
  emit("update:modelValue", percent);
}

function startDrag(e) {
  setFromEvent(e);

  window.addEventListener("mousemove", setFromEvent);
  window.addEventListener("mouseup", stopDrag);
  window.addEventListener("touchmove", setFromEvent);
  window.addEventListener("touchend", stopDrag);
}

function stopDrag() {
  window.removeEventListener("mousemove", setFromEvent);
  window.removeEventListener("mouseup", stopDrag);
  window.removeEventListener("touchmove", setFromEvent);
  window.removeEventListener("touchend", stopDrag);
}
</script>