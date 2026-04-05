import { defineStore } from 'pinia'
import { base44 } from '@/api/base44Client'

export const useGroupsStore = defineStore('groups', {
  state: () => ({
    groups: [] as any[],
    loading: false,
  }),

  getters: {
    activeGroups: (state) =>
      state.groups.filter(g => g.status === 'В процессе').length,

    recentGroups: (state) =>
      state.groups.slice(0, 5),
  },

  actions: {
    async loadGroups() {
      this.loading = true
      this.groups = await base44.entities.TrainingGroup.list()
      this.loading = false
    },

    async createGroup(data: any) {
      await base44.entities.TrainingGroup.create(data)
      await this.loadGroups()
    },

    async deleteGroup(id: string) {
      await base44.entities.TrainingGroup.delete(id)
      this.groups = this.groups.filter(g => g.id !== id)
    }
  }
})