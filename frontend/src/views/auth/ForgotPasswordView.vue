<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { authApi } from '@/api/auth'
import SaInput  from '@/components/ui/SaInput.vue'
import SaButton from '@/components/ui/SaButton.vue'

const email   = ref('')
const loading = ref(false)
const sent    = ref(false)
const error   = ref('')

async function submit() {
  loading.value = true
  error.value = ''
  try {
    await authApi.forgotPassword(email.value)
    sent.value = true
  } catch {
    error.value = 'Something went wrong. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-[var(--sa-bg)] flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
      <div class="text-center mb-8">
        <h1 class="font-display text-2xl font-semibold text-[var(--sa-dark)]">Reset your password</h1>
        <p class="mt-2 text-sm text-[var(--sa-taupe)]">We'll send a link to your email</p>
      </div>

      <div class="glass rounded-2xl border border-[var(--sa-gray)] shadow-[var(--shadow-md)] p-7">
        <div v-if="sent" class="text-center py-4">
          <div class="text-3xl mb-3">📬</div>
          <p class="text-sm text-[var(--sa-dark)] font-medium">Check your inbox</p>
          <p class="text-sm text-[var(--sa-taupe)] mt-1">If that email exists, a reset link is on its way.</p>
        </div>
        <form v-else @submit.prevent="submit" novalidate class="flex flex-col gap-5">
          <SaInput v-model="email" label="Email" type="email" placeholder="you@example.com" autocomplete="email" required />
          <p v-if="error" class="text-sm text-red-600" role="alert">{{ error }}</p>
          <SaButton type="submit" :loading="loading" class="w-full justify-center">Send reset link</SaButton>
        </form>
      </div>

      <p class="mt-6 text-center text-sm text-[var(--sa-taupe)]">
        <RouterLink to="/login" class="font-medium text-[var(--sa-dark)] hover:underline">← Back to sign in</RouterLink>
      </p>
    </div>
  </div>
</template>
