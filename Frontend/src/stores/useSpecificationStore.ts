import { defineStore } from 'pinia';
import { ref } from 'vue';
import { specificationService } from '@/api/specificationService';

export const useSpecificationStore = defineStore('specification', () => {
  const error = ref<string | null>(null);
  const loading = ref(false);
  const responseData = ref(null);
  const fieldErrors = ref<Record<string, string>>({});

  function clearErrors() {
    error.value = null;
    fieldErrors.value = {};
  }

  function handleError(e: any) {
    if (e.response?.status === 422) {
      const errors = e.response.data.errors;
      error.value = Object.values(errors).flat().join(', ');
      fieldErrors.value = Object.fromEntries(
        Object.entries(errors).map(([key, val]) => [key, (val as string[])[0] ?? ''])
      ) as Record<string, string>;
    } else {
      error.value = e.response?.data?.message || 'Ошибка';
      fieldErrors.value = {};
    }
  }

  /** GET /specifications */
  async function read_specifications() {
    const { data: response } = await specificationService.read();
    return response.data.data;
  }

  /** GET /specifications/:id  (with groups & costs) */
  async function show_specification(id: number) {
    const { data: response } = await specificationService.show(id);
    return response.data;
  }

  /** POST /specifications  body: { number, date, company_id } */
  async function create_specification(payload: {
    number: string;
    date: string;
    company_id: number;
  }) {
    loading.value = true;
    clearErrors();
    try {
      const { data: response } = await specificationService.create(payload);
      responseData.value = response;
      return response.data;
    } catch (e: any) {
      handleError(e);
      throw e;
    } finally {
      loading.value = false;
    }
  }

  /** PUT /specifications/:id */
  async function update_specification(id: number, payload: {
    number?: string;
    date?: string;
    company_id?: number;
  }) {
    loading.value = true;
    clearErrors();
    try {
      const { data: response } = await specificationService.update(id, payload);
      responseData.value = response;
      return response.data;
    } catch (e: any) {
      handleError(e);
      throw e;
    } finally {
      loading.value = false;
    }
  }

  /** DELETE /specifications/:id */
  async function delete_specification(id: number) {
    loading.value = true;
    clearErrors();
    try {
      const { data: response } = await specificationService.delete(id);
      responseData.value = response;
    } catch (e: any) {
      handleError(e);
      throw e;
    } finally {
      loading.value = false;
    }
  }

  /** POST /specifications/:specId/groups/:groupId */
  async function attach_group(specId: number, groupId: number) {
    clearErrors();
    try {
      const { data: response } = await specificationService.attachGroup(specId, groupId);
      return response.data;
    } catch (e: any) {
      handleError(e);
      throw e;
    }
  }

  /** DELETE /specifications/:specId/groups/:groupId */
  async function detach_group(specId: number, groupId: number) {
    clearErrors();
    try {
      const { data: response } = await specificationService.detachGroup(specId, groupId);
      return response.data;
    } catch (e: any) {
      handleError(e);
      throw e;
    }
  }

  return {
    error,
    loading,
    fieldErrors,
    read_specifications,
    show_specification,
    create_specification,
    update_specification,
    delete_specification,
    attach_group,
    detach_group,
  };
});