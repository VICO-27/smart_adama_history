<script setup lang="ts">
import { RouterLink } from 'vue-router'
import SaButton from '@/components/ui/SaButton.vue'
</script>

<template>
  <div class="min-h-screen bg-[var(--sa-bg)] flex flex-col items-center justify-center text-center p-4 gap-4">
    <div class="text-5xl">🔍</div>
    <h1 class="font-display text-3xl font-semibold text-[var(--sa-dark)]">Page not found</h1>
    <p class="text-[var(--sa-taupe)]">The page you're looking for doesn't exist.</p>
    <RouterLink to="/"><SaButton>Go home</SaButton></RouterLink>
  </div>
</template>
