<template>
  <div>
    <PageHeader title="Учебные группы" description="Управление группами обучения">
      <Button class="gap-2" @click="dialogOpen = true" :disabled="store.loading">
        <Plus class="w-4 h-4" /> Создать группу
      </Button>
    </PageHeader>

    <!-- Search -->
    <div class="relative mb-6 max-w-sm">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground"/>
      <Input v-model="search" placeholder="Поиск по курсу, статусу..." class="pl-10"/>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center h-96">
      <div class="w-8 h-8 border-4 border-muted border-t-primary rounded-full animate-spin"/>
    </div>

    <!-- Empty -->
    <EmptyState v-else-if="filtered.length === 0"
                :icon="UsersRound"
                title="Нет групп"
                description="Создайте первую учебную группу">
      <Button variant="outline" class="gap-2" @click="dialogOpen = true" :disabled="store.loading">
        <Plus class="w-4 h-4"/> Создать группу
      </Button>
    </EmptyState>

    <!-- Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div v-for="g in filtered" :key="g.id"
           class="bg-card rounded-2xl border border-border p-6 hover:shadow-md transition-shadow">
        <!-- Header -->
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="font-semibold text-lg">
              {{ g.course?.name || g.course_name || 'Курс не указан' }}
            </h3>
            <p class="text-sm text-muted-foreground mt-1">
              {{ g.start_date || '—' }} — {{ g.end_date || '—' }}
            </p>
          </div>
          <StatusBadge :status="g.status_label || 'planned'" :label="g.status_label"/>
        </div>

        <!-- Progress -->
        <ProgressBar :value="g.average_progress || 0" class="mb-4"/>

        <!-- Stats -->
        <div class="grid grid-cols-2 gap-3 mb-4">
          <div class="bg-muted/50 rounded-xl p-3 text-center">
            <p class="text-xs text-muted-foreground">Участники</p>
            <p class="text-lg font-semibold">{{ g.participants_count || 0 }}</p>
          </div>
          <div class="bg-muted/50 rounded-xl p-3 text-center">
            <p class="text-xs text-muted-foreground">Стоимость</p>
            <p class="text-lg font-semibold">{{ formatPrice(g.group_cost) }} ₽</p>
          </div>
        </div>

        <!-- Button -->
        <router-link :to="`/groups/${g.id}`">
          <Button variant="outline" class="w-full gap-2">
            <Eye class="w-4 h-4"/> Подробнее
          </Button>
        </router-link>
      </div>
    </div>

    <!-- Dialog -->
    <GroupFormDialog v-model:open="dialogOpen" @saved="load"/>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { UsersRound, Plus, Eye, Search } from 'lucide-vue-next'
import { useTrainingGroupStore, type TrainingGroup } from '@/stores/useTrainingGroupStore'
import PageHeader from '@/components/ui/PageHeader.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import ProgressBar from '@/components/ui/ProgressBar.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import GroupFormDialog from '@/components/groups/GroupFormDialog.vue'

const store = useTrainingGroupStore()

const groups = ref<TrainingGroup[]>([])
const loading = ref(true)
const search = ref('')
const dialogOpen = ref(false)

const load = async () => {
  loading.value = true
  try {
    groups.value = await store.read_groups()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(load)

const filtered = computed(() => {
  const q = search.value.toLowerCase().trim()
  if (!q) return groups.value
  return groups.value.filter(g =>
    (g.course?.name || g.course_name || '').toLowerCase().includes(q) ||
    (g.status_label || '').toLowerCase().includes(q)
  )
})

const formatPrice = (v: number | string) => Number(v || 0).toLocaleString('ru-RU')
</script>