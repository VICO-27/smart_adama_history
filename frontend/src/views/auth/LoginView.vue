<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import SaInput  from '@/components/ui/SaInput.vue'
import SaButton from '@/components/ui/SaButton.vue'

const auth   = useAuthStore()
const router = useRouter()

const email    = ref('')
const password = ref('')

async function submit() {
  await auth.login({ email: email.value, password: password.value })
  router.push('/dashboard')
}

// Function to handle OAuth redirects to your Laravel backend
function loginWith(provider: 'google' | 'facebook') {
  window.location.href = `http://localhost:8000/api/v1/auth/${provider}/redirect`
}
</script>

<template>
  <div class="min-h-screen bg-[var(--sa-bg)] flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="font-display text-3xl font-semibold text-[var(--sa-dark)] tracking-tight">Smart Adama</h1>
        <p class="mt-2 text-sm text-[var(--sa-taupe)]">Sign in to continue learning</p>
      </div>

      <!-- Card -->
      <div class="glass rounded-2xl border border-[var(--sa-gray)] shadow-[var(--shadow-md)] p-7">
        
        <!-- Social Login Buttons -->
        <div class="flex flex-col gap-3 mb-5">
          <button @click="loginWith('google')" type="button" class="w-full flex items-center justify-center gap-3 bg-white border border-gray-200 text-sm font-semibold text-gray-700 px-4 py-2.5 rounded-xl hover:bg-gray-50 transition-colors shadow-sm active:scale-95">
            <svg class="w-5 h-5" viewBox="0 0 24 24">
              <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
              <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
              <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
              <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Sign in with Google
          </button>
          
          <button @click="loginWith('facebook')" type="button" class="w-full flex items-center justify-center gap-3 bg-white border border-gray-200 text-sm font-semibold text-gray-700 px-4 py-2.5 rounded-xl hover:bg-gray-50 transition-colors shadow-sm active:scale-95">
            <svg class="w-5 h-5 text-[#1877F2]" fill="currentColor" viewBox="0 0 24 24">
              <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
            Sign in with Facebook
          </button>
        </div>

        <div class="relative flex items-center py-2 mb-5">
          <div class="flex-grow border-t border-[var(--sa-gray)]"></div>
          <span class="flex-shrink-0 mx-4 text-xs font-semibold text-[var(--sa-taupe)] uppercase tracking-wider">Or</span>
          <div class="flex-grow border-t border-[var(--sa-gray)]"></div>
        </div>

        <form @submit.prevent="submit" novalidate class="flex flex-col gap-5">
          <SaInput
            v-model="email"
            label="Email"
            type="email"
            placeholder="you@example.com"
            autocomplete="email"
            :error="auth.error && !auth.loading ? '' : undefined"
            required
          />
          <SaInput
            v-model="password"
            label="Password"
            type="password"
            placeholder="••••••••"
            autocomplete="current-password"
            required
          />

          <p v-if="auth.error" class="text-sm text-red-600 -mt-2" role="alert">{{ auth.error }}</p>

          <div class="flex items-center justify-between text-sm">
            <span />
            <RouterLink to="/forgot-password" class="text-[var(--sa-taupe)] hover:text-[var(--sa-dark)] transition-colors">
              Forgot password?
            </RouterLink>
          </div>

          <SaButton type="submit" :loading="auth.loading" class="w-full justify-center">
            Sign in
          </SaButton>
        </form>
      </div>

      <p class="mt-6 text-center text-sm text-[var(--sa-taupe)]">
        Don't have an account?
        <RouterLink to="/register" class="font-medium text-[var(--sa-dark)] hover:underline ml-1">
          Get started
        </RouterLink>
      </p>
    </div>
  </div>
</template>