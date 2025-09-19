import { defineStore } from 'pinia'

export const useUiStore = defineStore('ui', {
  state: () => ({ pending: 0 }),
  getters: { isLoading: (s) => s.pending > 0 },
  actions: {
    start() { this.pending++ },
    done()  { this.pending = Math.max(0, this.pending - 1) }
  }
})
