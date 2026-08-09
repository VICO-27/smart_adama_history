<template>
  <div class="min-h-screen bg-[var(--sa-bg)] text-[var(--sa-dark)] flex flex-col">

    <AppNav @open-auth="openAuthModal" />

    <!-- Added pt-24 md:pt-28 to give ~1cm of clean breathing room below the fixed navbar -->
    <main class="flex-grow w-full relative pt-24 md:pt-28">
      <slot />
    </main>

    <AuthModal
      :is-open="isAuthModalOpen"
      :initial-mode="authMode"
      @close="isAuthModalOpen = false"
    />

  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AppNav from './AppNav.vue'
import AuthModal from '@/components/auth/AuthModal.vue'

const authStore = useAuthStore()

const isAuthModalOpen = ref(false)
const authMode = ref<'login' | 'register'>('login')

const openAuthModal = (mode: 'login' | 'register') => {
  authMode.value = mode
  isAuthModalOpen.value = true
}
</script>