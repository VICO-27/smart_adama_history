<!-- src/App.vue -->
<template>
  <AppShell>
    <router-view v-slot="{ Component, route }">
      <transition name="page" mode="out-in">
        <component :is="Component" :key="route.fullPath" />
      </transition>
    </router-view>
  </AppShell>
</template>

<script setup lang="ts">
import AppShell from '@/components/layout/AppShell.vue'
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
</style>