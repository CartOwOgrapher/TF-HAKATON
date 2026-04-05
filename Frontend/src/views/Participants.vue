<template>
  <div>
    <PageHeader title="Участники обучения" description="Реестр сотрудников для обучения">
      <Button @click="openCreate" class="gap-2" :disabled="store.loading">
        <Plus class="w-4 h-4" /> Добавить участника
      </Button>
    </PageHeader>

    <div class="relative mb-6 max-w-sm">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
      <Input v-model="search" placeholder="Поиск по ФИО, email, компании..." class="pl-10" />
    </div>

    <div v-if="loading" class="flex items-center justify-center h-96">
      <div class="w-8 h-8 border-4 border-muted border-t-primary rounded-full animate-spin" />
    </div>

    <template v-else>
      <EmptyState
        v-if="filtered.length === 0"
        :icon="Users"
        title="Нет участников"
        :description="search ? 'Ничего не найдено по вашему запросу' : 'Добавьте первого участника'"
      >
        <Button @click="openCreate" variant="outline" class="gap-2">
          <Plus class="w-4 h-4" /> Добавить
        </Button>
      </EmptyState>

      <div v-else class="bg-card rounded-2xl border border-border overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-border bg-muted/50">
                <th class="text-left px-6 py-3 text-xs uppercase text-muted-foreground">ФИО</th>
                <th class="text-left px-6 py-3 text-xs uppercase text-muted-foreground">Компания</th>
                <th class="text-left px-6 py-3 text-xs uppercase text-muted-foreground">Email</th>
                <th class="text-left px-6 py-3 text-xs uppercase text-muted-foreground min-w-[200px]">Учебные группы</th>
                <th class="text-right px-6 py-3 text-xs uppercase text-muted-foreground w-24">Действия</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-border">
              <tr v-for="emp in filtered" :key="emp.id" class="hover:bg-muted/30 transition-colors" :class="{ 'opacity-50': deletingId === emp.id }">
                <td class="px-6 py-4 font-medium truncate max-w-[200px]">{{ emp.full_name }}</td>
                <td class="px-6 py-4 text-sm text-muted-foreground truncate max-w-[150px]">{{ emp.company_name || '—' }}</td>
                <td class="px-6 py-4 text-sm truncate max-w-[180px]">{{ emp.email || '—' }}</td>

                <td class="px-6 py-4">
                  <div class="flex flex-wrap gap-1.5">
                    <template v-if="emp.training_groups?.length">
                      <router-link
                        v-for="grp in emp.training_groups"
                        :key="grp.id"
                        :to="`/groups/${grp.id}`"
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary hover:bg-primary/20 transition-colors"
                        :title="`${grp.name} • ${getStatusLabel(grp.status)}`"
                      >
                        {{ grp.name }}
                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" :class="getStatusColor(grp.status)" />
                      </router-link>
                    </template>
                    <span v-else class="text-xs text-muted-foreground">—</span>
                  </div>
                </td>

                <td class="px-6 py-4 text-right">
                  <div class="flex justify-end gap-1">
                    <Button size="icon" variant="ghost" class="h-8 w-8" @click="openEdit(emp)" :disabled="store.loading">
                      <Pencil class="w-3.5 h-3.5" />
                    </Button>
                    <Button size="icon" variant="ghost" class="h-8 w-8 text-destructive" @click="handleDelete(emp.id)" :disabled="store.loading || deletingId === emp.id">
                      <Loader2 v-if="deletingId === emp.id" class="w-3.5 h-3.5 animate-spin" />
                      <Trash2 v-else class="w-3.5 h-3.5" />
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- Dialog создания/редактирования -->
    <Dialog v-model="dialogOpen">
      <div class="space-y-4 mt-2 max-h-[80vh] overflow-y-auto pr-1">
        <h2 class="text-lg font-semibold">{{ editing ? 'Редактировать сотрудника' : 'Новый сотрудник' }}</h2>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <Label>Табельный номер *</Label>
            <Input v-model="form.employee_code" :disabled="!!editing" :class="fe.employee_code ? 'border-destructive' : ''" />
            <p v-if="fe.employee_code" class="text-xs text-destructive mt-1">{{ fe.employee_code }}</p>
          </div>
          <div>
            <Label>Email</Label>
            <Input v-model="form.email" type="email" :class="fe.email ? 'border-destructive' : ''" />
            <p v-if="fe.email" class="text-xs text-destructive mt-1">{{ fe.email }}</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <Label>Фамилия *</Label>
            <Input v-model="form.last_name" :class="fe.last_name ? 'border-destructive' : ''" />
            <p v-if="fe.last_name" class="text-xs text-destructive mt-1">{{ fe.last_name }}</p>
          </div>
          <div>
            <Label>Имя *</Label>
            <Input v-model="form.first_name" :class="fe.first_name ? 'border-destructive' : ''" />
            <p v-if="fe.first_name" class="text-xs text-destructive mt-1">{{ fe.first_name }}</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <Label>Отчество</Label>
            <Input v-model="form.middle_name" :class="fe.middle_name ? 'border-destructive' : ''" />
            <p v-if="fe.middle_name" class="text-xs text-destructive mt-1">{{ fe.middle_name }}</p>
          </div>
          <div>
            <Label>Полное ФИО *</Label>
            <Input v-model="form.full_name" :class="fe.full_name ? 'border-destructive' : ''" />
            <p v-if="fe.full_name" class="text-xs text-destructive mt-1">{{ fe.full_name }}</p>
          </div>
        </div>

        <div>
          <Label>Компания *</Label>
          <select v-model="form.company_id" class="w-full border border-border bg-background rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 disabled:opacity-50" :class="fe.company_id ? 'border-destructive' : ''" :disabled="companiesLoading">
            <option value="" disabled>{{ companiesLoading ? 'Загрузка...' : 'Выберите компанию' }}</option>
            <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <p v-if="fe.company_id" class="text-xs text-destructive mt-1">{{ fe.company_id }}</p>
        </div>

        <Button @click="handleSave" :disabled="isFormInvalid || store.loading" class="w-full gap-2">
          <Loader2 v-if="store.loading" class="w-4 h-4 animate-spin" />
          {{ editing ? 'Сохранить' : 'Создать' }}
        </Button>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { Users, Plus, Pencil, Trash2, Search, Loader2 } from "lucide-vue-next";
import { useEmployeeStore } from "@/stores/useEmployeeStore";
import { toast } from "@/composables/use-toast";

import Button from "@/components/ui/button.vue";
import Input from "@/components/ui/input.vue";
import Dialog from "@/components/ui/dialog.vue";
import Label from "@/components/ui/label.vue";
import PageHeader from "@/components/ui/PageHeader.vue";
import EmptyState from "@/components/ui/EmptyState.vue";

const store = useEmployeeStore();

// State
const employees = ref<any[]>([]);
const companies = ref<any[]>([]);
const companiesLoading = ref(false);
const loading = ref(true);
const search = ref("");
const deletingId = ref<number | null>(null);

const dialogOpen = ref(false);
const editing = ref<any>(null);
const emptyForm = { employee_code: "", last_name: "", first_name: "", middle_name: "", full_name: "", email: "", company_id: "" };
const form = ref({ ...emptyForm });

const fe = computed(() => store.fieldErrors);

const isFormInvalid = computed(() =>
  !form.value.full_name?.trim() ||
  !form.value.employee_code?.trim() ||
  !form.value.last_name?.trim() ||
  !form.value.first_name?.trim() ||
  !form.value.company_id ||
  (form.value.email && !/^\S+@\S+\.\S+$/.test(form.value.email))
);

// Loaders
const load = async () => {
  loading.value = true;
  try {
    const rawEmployees = await store.read_employees();
    employees.value = rawEmployees.map((emp: any) => ({
      ...emp,
      company_name: emp.company?.name || null,
      training_groups: []
    }));
    await loadTrainingGroups();
    if (!companies.value.length) extractCompaniesFromEmployees();
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const loadCompanies = async () => {
  if (companies.value.length) return;
  companiesLoading.value = true;
  try {
    companies.value = await store.read_companies();
  } catch {
    extractCompaniesFromEmployees();
  } finally {
    companiesLoading.value = false;
  }
};

const extractCompaniesFromEmployees = () => {
  const map = new Map();
  employees.value.forEach((emp: any) => {
    if (emp.company && !map.has(emp.company.id)) map.set(emp.company.id, { id: emp.company.id, name: emp.company.name });
  });
  companies.value = Array.from(map.values());
};

const loadTrainingGroups = async () => {
  try {
    const rawGroups = await store.read_training_groups();
    // Гарантируем, что groups — это массив
    const groups = Array.isArray(rawGroups) ? rawGroups : [];

    // Явная типизация словаря
    const groupsByEmployee: Record<number, Array<{ id: number; name: string; status: string }>> = {};

    for (const group of groups) {
      const participants = group.participants as Array<{ employee_id?: number }> | undefined;
      if (!participants?.length) continue;

      const groupName = group.course?.name || group.course_name || 'Без названия';
      const status = group.status || '';

      for (const p of participants) {
        const empId = p.employee_id;
        if (empId == null) continue; // Пропускаем, если ID нет

        if (!groupsByEmployee[empId]) {
          groupsByEmployee[empId] = [];
        }
        groupsByEmployee[empId].push({ id: group.id, name: groupName, status });
      }
    }

    // Привязываем к сотрудникам
    employees.value.forEach((emp: any) => {
      emp.training_groups = groupsByEmployee[emp.id] || [];
    });
  } catch (e) {
    console.warn('Не удалось загрузить учебные группы:', e);
  }
};

onMounted(async () => {
  await Promise.all([load(), loadCompanies()]);
});

// Computed
const filtered = computed(() => {
  const q = search.value.toLowerCase().trim();
  if (!q) return employees.value;
  return employees.value.filter((emp: any) =>
    (emp.full_name || '').toLowerCase().includes(q) ||
    (emp.email || '').toLowerCase().includes(q) ||
    (emp.company_name || '').toLowerCase().includes(q) ||
    emp.training_groups?.some((g: any) => g.name.toLowerCase().includes(q))
  );
});

// Actions
const openCreate = () => {
  editing.value = null;
  form.value = { ...emptyForm };
  store.clearErrors();
  dialogOpen.value = true;
};

const openEdit = (emp: any) => {
  editing.value = emp;
  form.value = {
    employee_code: emp.employee_code || "",
    last_name: emp.last_name || "",
    first_name: emp.first_name || "",
    middle_name: emp.middle_name || "",
    full_name: emp.full_name || "",
    email: emp.email || "",
    company_id: emp.company_id || "",
  };
  store.clearErrors();
  dialogOpen.value = true;
};

const handleSave = async () => {
  try {
    const payload = { ...form.value };
    if (editing.value) {
      await store.update_employee(editing.value.id, payload);
      toast({ title: "Сотрудник обновлён" });
    } else {
      await store.create_employee(payload);
      toast({ title: "Сотрудник создан" });
    }
    dialogOpen.value = false;
    await load();
  } catch (e: any) {
    console.log('Save error:', e.response?.data);
  }
};

const handleDelete = async (id: number) => {
  const emp = employees.value.find(e => e.id === id);
  if (!confirm(`Удалить "${emp?.full_name || 'сотрудника'}"?`)) return;

  deletingId.value = id;
  try {
    await store.delete_employee(id);
    toast({ title: "Сотрудник удалён" });
    employees.value = employees.value.filter(e => e.id !== id);
  } catch (e: any) {
    console.log('Delete error:', e.response?.data);
  } finally {
    deletingId.value = null;
  }
};

const getStatusLabel = (status: string) => ({ planned: 'Планируется', in_progress: 'В процессе', completed: 'Завершено', cancelled: 'Отменено' }[status] || status);
const getStatusColor = (status: string) => ({ planned: 'bg-blue-500', in_progress: 'bg-amber-500', completed: 'bg-green-500', cancelled: 'bg-gray-400' }[status] || 'bg-gray-400');
</script>