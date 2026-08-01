<template>
  <transition name="modal">
    <!-- Backdrop -->
    <div v-if="isOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-[var(--sa-dark)]/40 backdrop-blur-sm" @click.self="closeModal">
      
      <!-- Modal Card -->
      <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-[420px] p-10 flex flex-col items-center relative">
        
        <!-- Close Button -->
        <button @click="closeModal" class="absolute top-6 right-6 text-gray-400 hover:text-black transition">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>

        <!-- Icon -->
        <div class="w-16 h-16 mb-6 flex items-center justify-center">
          <span class="text-5xl">⚡️</span>
        </div>

        <!-- Title dynamically changes based on mode -->
        <h1 class="text-3xl font-display font-bold text-black mb-8 text-center">
          <template v-if="mode === 'register'">Create account</template>
          <template v-else-if="showEmailForm">Welcome back</template>
          <template v-else>Sign in</template>
        </h1>

        <!-- STEP 1: Social Buttons (Hidden if they are registering OR looking at the email login form) -->
        <div v-if="mode === 'login' && !showEmailForm" class="w-full flex flex-col gap-3">
          <button 
            @click="loginWith('google')" 
            type="button" 
            class="flex items-center justify-center gap-3 w-full py-3 px-4 rounded-full border border-gray-300 hover:bg-gray-50 transition-colors text-sm font-medium text-black cursor-pointer active:scale-95"
          >
            <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
            Sign in with Google
          </button>
          
          <button 
            @click="loginWith('facebook')" 
            type="button" 
            class="flex items-center justify-center gap-3 w-full py-3 px-4 rounded-full border border-gray-300 hover:bg-gray-50 transition-colors text-sm font-medium text-black cursor-pointer active:scale-95"
          >
            <svg class="w-5 h-5" fill="#1877F2" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            Sign in with Facebook
          </button>

          <button @click="showEmailForm = true" class="mt-2 flex items-center justify-center w-full py-3 px-4 rounded-full bg-[#2A2B2A] hover:bg-black transition-colors text-sm font-bold text-white shadow-md cursor-pointer active:scale-95">
            Sign in with email or username
          </button>
        </div>

        <!-- STEP 2: Email Form (Handles both Login and Registration) -->
        <form v-else @submit.prevent="submitAuth" novalidate class="w-full flex flex-col gap-4">
          
          <div v-if="auth.error" class="text-red-600 text-sm text-center bg-red-50 py-2 rounded-lg" role="alert">
            {{ auth.error }}
          </div>

          <!-- Name field only shows when Registering -->
          <SaInput 
            v-if="mode === 'register'"
            v-model="form.name" 
            label="Full name" 
            placeholder="Adama Diallo" 
            autocomplete="name" 
            required 
            :error="auth.fieldErrors?.name"
          />

          <SaInput 
            v-model="form.email" 
            type="email" 
            label="Email" 
            placeholder="you@example.com"
            autocomplete="email" 
            required 
            :error="auth.fieldErrors?.email"
          />
          
          <SaInput 
            v-model="form.password" 
            type="password" 
            label="Password" 
            :placeholder="mode === 'register' ? 'Min. 8 chars with a number' : '••••••••'" 
            autocomplete="current-password" 
            required 
            :error="auth.fieldErrors?.password"
          />
          
          <!-- Forgot Password only on Login -->
          <div v-if="mode === 'login'" class="text-right mt-1">
            <router-link to="/forgot-password" @click="closeModal" class="text-xs font-medium text-gray-500 hover:text-black transition-colors">
              Forgot password?
            </router-link>
          </div>

          <SaButton type="submit" variant="primary" class="rounded-full mt-2 w-full justify-center" :loading="auth.loading">
            {{ mode === 'register' ? 'Create account' : 'Sign in' }}
          </SaButton>

          <!-- Back button if they were in the social login flow -->
          <button v-if="mode === 'login'" type="button" @click="showEmailForm = false" class="text-sm text-gray-500 hover:text-black mt-2 transition-colors cursor-pointer">
            ← Back to all options
          </button>
        </form>

        <!-- Toggle between Login and Register -->
        <div class="mt-8 text-sm text-gray-500">
          <template v-if="mode === 'login'">
            New user? 
            <button @click="toggleMode('register')" class="font-bold text-black hover:underline cursor-pointer">
              Sign up
            </button>
          </template>
          <template v-else>
            Already have an account? 
            <button @click="toggleMode('login')" class="font-bold text-black hover:underline cursor-pointer">
              Sign in
            </button>
          </template>
        </div>

      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import SaInput from '@/components/ui/SaInput.vue'
import SaButton from '@/components/ui/SaButton.vue'

const props = defineProps<{ 
  isOpen: boolean, 
  initialMode?: 'login' | 'register' 
}>()

const emit = defineEmits(['close'])

const router = useRouter()
const auth = useAuthStore()

// State
const mode = ref<'login' | 'register'>('login')
const showEmailForm = ref(false)

const form = reactive({ 
  name: '', 
  email: '', 
  password: '' 
})

// Handles redirect to Laravel Socialite backend endpoint
function loginWith(provider: 'google' | 'facebook') {
  window.location.href = `http://localhost:8000/api/v1/auth/${provider}/redirect`
}

// Reset and setup form when modal opens/closes
watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    mode.value = props.initialMode || 'login'
    // If opening in register mode, skip the social buttons
    showEmailForm.value = mode.value === 'register'
  } else {
    // Reset when closing
    showEmailForm.value = false
    form.name = ''
    form.email = ''
    form.password = ''
    auth.clearErrors()
  }
})

const toggleMode = (newMode: 'login' | 'register') => {
  auth.clearErrors()
  mode.value = newMode
  showEmailForm.value = true // Always show email form when toggling
}

const closeModal = () => {
  emit('close')
}

const submitAuth = async () => {
  try {
    if (mode.value === 'register') {
      await auth.register({ name: form.name, email: form.email, password: form.password })
    } else {
      await auth.login({ email: form.email, password: form.password })
    }
    
    closeModal()
    router.push('/dashboard')
  } catch (e) {
    console.error('Authentication failed')
  }
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
.modal-enter-active .bg-white, .modal-leave-active .bg-white {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.modal-enter-from .bg-white, .modal-leave-to .bg-white {
  transform: scale(0.95) translateY(10px);
}
</style>