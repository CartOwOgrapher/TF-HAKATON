<template>
  <Dialog v-model="dialogOpen">
    <div class="sm:max-w-lg max-h-[80vh] flex flex-col">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Выбрать сотрудников</h2>
        <Button variant="ghost" size="icon" class="h-8 w-8" @click="dialogOpen = false">
          <X class="w-4 h-4" />
        </Button>
      </div>

      <!-- Поиск -->
      <div class="relative mb-3">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
        <Input 
          v-model="search" 
          placeholder="Поиск по ФИО, компании, email..." 
          class="pl-10" 
          :disabled="loading"
        />
      </div>

      <!-- Индикатор загрузки -->
      <div v-if="loading" class="flex items-center justify-center py-8">
        <Loader2 class="w-6 h-6 animate-spin text-muted-foreground" />
      </div>

      <!-- Список сотрудников -->
      <div v-else class="flex-1 overflow-y-auto divide-y divide-border border rounded-lg">
        <div v-if="filtered.length === 0" class="p-6 text-center text-sm text-muted-foreground">
          {{ search ? 'Ничего не найдено' : 'Нет доступных сотрудников' }}
        </div>
        
        <label
          v-for="emp in filtered"
          :key="emp.id"
          class="flex items-center gap-3 px-4 py-3 hover:bg-muted/50 cursor-pointer transition-colors"
        >
          <input 
            type="checkbox" 
            :checked="selected.includes(emp.id)" 
            @change="toggleSelect(emp.id)"
            class="w-4 h-4 rounded border-gray-300"
          />
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate">{{ emp.full_name }}</p>
            <p v-if="emp.email" class="text-xs text-muted-foreground truncate">{{ emp.email }}</p>
            <p v-if="emp.company_name" class="text-xs text-muted-foreground truncate">{{ emp.company_name }}</p>
          </div>
        </label>
      </div>

      <!-- Footer -->
      <div class="flex gap-2 mt-4 pt-4 border-t">
        <Button variant="outline" class="flex-1" @click="dialogOpen = false">
          Отмена
        </Button>
        <Button 
          class="flex-1 gap-2" 
          :disabled="selected.length === 0 || adding"
          @click="submit"
        >
          <Loader2 v-if="adding" class="w-4 h-4 animate-spin" />
          <UserPlus v-else class="w-4 h-4" />
          Добавить ({{ selected.length }})
        </Button>
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import apiClient from '@/api/axios'  // ✅ Используем apiClient для /employees/list
import { Search, UserPlus, X, Loader2 } from 'lucide-vue-next'
import Dialog from '@/components/ui/dialog.vue'
import Input from '@/components/ui/input.vue'
import Button from '@/components/ui/button.vue'

const props = defineProps({
  open: Boolean,
  existingIds: { type: Array, default: () => [] }, // ✅ employee_id, которые уже в группе
})

const emit = defineEmits(['update:open', 'select'])

const employees = ref([])  // ✅ Загружаем Employee, не Participant
const selected = ref([])
const search = ref('')
const loading = ref(false)
const adding = ref(false)

const dialogOpen = computed({
  get: () => props.open,
  set: (val) => emit('update:open', val),
})

// ✅ Загружаем сотрудников из правильного эндпоинта
watch(dialogOpen, async (isOpen) => {
  if (!isOpen) return
  
  loading.value = true
  try {
    // Бэкенд возвращает { employees: [...] }
    const { data } = await apiClient.get('/employees/list')
    employees.value = data.employees || []
  } catch (e) {
    console.error('Ошибка загрузки сотрудников:', e)
    employees.value = []
    // Можно показать тост, но чтобы не спамить — молча пустой список
  } finally {
    loading.value = false
  }
  
  selected.value = []
  search.value = ''
})

// Исключаем уже добавленных в группу (по employee_id)
const available = computed(() =>
  employees.value.filter((emp) => !props.existingIds.includes(emp.id))
)

// Поиск по полям Employee
const filtered = computed(() => {
  const s = search.value.toLowerCase().trim()
  if (!s) return available.value
  
  return available.value.filter(
    (emp) =>
      emp.full_name?.toLowerCase().includes(s) ||
      emp.email?.toLowerCase().includes(s) ||
      emp.company_name?.toLowerCase().includes(s)
  )
})

function toggleSelect(id) {
  const idx = selected.value.indexOf(id)
  if (idx === -1) {
    selected.value.push(id)
  } else {
    selected.value.splice(idx, 1)
  }
}

async function submit() {
  if (selected.value.length === 0) return
  
  adding.value = true
  try {
    // ✅ Эмитим массив employee_id, родитель создаст из них GroupParticipant
    emit('select', [...selected.value])
    dialogOpen.value = false
  } finally {
    adding.value = false
  }
}
</script>