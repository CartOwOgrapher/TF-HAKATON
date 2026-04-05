import { defineStore } from 'pinia';
import { ref } from 'vue';
import { trainingGroupService } from '@/api/trainingGroupService';

export interface TrainingGroup {
  id: number;
  course_id: number;
  course?: { id: number; name: string; price?: any };
  course_name?: string;
  start_date: string;
  end_date: string;
  status: 'planned' | 'in_progress' | 'completed' | 'cancelled';
  status_label?: string;
  participants_count: number;
  price_per_person: number;
  group_cost: number;
  average_progress: number;
  participants?: Array<{
    id: number;
    employee_id: number;
    employee?: { id: number; full_name: string; email?: string };
    completion_percent: number;
  }>;
  created_at?: string;
  updated_at?: string;
}

export const useTrainingGroupStore = defineStore('trainingGroup', () => {
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

  // --- Группы ---
  async function read_groups(): Promise<TrainingGroup[]> {
    const {  data } = await trainingGroupService.list();
    // Laravel пагинация: { success: true,  {  [groups] } }
    const raw = data?.data?.data || data?.data || data || [];
    return Array.isArray(raw) ? raw : [];
  }

async function read_group(id: number): Promise<TrainingGroup | null> {
  try {
    const { data } = await trainingGroupService.show(id);
    return data?.data || data || null;
  } catch (e: any) {
    if (e.response?.status === 404) return null;
    throw e;
  }
}

  async function create_group(payload: object) {
    loading.value = true;
    clearErrors();
    try {
      const {  data } = await trainingGroupService.create(payload);
      responseData.value = data;
      return data.group as TrainingGroup;
    } catch (e: any) {
      handleError(e);
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function update_group(id: number, payload: object) {
    loading.value = true;
    clearErrors();
    try {
      const {  data } = await trainingGroupService.update(id, payload);
      responseData.value = data;
      return data.group as TrainingGroup;
    } catch (e: any) {
      handleError(e);
      throw e;
    } finally {
      loading.value = false;
    }
  }

  

  async function delete_group(id: number) {
    loading.value = true;
    clearErrors();
    try {
      const {  data } = await trainingGroupService.delete(id);
      responseData.value = data;
    } catch (e: any) {
      handleError(e);
      throw e;
    } finally {
      loading.value = false;
    }
  }

  // --- Участники ---
  async function read_participants(groupId: number) {
    const {  data } = await trainingGroupService.getParticipants(groupId);
    const raw = data?.data || data?.participants || data || [];
    return Array.isArray(raw) ? raw : [];
  }

  async function add_participant(groupId: number, payload: { employee_id: number; completion_percent?: number }) {
    loading.value = true;
    clearErrors();
    try {
      const {  data } = await trainingGroupService.addParticipant(groupId, payload);
      return data.participant;
    } catch (e: any) {
      handleError(e);
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function update_participant(groupId: number, participantId: number, payload: { completion_percent: number }) {
    loading.value = true;
    clearErrors();
    try {
      const {  data } = await trainingGroupService.updateParticipant(groupId, participantId, payload);
      return data.participant;
    } catch (e: any) {
      handleError(e);
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function remove_participant(groupId: number, participantId: number) {
    loading.value = true;
    clearErrors();
    try {
      await trainingGroupService.removeParticipant(groupId, participantId);
    } catch (e: any) {
      handleError(e);
      throw e;
    } finally {
      loading.value = false;
    }
  }


  

  // --- Статус ---
  async function change_group_status(id: number, status: string) {
    loading.value = true;
    clearErrors();
    try {
      const {  data } = await trainingGroupService.changeStatus(id, status);
      return data.group as TrainingGroup;
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
    read_groups,
    read_group,
    create_group,
    update_group,
    delete_group,
    read_participants,
    add_participant,
    update_participant,
    remove_participant,
    change_group_status,
  };
});