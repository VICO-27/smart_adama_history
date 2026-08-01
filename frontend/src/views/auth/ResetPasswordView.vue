<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { authApi } from '@/api/auth'
import SaInput  from '@/components/ui/SaInput.vue'
import SaButton from '@/components/ui/SaButton.vue'

const route   = useRoute()
const router  = useRouter()
const password = ref('')
const loading  = ref(false)
const error    = ref('')
const success  = ref(false)

async function submit() {
  loading.value = true
  error.value = ''
  try {
    await authApi.resetPassword({
      token:    route.query.token as string,
      email:    route.query.email as string,
      password: password.value,
    })
    success.value = true
    setTimeout(() => router.push('/login'), 2000)
  } catch (e: any) {
    error.value = e.response?.data?.error?.message ?? 'Invalid or expired token.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-[var(--sa-bg)] flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
      <div class="text-center mb-8">
        <h1 class="font-display text-2xl font-semibold text-[var(--sa-dark)]">Set new password</h1>
      </div>

      <div class="glass rounded-2xl border border-[var(--sa-gray)] shadow-[var(--shadow-md)] p-7">
        <div v-if="success" class="text-center py-4">
          <div class="text-3xl mb-3">✅</div>
          <p class="text-sm font-medium text-[var(--sa-dark)]">Password updated! Redirecting…</p>
        </div>
        <form v-else @submit.prevent="submit" novalidate class="flex flex-col gap-5">
          <SaInput v-model="password" label="New password" type="password" placeholder="Min. 8 chars with a number" autocomplete="new-password" required />
          <p v-if="error" class="text-sm text-red-600" role="alert">{{ error }}</p>
          <SaButton type="submit" :loading="loading" class="w-full justify-center">Update password</SaButton>
        </form>
      </div>

      <p class="mt-6 text-center text-sm text-[var(--sa-taupe)]">
        <RouterLink to="/login" class="font-medium text-[var(--sa-dark)] hover:underline">← Back to sign in</RouterLink>
      </p>
    </div>
  </div>
</template>
