import { defineStore } from 'pinia'
import { base44 } from '@/api/base44Client'

export const useCompaniesStore = defineStore('companies', {
  state: () => ({
    companies: [] as any[],
    loading: false,
  }),

  actions: {
    async load() {
      this.loading = true
      this.companies = await base44.entities.Company.list()
      this.loading = false
    },

    async create(data: any) {
      await base44.entities.Company.create(data)
      await this.load()
    },

    async delete(id: string) {
      await base44.entities.Company.delete(id)
      this.companies = this.companies.filter(c => c.id !== id)
    }
  }
})