<!-- src/App.vue -->
<template>
  <router-view v-slot="{ Component, route }">
    <transition name="page" mode="out-in">
      <component :is="Component" :key="route.fullPath" />
    </transition>
  </router-view>

  <!-- The ONE and ONLY Global Assistant rendered globally -->
  <GlobalAssistant v-if="authStore.isAuthenticated" />
</template>

<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import GlobalAssistant from '@/components/layout/GlobalAssistant.vue'

const authStore = useAuthStore()
</script>

<style>
.page-enter-active,
.page-leave-active {
  transition: opacity 0.15s ease;
}
.page-enter-from,
.page-leave-to {
  opacity: 0;
}
#app {
  width: 100%;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
body {
  margin: 0;
  padding: 0;
  background-color: var(--sa-bg);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>