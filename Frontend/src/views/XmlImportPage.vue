<template>
  <div class="space-y-6">
    <PageHeader title="XML Импорт" description="Загрузка данных из внешних систем">
      <label 
        class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors cursor-pointer disabled:opacity-50"
        :class="{ 'pointer-events-none': store.uploading }"
      >
        <Upload v-if="!store.uploading" class="w-4 h-4" />
        <Loader2 v-else class="w-4 h-4 animate-spin" />
        <span>{{ store.uploading ? 'Обработка...' : 'Загрузить файл' }}</span>
        <input type="file" accept=".xml" class="hidden" @change="handleFileSelect" :disabled="store.uploading" />
      </label>
    </PageHeader>

    <div class="border-2 border-dashed rounded-2xl p-8 text-center transition-colors"
         :class="[isDragOver ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/50', store.uploading ? 'opacity-50 pointer-events-none' : '']"
         @dragover.prevent="isDragOver = true" @dragleave.prevent="isDragOver = false" @drop.prevent="handleDrop">
      <CloudUpload class="w-12 h-12 mx-auto mb-3 text-muted-foreground" />
      <p class="text-sm text-muted-foreground mb-1">Перетащите XML-файл сюда или нажмите кнопку выше</p>
      <p class="text-xs text-muted-foreground/70">Поддерживаются: Edu_Participant, Edu_Course, Edu_Specification</p>
    </div>

    <div v-if="store.error" class="p-3 rounded-lg bg-destructive/10 text-destructive text-sm">{{ store.error }}</div>

    <div class="bg-card rounded-2xl border border-border overflow-hidden">
      <div class="px-6 py-4 border-b border-border flex items-center justify-between">
        <h3 class="font-semibold">История импорта</h3>
        <Button variant="ghost" size="sm" @click="loadBatches" :disabled="store.loading">
          <RefreshCw v-if="store.loading" class="w-4 h-4 animate-spin mr-1" /> Обновить
        </Button>
      </div>

      <div v-if="store.loading" class="flex items-center justify-center py-12">
        <div class="w-6 h-6 border-4 border-muted border-t-primary rounded-full animate-spin" />
      </div>

      <div v-else-if="batches.length === 0" class="p-8 text-center text-muted-foreground text-sm">Нет записей импорта</div>

      <table v-else class="w-full">
        <thead>
          <tr class="border-b border-border bg-muted/50 text-xs uppercase text-muted-foreground">
            <th class="text-left px-6 py-3">Файл</th>
            <th class="text-left px-6 py-3">Система</th>
            <th class="text-left px-6 py-3">Дата</th>
            <th class="text-left px-6 py-3">Результат</th>
            <th class="text-right px-6 py-3">Действия</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border">
          <template v-for="batch in batches" :key="batch.id">
            <tr class="hover:bg-muted/30 transition-colors">
              <td class="px-6 py-4 font-medium truncate max-w-[200px]">{{ batch.file_name }}</td>
              <td class="px-6 py-4 text-sm text-muted-foreground">{{ batch.source_system }}</td>
              <td class="px-6 py-4 text-sm">{{ formatDate(batch.imported_at) }}</td>
              <td class="px-6 py-4">
                <div class="flex gap-2">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">✓ {{ batch.success_count || 0 }}</span>
                  <span v-if="batch.error_count" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">✗ {{ batch.error_count }}</span>
                  <span v-if="batch.skipped_count" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">⊘ {{ batch.skipped_count }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-right">
                <Button size="sm" variant="ghost" class="h-8" @click="toggleBatchLogs(batch.id)">
                  <ChevronDown v-if="expandedBatchId !== batch.id" class="w-4 h-4" />
                  <ChevronUp v-else class="w-4 h-4" /> Логи
                </Button>
              </td>
            </tr>

            <tr v-if="expandedBatchId === batch.id" :key="`logs-${batch.id}`">
              <td colspan="5" class="px-6 py-4 bg-muted/20 border-t border-border">
                <div v-if="logsLoadingId === batch.id" class="flex justify-center py-4">
                  <Loader2 class="w-5 h-5 animate-spin text-primary" />
                </div>
                <div v-else-if="!batchLogsMap[batch.id]?.length" class="text-sm text-muted-foreground py-2 text-center">Нет записей в логах</div>
                <div v-else class="space-y-2 max-h-60 overflow-y-auto px-2">
                  <div v-for="log in batchLogsMap[batch.id]" :key="log.id" class="flex items-start gap-3 p-2 rounded-lg bg-background border border-border text-sm">
                    <span class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0" :class="getLogStatusColor(log.status)" />
                    <div class="flex-1 min-w-0">
                      <p class="font-medium">
                        {{ log.entity_name }}
                        <span v-if="log.entity_external_id" class="text-muted-foreground font-normal">#{{ log.entity_external_id }}</span>
                      </p>
                      <p class="text-muted-foreground text-xs mt-0.5 truncate">{{ log.operation_type ? getOperationLabel(log.operation_type) + ' • ' : '' }}{{ log.message }}</p>
                    </div>
                    <span class="text-xs text-muted-foreground whitespace-nowrap">{{ formatTime(log.created_at) }}</span>
                  </div>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Upload, CloudUpload, Loader2, RefreshCw, ChevronDown, ChevronUp } from 'lucide-vue-next'
import { useXmlImportStore, type XmlImportBatch, type XmlImportLog } from '@/stores/useXmlImportStore'
import { toast } from '@/composables/use-toast'
import Button from '@/components/ui/button.vue'
import PageHeader from '@/components/ui/PageHeader.vue'

const store = useXmlImportStore()
const batches = ref<XmlImportBatch[]>([])
const expandedBatchId = ref<number | null>(null)
const logsLoadingId = ref<number | null>(null)
const batchLogsMap = ref<Record<number, XmlImportLog[]>>({})
const isDragOver = ref(false)

const loadBatches = async () => { batches.value = await store.read_batches() }

// ✅ ИСПРАВЛЕНО: корректная ленивая загрузка с кешированием
const toggleBatchLogs = async (batchId: number) => {
  if (expandedBatchId.value === batchId) {
    expandedBatchId.value = null
  } else {
    expandedBatchId.value = batchId
    if (!batchLogsMap.value[batchId]) {
      logsLoadingId.value = batchId
      try {
        batchLogsMap.value[batchId] = await store.read_batch_logs(batchId)
      } catch {
        toast({ title: 'Ошибка загрузки логов', description: store.error || 'Не удалось получить данные', variant: 'destructive' })
        batchLogsMap.value[batchId] = []
      } finally {
        logsLoadingId.value = null
      }
    }
  }
}

const handleFileSelect = async (e: Event) => {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  await uploadFile(file)
  input.value = ''
}

const handleDrop = async (e: DragEvent) => {
  isDragOver.value = false
  const file = e.dataTransfer?.files[0]
  if (!file) return
  if (file.type !== 'application/xml' && !file.name.endsWith('.xml')) {
    toast({ title: 'Неверный формат', description: 'Загрузите файл .xml', variant: 'destructive' })
    return
  }
  await uploadFile(file)
}

const uploadFile = async (file: File) => {
  try {
    const batch = await store.importXml(file)
    if (batch) {
      const hasErrors = (batch.error_count || 0) > 0
      toast({ 
        title: hasErrors ? 'Импорт завершён с ошибками' : 'Импорт успешно завершён', 
        description: `${batch.file_name}: ✓ ${batch.success_count}, ✗ ${batch.error_count}, ⊘ ${batch.skipped_count}`,
      })
    } else {
      toast({ title: 'Импорт запущен'})
    }
    await loadBatches()
  } catch (e: any) {
    toast({ title: 'Ошибка импорта', description: store.error || e.message, variant: 'destructive' })
  }
}

const formatDate = (d: string) => d ? new Date(d).toLocaleString('ru-RU', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' }) : '—'
const formatTime = (d: string) => new Date(d).toLocaleTimeString('ru-RU', { hour:'2-digit', minute:'2-digit' })
const getLogStatusColor = (s: string) => ({ success: 'bg-green-500', error: 'bg-red-500', warning: 'bg-amber-500' }[s] || 'bg-gray-400')
const getOperationLabel = (o: string) => ({ create: 'Создан', update: 'Обновлён', skip: 'Пропущен' }[o] || o)

onMounted(loadBatches)
</script>