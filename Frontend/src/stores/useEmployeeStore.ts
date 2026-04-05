import { defineStore } from 'pinia';
import { ref } from 'vue';
import { employeeService } from '@/api/employeeService';

export const useEmployeeStore = defineStore('employee', () => {
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

  async function read_employees() {
    const { data: response } = await employeeService.list();
    return response.employees || response.data?.employees || response.data || [];
  }

  async function create_employee(payload: object) {
    loading.value = true;
    clearErrors();
    try {
      const { data: response } = await employeeService.create(payload);
      responseData.value = response;
      return response.employee;
    } catch (e: any) {
      handleError(e);
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function update_employee(id: number, payload: object) {
    loading.value = true;
    clearErrors();
    try {
      const { data: response } = await employeeService.update(id, payload);
      responseData.value = response;
      return response.employee;
    } catch (e: any) {
      handleError(e);
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function delete_employee(id: number) {
    loading.value = true;
    clearErrors();
    try {
      const { data: response } = await employeeService.delete(id);
      responseData.value = response;
    } catch (e: any) {
      handleError(e);
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function read_companies() {
    const { data: response } = await employeeService.getCompanies();
    return response.companies || response.data?.companies || response.data || [];
  }

  async function read_training_groups() {
    const { data: response } = await employeeService.getTrainingGroups();
    // Laravel пагинация: { success: true, data: { data: [...] } }
    return response.data?.data?.data || response.data?.data || response.data || [];
  }

  return {
    error,
    loading,
    fieldErrors,
    clearErrors,
    read_employees,
    create_employee,
    update_employee,
    delete_employee,
    read_companies,
    read_training_groups,
  };
});