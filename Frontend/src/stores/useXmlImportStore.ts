import { defineStore } from 'pinia';
import { ref } from 'vue';
import { xmlService } from '@/api/xmlService';

export interface XmlImportLog {
  id: number;
  entity_name: string;
  entity_external_id: string | null;
  operation_type: string | null;
  status: 'success' | 'error' | 'warning';
  message: string;
  created_at: string;
}

export interface XmlImportBatch {
  id: number;
  source_system: string;
  file_name: string;
  imported_at: string;
  success_count: number;
  error_count: number;
  skipped_count: number;
}

export const useXmlImportStore = defineStore('xmlImport', () => {
  const error = ref<string | null>(null);
  const loading = ref(false);
  const uploading = ref(false);
  const responseData = ref<any>(null);
  const fieldErrors = ref<Record<string, string>>({});

  function clearErrors() {
    error.value = null;
    fieldErrors.value = {};
  }

  function handleError(e: any) {
    if (e.response?.status === 422) {
      const errors: Record<string, string[]> = e.response.data.errors ?? {};
      error.value = Object.values(errors).flat().join(', ');
      fieldErrors.value = Object.fromEntries(
        Object.entries(errors).map(([key, val]: [string, string[]]) => [key, val[0] ?? ''])
      ) as Record<string, string>;
    } else {
      error.value = e.response?.data?.message || 'Ошибка';
      fieldErrors.value = {};
    }
  }

  async function importXml(file: File): Promise<XmlImportBatch | null> {
    uploading.value = true;
    clearErrors();
    try {
      const formData = new FormData();
      formData.append('file', file);
      const {  data } = await xmlService.import(formData);
      responseData.value = data;
      const batches = data.batches || data.data?.batches || data.data?.data || [];
      return Array.isArray(batches) ? batches[0] : null;
    } catch (e: any) {
      handleError(e);
      throw e;
    } finally {
      uploading.value = false;
    }
  }

  async function read_batches(): Promise<XmlImportBatch[]> {
    loading.value = true;
    try {
      const {  data } = await xmlService.listBatches();
      const paginator = data?.data ?? data;
      const raw = paginator?.data ?? paginator ?? [];
      return Array.isArray(raw) ? raw : [];
    } catch (e: any) {
      handleError(e);
      return [];
    } finally {
      loading.value = false;
    }
  }

  // ✅ ИСПРАВЛЕНО: корректная работа с data и проброс ошибок
  async function read_batch_logs(batchId: number): Promise<XmlImportLog[]> {
    try {
      const {  data } = await xmlService.getBatchLogs(batchId);
      // Поддержка: {  { data: [...] } } или { logs: [...] } или прямой массив
      const raw = data?.data?.data ?? data?.data ?? data?.logs ?? data ?? [];
      return Array.isArray(raw) ? raw : [];
    } catch (e: any) {
      handleError(e);
      throw e; // ✅ Пробрасываем ошибку в компонент, чтобы сработал toast
    }
  }

  return {
    error,
    loading,
    uploading,
    fieldErrors,
    clearErrors,
    importXml,
    read_batches,
    read_batch_logs,
  };
});