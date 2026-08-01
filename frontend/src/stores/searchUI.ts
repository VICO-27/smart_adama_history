// src/stores/searchUI.ts
import { defineStore } from 'pinia'

export const useSearchUIStore = defineStore('searchUI', {
  state: () => ({
    isOpen: false,
    pendingQuery: '',
  }),
  actions: {
    open() { this.isOpen = true },
    close() { this.isOpen = false },
    toggle() { this.isOpen = !this.isOpen },
  },
})