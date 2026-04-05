<template>
  <!-- OVERLAY -->
  <div 
    v-if="open" 
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity"
    @click.self="close"
  >
    <!-- MODAL -->
    <div class="bg-card w-full max-w-md rounded-2xl border border-border p-6 shadow-xl m-4">
      <div class="flex items-center justify-between mb-5">
        <h2 class="text-xl font-semibold">
          {{ isEditing ? 'Редактировать группу' : 'Создать группу' }}
        </h2>
        <button @click="close" class="text-muted-foreground hover:text-foreground transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <!-- COURSE -->
        <div class="space-y-2">
          <label class="text-sm font-medium">Курс</label>
          <select 
            v-model="form.course_id" 
            class="w-full border border-border bg-background rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 disabled:opacity-50"
            :disabled="loadingCourses || submitting"
          >
            <option value="" disabled>Выберите курс</option>
            <option v-for="c in courses" :key="c.id" :value="c.id">
              {{ c.title }} — {{ formatPrice(getActivePrice(c)) }} ₽
            </option>
          </select>
          <p v-if="errors.course_id" class="text-xs text-destructive mt-1">{{ errors.course_id[0] }}</p>
        </div>

        <!-- DATES -->
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-2">
            <label class="text-sm font-medium">Дата начала</label>
            <input 
              v-model="form.start_date" 
              type="date" 
              class="w-full border border-border bg-background rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 disabled:opacity-50"
              :disabled="submitting"
            />
            <p v-if="errors.start_date" class="text-xs text-destructive mt-1">{{ errors.start_date[0] }}</p>
          </div>
          <div class="space-y-2">
            <label class="text-sm font-medium">Дата окончания</label>
            <input 
              v-model="form.end_date" 
              type="date" 
              class="w-full border border-border bg-background rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 disabled:opacity-50"
              :disabled="submitting"
            />
            <p v-if="errors.end_date" class="text-xs text-destructive mt-1">{{ errors.end_date[0] }}</p>
          </div>
        </div>

        <!-- STATUS (Enum values instead of labels) -->
        <div class="space-y-2">
          <label class="text-sm font-medium">Статус</label>
          <select 
            v-model="form.status" 
            class="w-full border border-border bg-background rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 disabled:opacity-50"
            :disabled="submitting"
          >
            <option value="planned">Планируется</option>
            <option value="in_progress">В процессе</option>
            <option value="completed">Завершено</option>
            <option value="cancelled">Отменено</option>
          </select>
          <p v-if="errors.status" class="text-xs text-destructive mt-1">{{ errors.status[0] }}</p>
        </div>

        <!-- ACTIONS -->
        <div class="flex gap-3 pt-2">
          <button 
            type="button" 
            class="flex-1 px-4 py-2 rounded-lg border border-border bg-background hover:bg-muted transition-colors text-sm font-medium disabled:opacity-50"
            :disabled="submitting"
            @click="close"
          >
            Отмена
          </button>
          <button 
            type="submit" 
            class="flex-1 px-4 py-2 rounded-lg bg-primary text-primary-foreground hover:bg-primary/90 transition-colors text-sm font-medium disabled:opacity-50 flex items-center justify-center gap-2"
            :disabled="submitting || !form.course_id"
          >
            <svg v-if="submitting" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
            {{ submitting ? 'Сохранение...' : 'Сохранить' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import apiClient from '@/api/axios'
import { toast } from '@/composables/use-toast'

const props = defineProps({
  open: Boolean,
  editingGroup: Object
})

const emit = defineEmits(['update:open', 'saved'])

const courses = ref([])
const loadingCourses = ref(false)
const submitting = ref(false)
const errors = ref({})

const isEditing = computed(() => !!props.editingGroup)

const form = reactive({
  course_id: '',
  start_date: '',
  end_date: '',
  status: 'planned' 
})

function close() {
  emit('update:open', false)
}

function formatPrice(n) {
  return Number(n || 0).toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}


function getActivePrice(course) {
  if (!course?.price) return 0
  if (Array.isArray(course.price)) {
    
    const found = course.price.find(p => p.price != null) || course.price[0]
    return found?.price || 0
  }
  return course.price
}

async function loadCourses() {
  loadingCourses.value = true
  try {
    
    const { data } = await apiClient.get('/courses/list')
    
    courses.value = data?.courses || data?.data?.courses || data?.data || data || []
  } catch (e) {
    console.error('Ошибка загрузки курсов:', e)
    toast({ title: 'Не удалось загрузить список курсов', variant: 'destructive' })
  } finally {
    loadingCourses.value = false
  }
}

watch(
  () => props.open,
  async (isOpen) => {
    if (!isOpen) return

    errors.value = {}
    
    if (isEditing.value && props.editingGroup) {
      form.course_id = props.editingGroup.course_id || ''
      
      form.start_date = props.editingGroup.start_date ? String(props.editingGroup.start_date).split('T')[0] : ''
      form.end_date = props.editingGroup.end_date ? String(props.editingGroup.end_date).split('T')[0] : ''
      
      form.status = props.editingGroup.status || 'planned'
    } else {
      form.course_id = ''
      form.start_date = ''
      form.end_date = ''
      form.status = 'planned'
    }

    if (!courses.value.length) {
      await loadCourses()
    }
  }
)

function calcEndDate(startDate, durationDays) {
  if (!startDate || !durationDays) return ''
  const date = new Date(startDate)
  date.setDate(date.getDate() + Number(durationDays))
  return date.toISOString().split('T')[0]
}


const selectedCourse = computed(() => 
  courses.value.find(c => c.id === form.course_id || c.id === Number(form.course_id))
)


watch(() => form.start_date, (newDate) => {
  if (newDate && selectedCourse.value?.duration_days) {
    form.end_date = calcEndDate(newDate, selectedCourse.value.duration_days)
  }
})


watch(() => form.course_id, () => {
  if (form.start_date && selectedCourse.value?.duration_days) {
    form.end_date = calcEndDate(form.start_date, selectedCourse.value.duration_days)
  }
})

async function submit() {
  submitting.value = true
  errors.value = {}

  try {
    const payload = { ...form }
    
    if (isEditing.value && props.editingGroup?.id) {
      await apiClient.put(`/training-groups/${props.editingGroup.id}`, payload)
      toast({ title: 'Группа успешно обновлена' })
    } else {
      await apiClient.post('/training-groups', payload)
      toast({ title: 'Группа успешно создана' })
    }

    emit('saved')
    close()
  } catch (e) {
    if (e.response?.status === 422) {
      
      errors.value = e.response.data.errors || {}
      toast({ title: 'Проверьте правильность заполнения', variant: 'destructive' })
    } else {
      console.error('Ошибка сохранения группы:', e)
      toast({ 
        title: 'Ошибка сохранения', 
        description: e.response?.data?.message || e.message,
        variant: 'destructive' 
      })
    }
  } finally {
    submitting.value = false
  }
}
</script>