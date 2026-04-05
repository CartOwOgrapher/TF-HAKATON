import { defineStore } from 'pinia';
import { ref } from 'vue';
import { companyService } from '@/api/companyService';

export interface Company {
  id: number;
  code: string;
  name: string;
  created_at?: string;
  updated_at?: string;
  deleted_at?: string | null;
}

export const useCompanyStore = defineStore('company', () => {
  const error = ref<string | null>(null);
  const loading = ref(false);
  const responseData = ref<any>(null);
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

  async function read_companies(): Promise<Company[]> {
    const {  data } = await companyService.list();
    const raw = data.companies || data.data?.companies || data.data || [];
    return Array.isArray(raw) ? raw : [];
  }

  async function create_company(payload: { code: string; name: string }) {
    loading.value = true;
    clearErrors();
    try {
      const {  data } = await companyService.create(payload);
      responseData.value = data;
      return data.company as Company;
    } catch (e: any) {
      handleError(e);
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function update_company(id: number, payload: { code: string; name: string }) {
    loading.value = true;
    clearErrors();
    try {
      const {  data } = await companyService.update(id, payload);
      responseData.value = data;
      return data.company as Company;
    } catch (e: any) {
      handleError(e);
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function delete_company(id: number) {
    loading.value = true;
    clearErrors();
    try {
      const {  data } = await companyService.delete(id);
      responseData.value = data;
    } catch (e: any) {
      handleError(e);
      throw e;
    } finally {
      loading.value = false;
    }
  }

  return {
    error,
    loading,
    fieldErrors,
    clearErrors,
    read_companies,
    create_company,
    update_company,
    delete_company,
  };
});