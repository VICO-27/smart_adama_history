<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

onMounted(async () => {
  const token = route.query.token as string
  
  if (token) {
    // 1. Save the token to local storage and the Pinia store
    localStorage.setItem('sa_token', token)
    auth.token = token
    
    // 2. Fetch the user's profile data from Laravel
    await auth.fetchMe()
    
    // 3. Redirect to the dashboard
    router.push('/dashboard')
  } else {
    // If it fails, send them back to login
    router.push('/login?error=oauth_failed')
  }
})
</script>

<template>
  <div class="min-h-screen bg-[var(--sa-bg)] flex items-center justify-center p-4">
    <div class="text-center">
      <div class="w-12 h-12 border-4 border-[var(--sa-gray)] border-t-black rounded-full animate-spin mx-auto mb-4"></div>
      <h1 class="font-display text-xl font-semibold text-[var(--sa-dark)] tracking-tight animate-pulse">
        Securely logging you in...
      </h1>
    </div>
  </div>
</template>