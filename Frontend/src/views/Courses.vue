<template>
  <div>
    <PageHeader title="Курсы обучения" description="Управление каталогом курсов">
      <Button @click="openCreate" class="gap-2">
        <Plus class="w-4 h-4" /> Создать курс
      </Button>
    </PageHeader>

    <div class="relative mb-6 max-w-sm">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
      <Input v-model="search" placeholder="Поиск по названию..." class="pl-10" />
    </div>

    <div v-if="loading" class="flex items-center justify-center h-96">
      <div class="w-8 h-8 border-4 border-muted border-t-primary rounded-full animate-spin" />
    </div>

    <template v-else>
      <EmptyState
        v-if="filtered.length === 0"
        :icon="BookOpen"
        title="Нет курсов"
        description="Создайте первый курс для начала работы"
      >
        <Button @click="openCreate" variant="outline" class="gap-2">
          <Plus class="w-4 h-4" /> Создать курс
        </Button>
      </EmptyState>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="course in filtered"
          :key="course.id"
          class="bg-card rounded-2xl border border-border p-6 hover:shadow-md transition-shadow"
        >
          <div class="flex items-start justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
              <BookOpen class="w-5 h-5 text-primary" />
            </div>
            <div class="flex gap-1">
              <Button size="icon" variant="ghost" class="h-8 w-8" @click="openEdit(course)">
                <Pencil class="w-3.5 h-3.5" />
              </Button>
              <Button size="icon" variant="ghost" class="h-8 w-8" @click="openPrices(course)">
                <Tag class="w-3.5 h-3.5" />
              </Button>
              <Button size="icon" variant="ghost" class="h-8 w-8 text-destructive" @click="handleDelete(course.id)">
                <Trash2 class="w-3.5 h-3.5" />
              </Button>
            </div>
          </div>

          <p class="text-xs text-muted-foreground mb-1">{{ course.code }}</p>
          <h3 class="font-semibold text-lg mb-1">{{ course.title }}</h3>
          <p v-if="course.description" class="text-sm text-muted-foreground line-clamp-2 mb-4">
            {{ course.description }}
          </p>

          <div class="flex items-center justify-between pt-4 border-t border-border">
            <div>
              <p class="text-xs text-muted-foreground">Длительность</p>
              <p class="text-sm font-medium">{{ course.duration_days }} дн.</p>
            </div>
            <div class="text-right">
              <p class="text-xs text-muted-foreground">Актуальная цена</p>
              <p class="text-sm font-semibold text-primary">
                {{ course.price
                    ? Number(course.price).toLocaleString("ru-RU") + " ₽"
                    : "Не указана" }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Диалог создания/редактирования курса -->
    <Dialog v-model="dialogOpen">
      <div class="space-y-4 mt-2">
        <h2 class="text-lg font-semibold">
          {{ editing ? "Редактировать курс" : "Новый курс" }}
        </h2>

        <div>
          <Label>Код курса *</Label>
          <Input
            v-model="form.code"
            placeholder="Например: OT-01-2024"
          />
        </div>

        <div>
          <Label>Название курса *</Label>
          <Input
            v-model="form.title"
            placeholder="Например: Охрана труда"
          />
        </div>

        <div>
          <Label>Описание</Label>
          <Textarea
            v-model="form.description"
            rows="3"
          />
        </div>

        <div>
          <Label>Длительность (дни) *</Label>
          <Input
            type="number"
            v-model="form.duration_days"
          />
        </div>

        <div class="border-t border-border pt-4">
          <p class="text-sm font-medium text-muted-foreground mb-3">
            {{ editing ? "Цена за человека" : "Начальная цена *" }}
          </p>
          <div>
            <Input
              type="number"
              v-model="form.price"
              placeholder="0"
            />
            <p v-if="editing" class="text-xs text-muted-foreground mt-1">
              Оставьте прежнее значение если цена не изменилась
            </p>
          </div>
        </div>

        <Button @click="handleSave" :disabled="isFormInvalid" class="w-full">
          {{ editing ? "Сохранить" : "Создать" }}
        </Button>
      </div>
    </Dialog>

    <!-- Диалог истории цен -->
    <Dialog v-model="pricesDialogOpen">
      <div class="space-y-4 mt-2">
        <h2 class="text-lg font-semibold">История цен: {{ selectedCourse?.title }}</h2>

        <div v-if="pricesLoading" class="flex justify-center py-4">
          <div class="w-6 h-6 border-4 border-muted border-t-primary rounded-full animate-spin" />
        </div>

        <div v-else>
          <div v-if="prices.length === 0" class="text-sm text-muted-foreground text-center py-4">
            Цены ещё не добавлены
          </div>
          <div v-else class="space-y-2 max-h-96 overflow-y-auto">
            <div
              v-for="price in prices"
              :key="price.id"
              class="flex items-center justify-between rounded-lg border border-border px-3 py-2 text-sm"
            >
              <span class="font-medium text-primary">
                {{ Number(price.price).toLocaleString("ru-RU") }} ₽
              </span>
              <span class="text-muted-foreground text-xs">
                с {{ price.valid_from }}
                <template v-if="price.valid_to"> по {{ price.valid_to }}</template>
                <template v-else> (актуальная)</template>
              </span>
            </div>
          </div>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { BookOpen, Plus, Pencil, Trash2, Search, Tag } from "lucide-vue-next";

import Button from "@/components/ui/button.vue";
import Input from "@/components/ui/input.vue";
import Dialog from "@/components/ui/dialog.vue";
import Label from "@/components/ui/label.vue";
import Textarea from "@/components/ui/textarea.vue";
import PageHeader from "@/components/ui/PageHeader.vue";
import EmptyState from "@/components/ui/EmptyState.vue";

import { toast } from "@/composables/use-toast";
import { useCourseStore } from "@/stores/useCourseStore";

const courseStore = useCourseStore();

const emptyCourse = { code: "", title: "", description: "", duration_days: "", price: "" };

const courses = ref([]);
const loading = ref(true);
const search = ref("");

const dialogOpen = ref(false);
const editing = ref(null);
const form = ref({ ...emptyCourse });

const pricesDialogOpen = ref(false);
const selectedCourse = ref(null);
const prices = ref([]);
const pricesLoading = ref(false);

const isFormInvalid = computed(() =>
  !form.value.title?.trim() ||
  !form.value.code?.trim() ||
  !form.value.duration_days ||
  Number(form.value.duration_days) <= 0 ||
  (!editing.value && (!form.value.price || Number(form.value.price) <= 0))
);

const load = async () => {
  loading.value = true;
  try {
    courses.value = await courseStore.read_courses();
  } catch (e) {
    toast({ title: 'Ошибка загрузки курсов', description: e.response?.data?.message || e.message });
  } finally {
    loading.value = false;
  }
};

onMounted(load);

const filtered = computed(() =>
  courses.value.filter((c) =>
    c.title?.toLowerCase().includes(search.value.toLowerCase())
  )
);

const openCreate = () => {
  editing.value = null;
  form.value = { ...emptyCourse };
  courseStore.fieldErrors = {};
  dialogOpen.value = true;
};

const openEdit = (course) => {
  editing.value = course;
  form.value = {
    code: course.code || "",
    title: course.title || "",
    description: course.description || "",
    duration_days: course.duration_days || "",
    price: course.price || "",

  };
  courseStore.fieldErrors = {};
  dialogOpen.value = true;
};

const handleSave = async () => {
  loading.value = true;
  try {
    const courseData = {
      code: form.value.code,
      title: form.value.title,
      price: Number(form.value.price).toFixed(2),
      description: form.value.description?.trim() || null,
      duration_days: Number(form.value.duration_days),
    };

    if (editing.value) {
      await courseStore.update_course(editing.value.id, courseData);
    } else {
      await courseStore.create_course(courseData);
    }

    dialogOpen.value = false;
    await load();
  } catch (e) {
    const fieldErrs = courseStore.fieldErrors
     const description = Object.keys(fieldErrs).length
       ? Object.values(fieldErrs).join(' · ')
     : e.response?.data?.message || e.message
     toast({ title: 'Ошибка сохранения курса', description, variant: 'destructive' })
  } finally {
    loading.value = false;
  }
};

const handleDelete = async (id) => {
  try {
    await courseStore.delete_course(id);
    toast({ title: "Курс удалён" });
    await load();
  } catch (e) {
    toast({ title: 'Ошибка удаления курса', description: e.response?.data?.message || e.message, variant: 'destructive' })  }
};

const openPrices = async (course) => {
  selectedCourse.value = course;
  pricesDialogOpen.value = true;
  await loadPrices(course.id);
};

const loadPrices = async (courseId) => {
  pricesLoading.value = true;
  try {
    const result = await courseStore.read_prices(courseId);
    prices.value = Array.isArray(result) ? result : [];
  } catch (e) {
    toast({ title: 'Ошибка загрузки истории цен', description: e.response?.data?.message || e.message, variant: 'destructive' });
  } finally {
    pricesLoading.value = false;
  }
};
</script>