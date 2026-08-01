<template>
  <div :class="[
    'bg-[var(--sa-bg)] text-[var(--sa-dark)] flex flex-col',
    route.meta.hideNav ? 'h-screen overflow-hidden' : 'min-h-screen'
  ]">

    <AppNav @open-auth="openAuthModal" />

    <main :class="['flex-grow w-full relative', route.meta.hideNav ? 'pt-0 h-full' : 'pt-16 md:pt-20']">
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
import { useRoute } from 'vue-router'
import AppNav from './AppNav.vue'
import AuthModal from '@/components/auth/AuthModal.vue'

const route = useRoute()
const isAuthModalOpen = ref(false)
const authMode = ref<'login' | 'register'>('login')

const openAuthModal = (mode: 'login' | 'register') => {
  authMode.value = mode
  isAuthModalOpen.value = true
}
</script>