import { defineStore } from 'pinia'
import { ref, computed } from 'vue';
import { dashboardService } from '@/api/dashboardService';

export const useDashboardStore = defineStore('dashboard', () => {
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

  async function fetchCourses () {
    const { data: response } = await dashboardService.getCourses();
    
    return response.courses;
  }

  async function fetchGroups() {
    const { data: response } = await dashboardService.getGroups();
    
    return response.data?.data;
  }

  async function fetchCompanies() {
    const { data: response } = await dashboardService.getCompanies();
    
    return response.companies;
  }

  async function fetchSpecs() {
    const { data: response } = await dashboardService.getSpecs();
    
    return response.data?.data;
  }

  return {
    error,
    loading,
    fieldErrors,
    fetchCourses,
    fetchGroups,
    fetchCompanies,
    fetchSpecs,
  };
})