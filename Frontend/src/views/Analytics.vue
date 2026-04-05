<template>
  <div>
    <PageHeader
      title="Аналитика по компаниям"
      description="Обучение, стоимость и прогресс в разрезе компаний"
    />

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center h-96">
      <div class="w-8 h-8 border-4 border-muted border-t-primary rounded-full animate-spin" />
    </div>

    <template v-else>
      <!-- Пустое состояние -->
      <EmptyState
        v-if="companies.length === 0"
        :icon="BarChart3"
        title="Нет данных"
        description="Добавьте компании и сотрудников для отображения аналитики"
      />

      <template v-else>
        <!-- ─── Сводные карточки ─── -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <div
            v-for="card in summaryCards"
            :key="card.label"
            class="bg-card rounded-2xl border border-border p-5"
          >
            <div class="flex items-center gap-3 mb-3">
              <div
                class="w-10 h-10 rounded-xl flex items-center justify-center"
                :class="card.bgClass"
              >
                <component :is="card.icon" class="w-5 h-5" :class="card.iconClass" />
              </div>
              <p class="text-sm text-muted-foreground">{{ card.label }}</p>
            </div>
            <p class="text-2xl font-bold">{{ card.value }}</p>
          </div>
        </div>

        <!-- ─── Таблица по компаниям ─── -->
        <div class="bg-card rounded-2xl border border-border overflow-hidden mb-8">
          <div class="px-6 py-4 border-b border-border">
            <h2 class="text-lg font-semibold">Сводка по компаниям</h2>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-border bg-muted/30">
                  <th class="text-left px-6 py-3 font-medium text-muted-foreground">Компания</th>
                  <th class="text-center px-4 py-3 font-medium text-muted-foreground">Сотрудники</th>
                  <th class="text-center px-4 py-3 font-medium text-muted-foreground">На курсах</th>
                  <th class="text-center px-4 py-3 font-medium text-muted-foreground">Групп</th>
                  <th class="text-center px-4 py-3 font-medium text-muted-foreground">Спецификаций</th>
                  <th class="text-right px-4 py-3 font-medium text-muted-foreground">Стоимость (с НДС)</th>
                  <th class="text-center px-4 py-3 font-medium text-muted-foreground">Прогресс</th>
                  <th class="px-4 py-3"></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="c in companies"
                  :key="c.id"
                  class="border-b border-border last:border-0 hover:bg-muted/20 transition-colors"
                >
                  <td class="px-6 py-4">
                    <p class="font-medium">{{ c.name }}</p>
                    <p class="text-xs text-muted-foreground">{{ c.code }}</p>
                  </td>
                  <td class="text-center px-4 py-4">{{ c.total_employees }}</td>
                  <td class="text-center px-4 py-4">
                    <span
                      class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                      :class="c.trained_employees > 0
                        ? 'bg-green-500/10 text-green-600'
                        : 'bg-muted text-muted-foreground'"
                    >
                      {{ c.trained_employees }}
                    </span>
                  </td>
                  <td class="text-center px-4 py-4">{{ c.training_groups_count }}</td>
                  <td class="text-center px-4 py-4">{{ c.specifications_count }}</td>
                  <td class="text-right px-4 py-4 font-medium">
                    {{ formatMoney(c.total_cost_with_vat) }}
                  </td>
                  <td class="text-center px-4 py-4">
                    <div class="flex items-center justify-center gap-2">
                      <div class="w-16 h-2 rounded-full bg-muted overflow-hidden">
                        <div
                          class="h-full rounded-full transition-all"
                          :class="progressColor(c.avg_progress)"
                          :style="{ width: Math.min(c.avg_progress, 100) + '%' }"
                        />
                      </div>
                      <span class="text-xs text-muted-foreground w-10 text-right">
                        {{ c.avg_progress }}%
                      </span>
                    </div>
                  </td>
                  <td class="px-4 py-4">
                    <Button size="icon" variant="ghost" class="h-8 w-8" @click="openDetail(c)">
                      <Eye class="w-4 h-4" />
                    </Button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ─── Визуальные графики ─── -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <!-- Обученность по компаниям -->
          <div class="bg-card rounded-2xl border border-border p-6">
            <h3 class="text-sm font-semibold mb-4 text-muted-foreground">
              Сотрудники: всего vs на курсах
            </h3>
            <div class="space-y-3">
              <div v-for="c in companies" :key="'bar-' + c.id">
                <div class="flex items-center justify-between text-xs mb-1">
                  <span class="font-medium truncate max-w-[180px]">{{ c.name }}</span>
                  <span class="text-muted-foreground">
                    {{ c.trained_employees }} / {{ c.total_employees }}
                  </span>
                </div>
                <div class="flex gap-1 h-5">
                  <!-- Обученные -->
                  <div
                    class="bg-primary rounded-md transition-all"
                    :style="{
                      width: barWidth(c.trained_employees, maxEmployees) + '%',
                      minWidth: c.trained_employees > 0 ? '4px' : '0'
                    }"
                  />
                  <!-- Необученные -->
                  <div
                    class="bg-muted rounded-r-md transition-all"
                    :style="{
                      width: barWidth(c.total_employees - c.trained_employees, maxEmployees) + '%',
                      minWidth: (c.total_employees - c.trained_employees) > 0 ? '4px' : '0'
                    }"
                  />
                </div>
              </div>
            </div>
            <div class="flex items-center gap-4 mt-4 text-xs text-muted-foreground">
              <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-primary" /> На курсах
              </span>
              <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-muted" /> Не участвовали
              </span>
            </div>
          </div>

          <!-- Стоимость по компаниям -->
          <div class="bg-card rounded-2xl border border-border p-6">
            <h3 class="text-sm font-semibold mb-4 text-muted-foreground">
              Стоимость обучения по компаниям (с НДС)
            </h3>
            <div class="space-y-3">
              <div v-for="c in companies" :key="'cost-' + c.id">
                <div class="flex items-center justify-between text-xs mb-1">
                  <span class="font-medium truncate max-w-[180px]">{{ c.name }}</span>
                  <span class="text-muted-foreground">{{ formatMoney(c.total_cost_with_vat) }}</span>
                </div>
                <div class="h-5 bg-muted rounded-md overflow-hidden">
                  <div
                    class="h-full bg-amber-500 rounded-md transition-all"
                    :style="{
                      width: barWidth(c.total_cost_with_vat, maxCost) + '%',
                      minWidth: c.total_cost_with_vat > 0 ? '4px' : '0'
                    }"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </template>

    <!-- ─── Диалог детализации ─── -->
    <Dialog v-model="detailOpen">
      <div class="space-y-5 mt-2">
        <h2 class="text-lg font-semibold">
          {{ detailCompany?.name }}
          <span class="text-sm text-muted-foreground font-normal ml-2">{{ detailCompany?.code }}</span>
        </h2>

        <div v-if="detailLoading" class="flex justify-center py-8">
          <div class="w-6 h-6 border-4 border-muted border-t-primary rounded-full animate-spin" />
        </div>

        <template v-else-if="detail">
          <!-- Распределение по статусам -->
          <div v-if="Object.keys(detail.status_distribution).length > 0">
            <h3 class="text-sm font-semibold text-muted-foreground mb-2">Группы по статусам</h3>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="(count, status) in detail.status_distribution"
                :key="status"
                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium border border-border"
              >
                {{ statusLabel(String(status)) }}: {{ count }}
              </span>
            </div>
          </div>

          <!-- Сотрудники -->
          <div>
            <h3 class="text-sm font-semibold text-muted-foreground mb-2">
              Сотрудники ({{ detail.employees.length }})
            </h3>
            <div class="max-h-64 overflow-y-auto space-y-1">
              <div
                v-for="emp in detail.employees"
                :key="emp.id"
                class="flex items-center justify-between rounded-lg border border-border px-3 py-2 text-sm"
              >
                <div>
                  <p class="font-medium">{{ emp.full_name }}</p>
                  <p class="text-xs text-muted-foreground">{{ emp.email }}</p>
                </div>
                <div class="text-right">
                  <p class="text-xs text-muted-foreground">
                    {{ emp.groups_count }} {{ pluralGroups(emp.groups_count) }}
                  </p>
                  <div class="flex items-center gap-1.5 mt-0.5">
                    <div class="w-12 h-1.5 rounded-full bg-muted overflow-hidden">
                      <div
                        class="h-full rounded-full"
                        :class="progressColor(emp.avg_progress)"
                        :style="{ width: Math.min(emp.avg_progress, 100) + '%' }"
                      />
                    </div>
                    <span class="text-xs">{{ emp.avg_progress }}%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Спецификации -->
          <div v-if="detail.specifications.length > 0">
            <h3 class="text-sm font-semibold text-muted-foreground mb-2">
              Спецификации ({{ detail.specifications.length }})
            </h3>
            <div class="space-y-1">
              <div
                v-for="spec in detail.specifications"
                :key="spec.id"
                class="flex items-center justify-between rounded-lg border border-border px-3 py-2 text-sm"
              >
                <span class="font-medium">{{ spec.number }}</span>
                <span class="text-muted-foreground text-xs">{{ spec.date }}</span>
              </div>
            </div>
          </div>
        </template>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import {
  BarChart3,
  Building2,
  Users,
  FileText,
  Wallet,
  Eye,
} from "lucide-vue-next";

import Button from "@/components/ui/button.vue";
import Dialog from "@/components/ui/dialog.vue";
import PageHeader from "@/components/ui/PageHeader.vue";
import EmptyState from "@/components/ui/EmptyState.vue";

import { useAnalyticsStore } from "@/stores/useAnalyticsStore";

const store = useAnalyticsStore();

const companies = ref([]);
const loading = ref(true);

const detailOpen = ref(false);
const detailCompany = ref(null);
const detail = ref(null);
const detailLoading = ref(false);

// ─── Загрузка данных ───

onMounted(async () => {
  loading.value = true;
  try {
    companies.value = await store.fetchCompaniesSummary();
  } catch (e) {
    console.error("Ошибка загрузки аналитики:", e);
  } finally {
    loading.value = false;
  }
});

// ─── Сводные карточки ───

const summaryCards = computed(() => {
  const all = companies.value;
  return [
    {
      label: "Компаний",
      value: all.length,
      icon: Building2,
      bgClass: "bg-primary/10",
      iconClass: "text-primary",
    },
    {
      label: "Всего на курсах",
      value: all.reduce((s, c) => s + c.trained_employees, 0),
      icon: Users,
      bgClass: "bg-green-500/10",
      iconClass: "text-green-600",
    },
    {
      label: "Спецификаций",
      value: all.reduce((s, c) => s + c.specifications_count, 0),
      icon: FileText,
      bgClass: "bg-blue-500/10",
      iconClass: "text-blue-600",
    },
    {
      label: "Общая стоимость (с НДС)",
      value: formatMoney(all.reduce((s, c) => s + c.total_cost_with_vat, 0)),
      icon: Wallet,
      bgClass: "bg-amber-500/10",
      iconClass: "text-amber-600",
    },
  ];
});

// ─── Вспомогательные для графиков ───

const maxEmployees = computed(() =>
  Math.max(...companies.value.map((c) => c.total_employees), 1)
);

const maxCost = computed(() =>
  Math.max(...companies.value.map((c) => c.total_cost_with_vat), 1)
);

function barWidth(value, max) {
  if (!max || !value) return 0;
  return Math.round((value / max) * 100);
}

// ─── Форматирование ───

function formatMoney(value) {
  if (!value) return "0 ₽";
  return Number(value).toLocaleString("ru-RU", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }) + " ₽";
}

function progressColor(pct) {
  if (pct >= 80) return "bg-green-500";
  if (pct >= 40) return "bg-amber-500";
  return "bg-red-500";
}

function pluralGroups(n) {
  const mod = n % 10;
  const mod100 = n % 100;
  if (mod === 1 && mod100 !== 11) return "группа";
  if (mod >= 2 && mod <= 4 && (mod100 < 12 || mod100 > 14)) return "группы";
  return "групп";
}

const STATUS_LABELS = {
  planned: "Планируется",
  in_progress: "В процессе",
  ongoing: "В процессе",
  completed: "Завершено",
  cancelled: "Отменено",
  draft: "Черновик",
};

function statusLabel(status) {
  return STATUS_LABELS[status] || status;
}

// ─── Детализация ───

async function openDetail(company) {
  detailCompany.value = company;
  detailOpen.value = true;
  detail.value = null;
  detailLoading.value = true;
  try {
    detail.value = await store.fetchCompanyDetail(company.id);
  } catch (e) {
    console.error("Ошибка загрузки деталей:", e);
  } finally {
    detailLoading.value = false;
  }
}
</script>
