<template>
  <div>
    <PageHeader title="Компании" description="Справочник компаний-заказчиков">
      <Button @click="openCreate" class="gap-2" :disabled="store.loading">
        <Plus class="w-4 h-4" /> Добавить компанию
      </Button>
    </PageHeader>

    <div class="relative mb-6 max-w-sm">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
      <Input placeholder="Поиск..." v-model="search" class="pl-10" />
    </div>

    <div v-if="loading" class="flex items-center justify-center h-96">
      <div class="w-8 h-8 border-4 border-muted border-t-primary rounded-full animate-spin" />
    </div>

    <template v-else>
      <EmptyState
        v-if="filtered.length === 0"
        :icon="Building2"
        title="Нет компаний"
        description="Добавьте первую компанию"
      >
        <Button @click="openCreate" variant="outline" class="gap-2">
          <Plus class="w-4 h-4" /> Добавить
        </Button>
      </EmptyState>

      <div v-else class="bg-card rounded-2xl border border-border overflow-hidden">
        <table class="w-full">
          <thead>
            <tr class="border-b border-border bg-muted/50">
              <th class="px-6 py-3 text-left text-xs uppercase">Код</th>
              <th class="px-6 py-3 text-left text-xs uppercase">Название</th>
              <th class="px-6 py-3 text-right text-xs uppercase">Действия</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border">
            <tr
              v-for="company in filtered"
              :key="company.id"
              class="hover:bg-muted/30 transition-colors"
            >
              <td class="px-6 py-4 text-sm font-mono">{{ company.code }}</td>
              <td class="px-6 py-4 font-medium">{{ company.name }}</td>
              <td class="px-6 py-4 text-right">
                <Button size="icon" variant="ghost" class="h-8 w-8" @click="openEdit(company)">
                  <Pencil class="w-3.5 h-3.5" />
                </Button>
                <Button
                  size="icon"
                  variant="ghost"
                  class="h-8 w-8 text-destructive"
                  @click="handleDelete(company.id)"
                  :disabled="store.loading"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </Button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <Dialog v-model="dialogOpen">
      <h2 class="text-lg font-semibold mb-4">
        {{ editing ? 'Редактировать компанию' : 'Новая компания' }}
      </h2>

      <div class="space-y-4">
        <div>
          <Label>Код компании *</Label>
          <Input 
            v-model="form.code" 
            placeholder="Например: ООО-001" 
            :class="fe.code ? 'border-destructive' : ''"
            :disabled="store.loading"
          />
          <p v-if="fe.code" class="text-xs text-destructive mt-1">{{ fe.code }}</p>
        </div>

        <div>
          <Label>Название *</Label>
          <Input 
            v-model="form.name" 
            placeholder='ООО "Ромашка"' 
            :class="fe.name ? 'border-destructive' : ''"
            :disabled="store.loading"
          />
          <p v-if="fe.name" class="text-xs text-destructive mt-1">{{ fe.name }}</p>
        </div>

        <Button
          @click="handleSave"
          :disabled="isFormInvalid || store.loading"
          class="w-full gap-2"
        >
          <Loader2 v-if="store.loading" class="w-4 h-4 animate-spin" />
          {{ editing ? 'Сохранить' : 'Создать' }}
        </Button>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Building2, Plus, Pencil, Trash2, Search, Loader2 } from 'lucide-vue-next'
import { useCompanyStore, type Company } from '@/stores/useCompanyStore'
import { toast } from '@/composables/use-toast'

import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Dialog from '@/components/ui/dialog.vue'
import Label from '@/components/ui/label.vue'
import PageHeader from '@/components/ui/PageHeader.vue'
import EmptyState from '@/components/ui/EmptyState.vue'

const store = useCompanyStore()

// State
const companies = ref<Company[]>([])
const loading = ref(true)
const search = ref('')
const dialogOpen = ref(false)
const editing = ref<Company | null>(null)
const form = ref({ code: '', name: '' })

const fe = computed(() => store.fieldErrors)

const isFormInvalid = computed(() => 
  !form.value.code?.trim() || !form.value.name?.trim()
)

// Load
const load = async () => {
  loading.value = true
  try {
    companies.value = await store.read_companies()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(load)

// Computed
const filtered = computed(() =>
  companies.value.filter(
    (c) =>
      c.name?.toLowerCase().includes(search.value.toLowerCase()) ||
      c.code?.toLowerCase().includes(search.value.toLowerCase())
  )
)

// Dialog helpers
const openCreate = () => {
  editing.value = null
  form.value = { code: '', name: '' }
  store.clearErrors()
  dialogOpen.value = true
}

const openEdit = (company: Company) => {
  editing.value = company
  form.value = { code: company.code, name: company.name }
  store.clearErrors()
  dialogOpen.value = true
}

// Save
const handleSave = async () => {
  try {
    const payload = { 
      code: form.value.code.trim(), 
      name: form.value.name.trim() 
    }
    
    if (editing.value) {
      await store.update_company(editing.value.id, payload)
      toast({ title: 'Компания обновлена' })
    } else {
      await store.create_company(payload)
      toast({ title: 'Компания создана' })
    }
    
    dialogOpen.value = false
    await load()
  } catch (e: any) {
    console.log('Save error:', e.response?.data)
  }
}

// Delete
const handleDelete = async (id: number) => {
  const company = companies.value.find(c => c.id === id)
  if (!confirm(`Удалить "${company?.name}"?`)) return

  try {
    await store.delete_company(id)
    toast({ title: 'Компания удалена' })
    companies.value = companies.value.filter(c => c.id !== id)
  } catch (e: any) {
    console.log('Delete error:', e.response?.data)
  }
}
</script>