<template>
  <div>
    <!-- Header -->
    <div class="flex flex-wrap items-center gap-3 mb-6">
      <router-link to="/groups">
        <Button variant="ghost" size="icon" class="h-9 w-9">
          <ArrowLeft class="w-5 h-5" />
        </Button>
      </router-link>

      <div class="flex-1 min-w-0">
        <h1 class="text-2xl font-bold truncate">{{ group?.course?.name || group?.course_name || 'Группа' }}</h1>
        <p class="text-sm text-muted-foreground">
          {{ group?.start_date || '—' }} — {{ group?.end_date || '—' }}
        </p>
      </div>

      <StatusBadge :status="group?.status_label || 'planned'" :label="group?.status_label" />

      <!-- Действия -->
      <div class="flex items-center gap-2 flex-shrink-0">
        <Button variant="outline" class="gap-2" @click="editDialogOpen = true" :disabled="store.loading || deletingGroup">
          <Pencil class="w-4 h-4" /> 
          <span class="hidden sm:inline">Редактировать</span>
        </Button>

        <Button 
          variant="destructive" 
          class="gap-2" 
          :disabled="store.loading || deletingGroup"
          @click="deleteGroup"
        >
          <Loader2 v-if="deletingGroup" class="w-4 h-4 animate-spin" />
          <Trash2 v-else class="w-4 h-4" />
          {{ deletingGroup ? 'Удаление...' : 'Удалить группу' }}
        </Button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center h-96">
      <div class="w-8 h-8 border-4 border-muted border-t-primary rounded-full animate-spin" />
    </div>

    <!-- Not found -->
    <div v-else-if="!group" class="text-center py-20 text-muted-foreground">
      Группа не найдена
    </div>

    <!-- Content -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- LEFT: участники -->
      <div class="lg:col-span-2">
        <div class="bg-card rounded-2xl border border-border">
          <div class="flex items-center justify-between px-6 py-4 border-b border-border">
            <h2 class="font-semibold">Участники группы ({{ participants.length }})</h2>
            <Button 
              size="sm" 
              class="gap-2" 
              :disabled="store.loading || addingLoading || deletingGroup"
              @click="selectorOpen = true"
            >
              <Loader2 v-if="addingLoading" class="w-4 h-4 animate-spin" />
              <UserPlus v-else class="w-4 h-4" /> 
              {{ addingLoading ? 'Добавление...' : 'Добавить' }}
            </Button>
          </div>

          <div v-if="participants.length === 0" class="p-8 text-center text-muted-foreground text-sm">
            Нет участников в группе
          </div>

          <div v-else class="divide-y divide-border">
            <div 
              v-for="p in participants" 
              :key="p.id" 
              class="px-6 py-4 flex items-center gap-4 transition-opacity"
              :class="{ 'opacity-50 pointer-events-none': store.loading || removingId === p.id }"
            >
              <div class="flex-1 min-w-0">
                <p class="font-medium truncate">{{ p.employee?.full_name || 'Неизвестный' }}</p>
                <p v-if="p.employee?.email" class="text-xs text-muted-foreground">{{ p.employee.email }}</p>
              </div>

              <div class="w-48 relative">
                <Slider
                  :model-value="p.completion_percent || 0"
                  :min="0"
                  :max="100"
                  :step="5"
                  :disabled="store.loading || savingProgressId === p.id"
                  @update:modelValue="(val: number) => handleSliderUpdate(p.id, val)"
                />
                <div v-if="savingProgressId === p.id" class="absolute -top-1.5 -right-1.5">
                  <Loader2 class="w-3.5 h-3.5 animate-spin text-primary bg-background rounded-full" />
                </div>
              </div>

              <span class="text-sm font-medium w-12 text-right tabular-nums">{{ p.completion_percent || 0 }}%</span>

              <!-- Сертификат -->
               
              <div class="flex items-center gap-1" :class="{ 'invisible': p.completion_percent !== 100 }">
                <template v-if="p.certificate_path">
                  <Button
                    size="icon"
                    variant="ghost"
                    class="h-8 w-8 text-green-600"
                    title="Скачать сертификат"
                    :disabled="store.loading || certificateLoadingId === p.id"
                    @click="downloadCertificate(p)"
                  >
                    <FileDown class="w-3.5 h-3.5" />
                  </Button>

                  <Button
                    size="icon"
                    variant="ghost"
                    class="h-8 w-8 text-orange-500"
                    title="Удалить сертификат"
                    :disabled="store.loading || certificateLoadingId === p.id"
                    @click="deleteCertificate(p)"
                  >
                    <Loader2 v-if="certificateLoadingId === p.id" class="w-3.5 h-3.5 animate-spin" />
                    <FileX v-else class="w-3.5 h-3.5" />
                  </Button>
                </template>

                <template v-else>
                  <Button
                    size="icon"
                    variant="ghost"
                    class="h-8 w-8 text-muted-foreground hover:text-primary"
                    title="Загрузить сертификат"
                    :disabled="store.loading || certificateLoadingId === p.id"
                    @click="triggerCertificateUpload(p.id)"
                  >
                    <Loader2 v-if="certificateLoadingId === p.id" class="w-3.5 h-3.5 animate-spin" />
                    <FileUp v-else class="w-3.5 h-3.5" />
                  </Button>
                </template>
              </div>

              <Button 
                size="icon" 
                variant="ghost" 
                class="h-8 w-8 text-destructive" 
                :disabled="store.loading || removingId === p.id || addingLoading || deletingGroup"
                @click="removeParticipant(p.id)"
              >
                <Loader2 v-if="removingId === p.id" class="w-3.5 h-3.5 animate-spin" />
                <Trash2 v-else class="w-3.5 h-3.5" />
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT: прогресс и стоимость -->
      <div class="space-y-6">
        <div class="bg-card rounded-2xl border border-border p-6">
          <h3 class="font-semibold mb-4">Общий прогресс</h3>
          <div class="text-4xl font-bold text-primary mb-2">{{ averageProgress }}%</div>
          <ProgressBar :value="averageProgress" size="lg" :showLabel="false" />
        </div>

        <div class="bg-card rounded-2xl border border-border p-6">
          <h3 class="font-semibold mb-4">Стоимость группы</h3>
          <div class="text-lg">{{ formatPrice(group.group_cost) }} ₽</div>
          <div v-if="group.course?.price" class="text-sm text-muted-foreground">
            за человека: {{ formatPrice(group.price_per_person) }} ₽
          </div>
        </div>
      </div>
    </div>

    <!-- Скрытый input для загрузки PDF -->
    <input
      ref="certFileInput"
      type="file"
      accept="application/pdf"
      class="hidden"
      @change="handleCertificateFileSelected"
    />

    <!-- Dialogs -->
    <GroupFormDialog 
      v-model:open="editDialogOpen" 
      :editingGroup="group" 
      @saved="onGroupSaved"
    />
    <ParticipantSelector
      v-model:open="selectorOpen"
      :existingIds="participants.map(p => p.employee_id)"
      @select="handleAddParticipants"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTrainingGroupStore, type TrainingGroup } from '@/stores/useTrainingGroupStore'
import { trainingGroupService } from '@/api/trainingGroupService'
import { toast } from '@/composables/use-toast'

import { ArrowLeft, Pencil, Trash2, UserPlus, Loader2, FileUp, FileDown, FileX } from 'lucide-vue-next'
import Button from '@/components/ui/button.vue'
import Slider from '@/components/ui/Slider.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import ProgressBar from '@/components/ui/ProgressBar.vue'
import GroupFormDialog from '@/components/groups/GroupFormDialog.vue'
import ParticipantSelector from '@/components/groups/ParticipantSelector.vue'

const store = useTrainingGroupStore()
const route = useRoute()
const router = useRouter()

const group = ref<TrainingGroup | null>(null)
const participants = ref<Array<{
  id: number
  employee_id: number
  employee?: { id: number; full_name: string; email?: string }
  completion_percent: number
  certificate_path?: string | null
}>>([])
const loading = ref(true)
const editDialogOpen = ref(false)
const selectorOpen = ref(false)
const removingId = ref<number | null>(null)
const addingLoading = ref(false)
const savingProgressId = ref<number | null>(null)
const deletingGroup = ref(false)

// Сертификаты
const certificateLoadingId = ref<number | null>(null)
const certUploadTargetId = ref<number | null>(null)
const certFileInput = ref<HTMLInputElement | null>(null)

let sliderDebounceTimer: ReturnType<typeof setTimeout> | null = null

// ── Загрузка данных ─────────────────────────────────────────────────

const loadGroup = async () => {
  const id = Number(route.params.id)
  if (!id) {
    toast({ title: 'ID группы не указан' })
    return
  }

  loading.value = true
  try {
    const data = await store.read_group(id)
    group.value = data
    participants.value = data?.participants || []
  } catch (e: any) {
    console.error('Ошибка загрузки группы:', e)
    if (e.response?.status === 404) {
      toast({ title: 'Группа не найдена' })
      router.push('/groups')
    } else {
      toast({
        title: 'Ошибка загрузки',
        description: e.response?.data?.message || e.message,
      })
    }
    group.value = null
    participants.value = []
  } finally {
    loading.value = false
  }
}

onMounted(loadGroup)

watch(() => route.params.id, (newId, oldId) => {
  if (newId && newId !== oldId) loadGroup()
})

const onGroupSaved = async () => {
  toast({ title: 'Группа обновлена' })
  await loadGroup()
}

// ── Прогресс ────────────────────────────────────────────────────────

const averageProgress = computed(() => {
  if (!participants.value.length) return 0
  const total = participants.value.reduce((sum, p) => sum + (p.completion_percent || 0), 0)
  return Math.round(total / participants.value.length)
})

const handleSliderUpdate = (pId: number, value: number) => {
  const numValue = Math.round(Number(value) * 100) / 100
  const participant = participants.value.find(p => p.id === pId)
  
  if (participant) participant.completion_percent = numValue

  if (sliderDebounceTimer) clearTimeout(sliderDebounceTimer)
  sliderDebounceTimer = setTimeout(() => {
    updateCompletion(pId, numValue)
  }, 500)
}

const updateCompletion = async (pId: number, value: number) => {
  if (!group.value) return
  if (savingProgressId.value === pId) return
  savingProgressId.value = pId

  const participant = participants.value.find(p => p.id === pId)
  const prevValue = participant?.completion_percent ?? 0

  try {
    await store.update_participant(group.value.id, pId, { completion_percent: value })
  } catch (e: any) {
    console.error('Ошибка обновления прогресса:', e)
    if (participant) participant.completion_percent = prevValue
    toast({ 
      title: 'Не удалось сохранить прогресс', 
      description: e.response?.data?.message || e.message,
    })
  } finally {
    savingProgressId.value = null
  }
}

// ── Участники ───────────────────────────────────────────────────────

const removeParticipant = async (pId: number) => {
  if (!group.value) return
  if (!confirm('Удалить участника из группы?')) return
  
  removingId.value = pId
  const originalList = [...participants.value]
  
  try {
    participants.value = participants.value.filter(p => p.id !== pId)
    await store.remove_participant(group.value.id, pId)
    toast({ title: 'Участник удалён' })
  } catch (e: any) {
    console.error('Ошибка удаления участника:', e)
    participants.value = originalList
    toast({ 
      title: 'Ошибка удаления', 
      description: e.response?.data?.message || e.message,
    })
  } finally {
    removingId.value = null
  }
}

const handleAddParticipants = async (employeeIds: number[]) => {
  if (!group.value) return
  
  const existing = new Set(participants.value.map(p => p.employee_id))
  const newIds = employeeIds.filter(id => !existing.has(id))
  
  if (!newIds.length) {
    toast({ title: 'Сотрудники уже добавлены' })
    return
  }

  addingLoading.value = true
  try {
    const results = await Promise.allSettled(
      newIds.map(id => store.add_participant(group.value!.id, { employee_id: id, completion_percent: 0 }))
    )
    
    const successCount = results.filter(r => r.status === 'fulfilled').length
    const failCount = results.length - successCount
    
    if (failCount > 0) {
      toast({
        title: 'Частичный успех',
        description: `Добавлено: ${successCount}, ошибок: ${failCount}`,
      })
    } else {
      toast({ title: `Добавлено участников: ${successCount}` })
    }
    
    selectorOpen.value = false
    await loadGroup()
  } catch (e: any) {
    console.error('Ошибка добавления участников:', e)
    toast({ 
      title: 'Ошибка добавления', 
      description: e.response?.data?.message || e.message,
    })
  } finally {
    addingLoading.value = false
  }
}

// ── Сертификаты ─────────────────────────────────────────────────────

/**
 * Открывает системный диалог выбора файла.
 * Запоминает participantId, чтобы при выборе файла отправить на правильный endpoint.
 */
const triggerCertificateUpload = (participantId: number) => {
  certUploadTargetId.value = participantId
  if (certFileInput.value) {
    certFileInput.value.value = ''
    certFileInput.value.click()
  }
}

/**
 * Обработчик выбора файла — отправляет PDF через trainingGroupService.
 */
const handleCertificateFileSelected = async (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file || !group.value || !certUploadTargetId.value) return

  if (file.type !== 'application/pdf') {
    toast({ title: 'Допустимый формат — только PDF' })
    return
  }

  if (file.size > 5 * 1024 * 1024) {
    toast({ title: 'Размер файла не должен превышать 5 МБ' })
    return
  }

  const participantId = certUploadTargetId.value
  certificateLoadingId.value = participantId

  try {
    const { data } = await trainingGroupService.uploadCertificate(
      group.value.id,
      participantId,
      file,
    )

    const participant = participants.value.find(p => p.id === participantId)
    if (participant) {
      participant.certificate_path = data?.data?.certificate_path || 'uploaded'
    }

    toast({ title: 'Сертификат загружен' })
  } catch (e: any) {
    console.error('Ошибка загрузки сертификата:', e)
    const message = e.response?.data?.errors?.certificate?.[0]
      || e.response?.data?.message
      || e.message
    toast({ title: 'Ошибка загрузки сертификата', description: message })
  } finally {
    certificateLoadingId.value = null
    certUploadTargetId.value = null
  }
}

/**
 * Скачивание сертификата через API-endpoint (Blob).
 * Всегда идём через бэкенд, не обращаемся к storage напрямую.
 */
const downloadCertificate = async (participant: { id: number }) => {
  if (!group.value) return

  certificateLoadingId.value = participant.id
  try {
    const { data } = await trainingGroupService.downloadCertificate(
      group.value.id,
      participant.id,
    )

    const url = window.URL.createObjectURL(new Blob([data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.download = `certificate_${participant.id}.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (e: any) {
    console.error('Ошибка скачивания сертификата:', e)
    toast({ title: 'Не удалось скачать сертификат' })
  } finally {
    certificateLoadingId.value = null
  }
}

/**
 * Удаление сертификата с подтверждением.
 */
const deleteCertificate = async (participant: { id: number }) => {
  if (!group.value) return
  if (!confirm('Удалить сертификат этого участника?')) return

  certificateLoadingId.value = participant.id
  try {
    await trainingGroupService.deleteCertificate(group.value.id, participant.id)

    const p = participants.value.find(item => item.id === participant.id)
    if (p) {
      p.certificate_path = null
    }

    toast({ title: 'Сертификат удалён' })
  } catch (e: any) {
    console.error('Ошибка удаления сертификата:', e)
    toast({ title: 'Не удалось удалить сертификат' })
  } finally {
    certificateLoadingId.value = null
  }
}

// ── Удаление группы ─────────────────────────────────────────────────

const deleteGroup = async () => {
  if (!group.value) return
  if (!confirm('Вы уверены, что хотите удалить эту группу? Все привязанные участники будут удалены безвозвратно.')) return

  deletingGroup.value = true
  try {
    await store.delete_group(group.value.id)
    toast({ title: 'Группа успешно удалена' })
    router.push('/groups')
  } catch (e: any) {
    console.error('Ошибка удаления группы:', e)
    toast({
      title: 'Ошибка удаления',
      description: e.response?.data?.message || e.message,
    })
  } finally {
    deletingGroup.value = false
  }
}

const formatPrice = (v: number | string) => {
  const num = Number(v || 0)
  return num.toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}
</script>