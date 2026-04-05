<template>
  <div>
    <PageHeader title="Спецификации" description="Финансовые документы обучения">
      <Button class="gap-2" @click="openCreate">
        <Plus class="w-4 h-4" />
        Создать спецификацию
      </Button>
    </PageHeader>

    <!-- Search -->
    <div class="relative mb-6 max-w-sm">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
      <Input v-model="search" placeholder="Поиск..." class="pl-10" />
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center h-96">
      <div class="w-8 h-8 border-4 border-muted border-t-primary rounded-full animate-spin" />
    </div>

    <!-- Content -->
    <template v-else>
      <EmptyState
        v-if="filtered.length === 0"
        :icon="FileText"
        title="Нет спецификаций"
        description="Создайте первую спецификацию"
      >
        <Button variant="outline" class="gap-2" @click="openCreate">
          <Plus class="w-4 h-4" /> Создать
        </Button>
      </EmptyState>

      <!-- Table -->
      <div v-else class="bg-card rounded-2xl border border-border overflow-hidden">
        <table class="w-full">
          <thead>
            <tr class="border-b border-border bg-muted/50">
              <th class="text-left text-xs px-6 py-3">№ документа</th>
              <th class="text-left text-xs px-6 py-3">Дата</th>
              <th class="text-left text-xs px-6 py-3">Компания</th>
              <th class="text-right text-xs px-6 py-3">Без НДС</th>
              <th class="text-right text-xs px-6 py-3">С НДС</th>
              <th class="text-right text-xs px-6 py-3">Действия</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-border">
            <tr
              v-for="spec in filtered"
              :key="spec.id"
              class="hover:bg-muted/30 transition-colors"
            >
              <td class="px-6 py-4 font-medium">{{ spec.number }}</td>
              <td class="px-6 py-4 text-sm">{{ spec.date }}</td>
              <td class="px-6 py-4 text-sm">{{ spec.company?.name || "—" }}</td>
              <td class="px-6 py-4 text-sm text-right font-medium">
                {{ fmt(spec.total_without_vat) }} ₽
              </td>
              <td class="px-6 py-4 text-sm text-right font-semibold text-primary">
                {{ fmt(spec.total_with_vat) }} ₽
              </td>
              <td class="px-6 py-4 text-right">
                <Button size="icon" variant="ghost" class="h-8 w-8" @click="openDetail(spec)">
                  <Eye class="w-3.5 h-3.5" />
                </Button>
                <Button size="icon" variant="ghost" class="h-8 w-8" @click="openEdit(spec)">
                  <Pencil class="w-3.5 h-3.5" />
                </Button>
                <Button size="icon" variant="ghost" class="h-8 w-8 text-destructive" @click="handleDelete(spec.id)">
                  <Trash2 class="w-3.5 h-3.5" />
                </Button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- CREATE / EDIT Dialog -->
    <Dialog v-model="dialogOpen">
      <div class="space-y-4 mt-2">
        <h2 class="text-lg font-semibold">
          {{ editing ? "Редактировать" : "Новая спецификация" }}
        </h2>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <Label>Номер документа *</Label>
            <Input
              v-model="form.number"
              placeholder="SPEC-001"
              :class="fe.number ? 'border-destructive' : ''"
            />
            <p v-if="fe.number" class="text-xs text-destructive mt-1">{{ fe.number }}</p>
          </div>
          <div>
            <Label>Дата *</Label>
            <Input
              type="date"
              v-model="form.date"
              :class="fe.date ? 'border-destructive' : ''"
            />
            <p v-if="fe.date" class="text-xs text-destructive mt-1">{{ fe.date }}</p>
          </div>
        </div>

        <div>
          <Label>Компания *</Label>
          <Select
            v-model="form.company_id"
            :options="companyOptions"
            placeholder="Выберите компанию"
            :class="fe.company_id ? 'border-destructive' : ''"
          />
          <p v-if="fe.company_id" class="text-xs text-destructive mt-1">{{ fe.company_id }}</p>
        </div>

        <Button
          class="w-full"
          :disabled="isFormInvalid"
          @click="handleSave"
        >
          {{ editing ? "Сохранить" : "Создать" }}
        </Button>
      </div>
    </Dialog>

    <!-- DETAIL Dialog (show with groups & costs) -->
    <Dialog v-model="detailDialogOpen">
      <div class="space-y-4 mt-2">
        <h2 class="text-lg font-semibold">
          Спецификация №{{ detailData?.number }}
        </h2>

        <div v-if="detailLoading" class="flex justify-center py-4">
          <div class="w-6 h-6 border-4 border-muted border-t-primary rounded-full animate-spin" />
        </div>

        <template v-else-if="detailData">
          <div class="flex justify-between text-sm">
            <span class="text-muted-foreground">Дата</span>
            <span>{{ detailData.date }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-muted-foreground">Компания</span>
            <span>{{ detailData.company?.name }}</span>
          </div>

          <!-- Groups inside spec -->
          <div v-if="detailData.training_groups?.length" class="space-y-2">
            <p class="text-sm font-medium">Группы обучения</p>
            <div class="border rounded-lg divide-y divide-border max-h-40 overflow-y-auto">
              <div
                v-for="g in detailData.training_groups"
                :key="g.id"
                class="flex items-center justify-between px-4 py-2.5 text-sm"
              >
                <span>{{ g.course?.name || `Группа #${g.id}` }}</span>
                <span class="text-muted-foreground">{{ fmt(g.group_cost) }} ₽</span>
              </div>
            </div>
          </div>

          <div class="border-t border-border my-2" />

          <div class="flex justify-between text-sm">
            <span class="text-muted-foreground">Без НДС</span>
            <span class="font-medium">{{ fmt(detailData.total_without_vat) }} ₽</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-muted-foreground">НДС (22%)</span>
            <span class="font-medium">{{ fmt(detailData.vat_amount) }} ₽</span>
          </div>
          <div class="flex justify-between text-sm font-semibold">
            <span>С НДС</span>
            <span class="text-primary">{{ fmt(detailData.total_with_vat) }} ₽</span>
          </div>
        </template>

        <!-- Manage groups -->
        <div class="border-t border-border pt-4">
          <p class="text-sm font-medium mb-2">Управление группами</p>
          <div class="border rounded-lg divide-y divide-border max-h-40 overflow-y-auto">
            <label
              v-for="g in allGroups"
              :key="g.id"
              class="flex items-center gap-3 px-4 py-2.5 hover:bg-muted/50 cursor-pointer"
            >
              <Checkbox
                :model-value="isGroupAttached(g.id)"
                @update:model-value="() => toggleGroupAttach(g.id)"
              />
              <div class="flex-1">
                <span class="text-sm">{{ g.course?.name || `Группа #${g.id}` }}</span>
                <span class="text-xs text-muted-foreground ml-2">{{ fmt(g.group_cost) }} ₽</span>
              </div>
            </label>
          </div>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import {
  FileText, Plus, Pencil, Trash2, Search, Eye,
} from "lucide-vue-next";

import Button from "@/components/ui/button.vue";
import Input from "@/components/ui/input.vue";
import Dialog from "@/components/ui/dialog.vue";
import Label from "@/components/ui/label.vue";
import Select from "@/components/ui/select.vue";
import Checkbox from "@/components/ui/checkbox.vue";
import PageHeader from "@/components/ui/PageHeader.vue";
import EmptyState from "@/components/ui/EmptyState.vue";

import { toast } from "@/composables/use-toast";
import { useSpecificationStore } from "@/stores/useSpecificationStore";

import { companyService } from "@/api/companyService";
import { trainingGroupService } from "@/api/trainingGroupService";

const specStore = useSpecificationStore();

const specs = ref([]);
const companies = ref([]);
const allGroups = ref([]);

const loading = ref(true);
const search = ref("");

/* ── Create / Edit ── */
const dialogOpen = ref(false);
const editing = ref(null);
const emptyForm = { number: "", date: "", company_id: "" };
const form = ref({ ...emptyForm });

const fe = computed(() => specStore.fieldErrors);

const isFormInvalid = computed(() =>
  !form.value.number?.trim() ||
  !form.value.date ||
  !form.value.company_id
);

const companyOptions = computed(() =>
  companies.value.map((c) => ({ label: c.name, value: c.id }))
);

/* ── Detail ── */
const detailDialogOpen = ref(false);
const detailData = ref(null);
const detailLoading = ref(false);

/* ── Filtered list ── */
const filtered = computed(() => {
  const q = search.value.toLowerCase();
  return specs.value.filter(
    (s) =>
      (s.number || "").toLowerCase().includes(q) ||
      (s.company?.name || "").toLowerCase().includes(q)
  );
});

/* ── Data loading ── */
const load = async () => {
  loading.value = true;
  try {
    const [s, c, g] = await Promise.all([
      specStore.read_specifications(),
      companyService.list().then((r) => {
        console.log('companies raw response:', r.data);
        const root = r.data;
        // Пробуем все возможные структуры:
        // { companies: [...] }
        // { data: [...] }
        // { data: { data: [...] } }
        // [...] напрямую
        if (Array.isArray(root)) return root;
        if (Array.isArray(root.companies)) return root.companies;
        if (Array.isArray(root.data)) return root.data;
        if (root.data && Array.isArray(root.data.data)) return root.data.data;
        return [];
      }),
      trainingGroupService.list().then((r) => {
        const d = r.data.data;
        return Array.isArray(d) ? d : d?.data ?? [];
      }),
    ]);

    specs.value = Array.isArray(s) ? s : [];
    companies.value = Array.isArray(c) ? c : [];
    allGroups.value = Array.isArray(g) ? g : [];
  } catch {
  } finally {
    loading.value = false;
  }
};

onMounted(load);

/* ── Actions ── */
const openCreate = () => {
  editing.value = null;
  form.value = { ...emptyForm };
  specStore.fieldErrors = {};
  dialogOpen.value = true;
};

const openEdit = (spec) => {
  editing.value = spec;
  form.value = {
    number: spec.number || "",
    date: spec.date || "",
    company_id: spec.company_id || spec.company?.id || "",
  };
  specStore.fieldErrors = {};
  dialogOpen.value = true;
};

const handleSave = async () => {
  loading.value = true;
  try {
    const payload = {
      number: form.value.number,
      date: form.value.date,
      company_id: Number(form.value.company_id),
    };

    if (editing.value) {
      await specStore.update_specification(editing.value.id, payload);
      toast({ title: "Спецификация обновлена" });
    } else {
      await specStore.create_specification(payload);
      toast({ title: "Спецификация создана" });
    }

    dialogOpen.value = false;
    await load();
  } catch (e) {
    const msg =
      e?.response?.data?.message ||
      e?.response?.data?.error ||
      specStore.error ||
      e?.message ||
      "Не удалось сохранить спецификацию";
    toast({
      title: "Ошибка сохранения",
      description: msg,
      variant: "destructive",
    });
  } finally {
    loading.value = false;
  }
};

const handleDelete = async (id) => {
  try {
    await specStore.delete_specification(id);
    toast({ title: "Спецификация удалена" });
    await load();
  } catch (e) {
    const msg =
      e?.response?.data?.message ||
      e?.response?.data?.error ||
      specStore.error ||
      e?.message ||
      "Не удалось удалить спецификацию";
    toast({
      title: "Ошибка удаления",
      description: msg,
      variant: "destructive",
    });
  }
};

/* ── Detail with groups ── */
const openDetail = async (spec) => {
  detailDialogOpen.value = true;
  detailLoading.value = true;
  try {
    detailData.value = await specStore.show_specification(spec.id);
  } catch {
  } finally {
    detailLoading.value = false;
  }
};

const isGroupAttached = (groupId) => {
  return detailData.value?.training_groups?.some((g) => g.id === groupId) ?? false;
};

const toggleGroupAttach = async (groupId) => {
  if (!detailData.value) return;

  const attaching = !isGroupAttached(groupId);

  try {
    if (attaching) {
      await specStore.attach_group(detailData.value.id, groupId);
      toast({ title: "Группа привязана" });
    } else {
      await specStore.detach_group(detailData.value.id, groupId);
      toast({ title: "Группа отвязана" });
    }

    // Перезагружаем детали чтобы получить обновлённые totals
    detailData.value = await specStore.show_specification(detailData.value.id);
    // Обновляем список для актуальных сумм
    specs.value = await specStore.read_specifications();
  } catch (e) {
    // Берём message из любого места в ответе бэкенда
    const msg =
      e?.response?.data?.message ||
      e?.response?.data?.error ||
      specStore.error ||
      e?.message ||
      "Не удалось выполнить операцию";
    toast({
      title: attaching ? "Не удалось привязать группу" : "Не удалось отвязать группу",
      description: msg,
      variant: "destructive",
    });
  }
};

/* ── Helpers ── */
function fmt(n) {
  return (n || 0).toLocaleString("ru-RU", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}
</script>