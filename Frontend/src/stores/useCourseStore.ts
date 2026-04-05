import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { courseService } from '@/api/courseService';

export const useCourseStore = defineStore('course', () => {
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

  async function read_courses() {
    const { data: response } = await courseService.read();
    return response.courses;
  }

  async function create_course(payload: {
    code: string;
    title: string;
    description: string;
    duration_days: number;
    price: number;
  }) {
    loading.value = true;
    clearErrors();
    try {
      const { data: response } = await courseService.create(payload);
      responseData.value = response;
      return response.course;
    } catch (e: any) {
      handleError(e);
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function update_course(id: number, payload: {
    code: string;
    title: string;
    description: string;
    duration_days: number;
    price: number;
  }) {
    loading.value = true;
    clearErrors();
    try {
      const { data: response } = await courseService.update(id, payload);
      responseData.value = response;
      return response.course;
    } catch (e: any) {
      handleError(e);
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function delete_course(id: number) {
    loading.value = true;
    clearErrors();
    try {
      const { data: response } = await courseService.delete(id);
      responseData.value = response;
    } catch (e: any) {
      handleError(e);
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function read_prices(courseId: number) {
    const { data: response } = await courseService.getPrices(courseId);
    return response.data.course_price;
  }

  async function create_price(courseId: number, payload: { price: number }) {
    clearErrors();
    try {
      const { data: response } = await courseService.createPrice(courseId, payload);
      return response.data.course_price;
    } catch (e: any) {
      handleError(e);
      throw e;
    }
  }

  return {
    error,
    loading,
    fieldErrors,
    read_courses,
    create_course,
    update_course,
    delete_course,
    read_prices,
    create_price,
  };
});