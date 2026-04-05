<template>
  <div class="bg-muted/50 rounded-xl p-4 space-y-2">
    <h4 class="text-sm font-semibold mb-3">Расчёт стоимости</h4>

    <div class="flex justify-between text-sm">
      <span class="text-muted-foreground">Цена за человека</span>
      <span class="font-medium">{{ fmt(pricePerPerson) }} ₽</span>
    </div>

    <div class="flex justify-between text-sm">
      <span class="text-muted-foreground">Количество участников</span>
      <span class="font-medium">{{ participantCount }}</span>
    </div>

    <div class="border-t border-border my-2" />

    <div class="flex justify-between text-sm">
      <span class="text-muted-foreground">Итого без НДС</span>
      <span class="font-medium">{{ fmt(totalWithoutVat) }} ₽</span>
    </div>

    <div class="flex justify-between text-sm">
      <span class="text-muted-foreground">НДС (22%)</span>
      <span class="font-medium">{{ fmt(vatAmount) }} ₽</span>
    </div>

    <div class="border-t border-border my-2" />

    <div class="flex justify-between text-sm font-semibold">
      <span>Итого с НДС</span>
      <span class="text-primary">{{ fmt(totalWithVat) }} ₽</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";

const VAT_RATE = 0.22;

const props = defineProps({
  pricePerPerson: {
    type: Number,
    default: 0,
  },
  participantCount: {
    type: Number,
    default: 0,
  },
});

const totalWithoutVat = computed(() => {
  return (props.pricePerPerson || 0) * (props.participantCount || 0);
});

const vatAmount = computed(() => {
  return totalWithoutVat.value * VAT_RATE;
});

const totalWithVat = computed(() => {
  return totalWithoutVat.value + vatAmount.value;
});

function fmt(n) {
  return Number(n || 0).toLocaleString("ru-RU", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}
</script>