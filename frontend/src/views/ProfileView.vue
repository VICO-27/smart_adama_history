<script setup lang="ts">
import { ref, onMounted, shallowRef, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppShell from '@/components/layout/AppShell.vue'
import SaCard from '@/components/ui/SaCard.vue'

const router = useRouter()
const auth = useAuthStore()

// --- STATE ---

const formData = ref({
  name: auth.user?.name || '',
  notify_badges: auth.user?.notify_badges ?? true,
})

const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

const fileInputRef = ref<HTMLInputElement | null>(null)
const profileMessage = ref('')
const securityMessage = ref({ text: '', type: '' })
const isSavingSecurity = ref(false)

// --- ACTIONS ---

async function saveProfile() {
  profileMessage.value = ''
  try {
    await auth.updateProfile({ 
      name: formData.value.name,
      notify_badges: formData.value.notify_badges 
    })
    profileMessage.value = 'Profile updated.'
    setTimeout(() => { profileMessage.value = '' }, 3000)
  } catch (error) {
    // Handled by auth store
  }
}

async function updatePassword() {
  isSavingSecurity.value = true
  securityMessage.value = { text: '', type: '' }
  
  // NOTE: You will need to add authApi.changePassword() to your api/auth.ts
  // For now, this simulates the network request.
  setTimeout(() => {
    if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
      securityMessage.value = { text: 'New passwords do not match.', type: 'error' }
    } else {
      securityMessage.value = { text: 'Password updated successfully.', type: 'success' }
      passwordForm.value = { current_password: '', new_password: '', new_password_confirmation: '' }
    }
    isSavingSecurity.value = false
    setTimeout(() => { securityMessage.value = { text: '', type: '' } }, 4000)
  }, 1000)
}

async function handleFileSelected(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    try {
      await auth.uploadAvatar(target.files[0])
      profileMessage.value = 'Avatar updated!'
      setTimeout(() => { profileMessage.value = '' }, 3000)
    } catch (error) {
      // Handled by auth store
    }
  }
}

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}

async function handleDeleteAccount() {
  if (window.confirm('Are you absolutely sure? This action cannot be undone and will erase all your progress and badges.')) {
    await auth.deleteAccount()
    router.push('/')
  }
}

// --- SPOTLIGHT EFFECT ---
let spotlightRaf: number | null = null
const lastPointerEvent = shallowRef<PointerEvent | null>(null)

function handleSpotlightMove(e: PointerEvent) {
  lastPointerEvent.value = e
  if (spotlightRaf) return
  spotlightRaf = requestAnimationFrame(() => {
    spotlightRaf = null
    const ev = lastPointerEvent.value
    if (!ev) return
    const target = ev.target as HTMLElement
    const card = target.closest('.spotlight-card') as HTMLElement | null
    if (!card) return
    const rect = card.getBoundingClientRect()
    const x = ((ev.clientX - rect.left) / rect.width) * 100
    const y = ((ev.clientY - rect.top) / rect.height) * 100
    card.style.setProperty('--spot-x', `${x}%`)
    card.style.setProperty('--spot-y', `${y}%`)
  })
}

onMounted(() => document.addEventListener('pointermove', handleSpotlightMove, { passive: true }))
onUnmounted(() => {
  document.removeEventListener('pointermove', handleSpotlightMove)
  if (spotlightRaf) cancelAnimationFrame(spotlightRaf)
})
</script>

<template>
  <AppShell>
    <!-- Reverted back to max-w-4xl to keep it centered and clean -->
    <div class="max-w-4xl mx-auto px-4 md:px-6 space-y-8 pb-24">
      
      <!-- Header -->
      <div class="fade-up pt-8" style="animation-delay: 0ms;">
        <h1 class="font-display text-4xl font-semibold text-[var(--sa-dark)]">Account Settings</h1>
        <p class="text-[var(--sa-taupe)] mt-2">Manage your personal information, security, and preferences.</p>
      </div>

      <!-- 1. Main Profile Card -->
      <SaCard class="spotlight-card fade-up relative overflow-hidden rounded-[2rem] p-8 md:p-10 border border-[var(--sa-gray)] bg-white" style="animation-delay: 100ms;">
        <div class="relative z-10 flex flex-col md:flex-row gap-10">
          
          <!-- Avatar Column -->
          <div class="flex flex-col items-center md:items-start gap-4">
            <div class="w-32 h-32 rounded-full overflow-hidden border border-[var(--sa-gray)] bg-gray-50 flex items-center justify-center relative group shadow-sm shrink-0">
              <img v-if="auth.user?.avatar_url" :src="auth.user.avatar_url" alt="Avatar" class="w-full h-full object-cover" />
              <span v-else class="text-4xl text-[var(--sa-taupe)]">{{ formData.name.charAt(0).toUpperCase() }}</span>
              
              <div @click="fileInputRef?.click()" class="absolute inset-0 bg-black/50 flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer backdrop-blur-sm">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <span class="text-xs font-medium">Update</span>
              </div>
            </div>
            <input type="file" ref="fileInputRef" class="hidden" accept="image/*" @change="handleFileSelected" />
            <p class="text-xs text-[var(--sa-taupe)] text-center md:text-left">JPG or PNG. Max 5MB.</p>
          </div>

          <!-- Form Column -->
          <form @submit.prevent="saveProfile" class="flex-1 space-y-6 w-full">
            <div class="space-y-1">
              <label class="text-sm font-semibold text-[var(--sa-dark)]">Full Name</label>
              <input 
                v-model="formData.name" 
                type="text" 
                class="w-full bg-gray-50 border border-[var(--sa-gray)] rounded-xl px-4 py-3 text-[var(--sa-dark)] focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-all"
              />
              <span v-if="auth.fieldErrors.name" class="text-red-500 text-xs mt-1 block">{{ auth.fieldErrors.name }}</span>
            </div>

            <div class="space-y-1">
              <label class="text-sm font-semibold text-[var(--sa-dark)]">Email Address</label>
              <input 
                :value="auth.user?.email" 
                type="email" 
                disabled
                class="w-full bg-gray-100 border border-[var(--sa-gray)] rounded-xl px-4 py-3 text-gray-500 cursor-not-allowed"
              />
              <p class="text-xs text-[var(--sa-taupe)] mt-1">Email cannot be changed.</p>
            </div>

            <div class="pt-2 flex items-center justify-between">
              <button 
                type="submit" 
                :disabled="auth.loading"
                class="bg-black text-white rounded-full px-8 py-3 font-semibold hover:bg-gray-800 transition-all active:scale-95 shadow-md disabled:opacity-50"
              >
                {{ auth.loading ? 'Saving...' : 'Save Changes' }}
              </button>
              <span v-if="profileMessage" class="text-emerald-500 text-sm font-medium">{{ profileMessage }}</span>
            </div>
          </form>

        </div>
      </SaCard>

      <!-- 2. Security Card -->
      <SaCard class="spotlight-card fade-up relative overflow-hidden rounded-[2rem] p-8 md:p-10 border border-[var(--sa-gray)] bg-white" style="animation-delay: 150ms;">
        <div class="relative z-10">
          <h3 class="font-display text-2xl font-semibold text-[var(--sa-dark)] mb-6">Security</h3>
          
          <form @submit.prevent="updatePassword" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-1 md:col-span-2">
              <label class="text-sm font-semibold text-[var(--sa-dark)]">Current Password</label>
              <input v-model="passwordForm.current_password" type="password" required class="w-full bg-gray-50 border border-[var(--sa-gray)] rounded-xl px-4 py-3 text-[var(--sa-dark)] focus:outline-none focus:ring-2 focus:ring-black transition-all max-w-md" />
            </div>
            
            <div class="space-y-1">
              <label class="text-sm font-semibold text-[var(--sa-dark)]">New Password</label>
              <input v-model="passwordForm.new_password" type="password" required minlength="8" class="w-full bg-gray-50 border border-[var(--sa-gray)] rounded-xl px-4 py-3 text-[var(--sa-dark)] focus:outline-none focus:ring-2 focus:ring-black transition-all" />
            </div>
            
            <div class="space-y-1">
              <label class="text-sm font-semibold text-[var(--sa-dark)]">Confirm New Password</label>
              <input v-model="passwordForm.new_password_confirmation" type="password" required minlength="8" class="w-full bg-gray-50 border border-[var(--sa-gray)] rounded-xl px-4 py-3 text-[var(--sa-dark)] focus:outline-none focus:ring-2 focus:ring-black transition-all" />
            </div>

            <div class="md:col-span-2 pt-2 flex items-center justify-between">
              <button type="submit" :disabled="isSavingSecurity" class="border-2 border-black text-black rounded-full px-8 py-3 font-bold hover:bg-black hover:text-white transition-all active:scale-95 disabled:opacity-50">
                {{ isSavingSecurity ? 'Updating...' : 'Update Password' }}
              </button>
              <span v-if="securityMessage.text" class="text-sm font-medium" :class="securityMessage.type === 'error' ? 'text-red-500' : 'text-emerald-500'">
                {{ securityMessage.text }}
              </span>
            </div>
          </form>
        </div>
      </SaCard>

      <!-- 3. Preferences & Danger Zone -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 fade-up" style="animation-delay: 200ms;">
        
        <!-- Preferences (Restored layout, no language selector) -->
        <SaCard class="spotlight-card rounded-[2rem] p-8 border border-[var(--sa-gray)] bg-white h-full">
          <div class="relative z-10">
            <h3 class="font-display text-xl font-semibold text-[var(--sa-dark)] mb-6">Preferences</h3>
            
            <label class="flex items-center justify-between cursor-pointer group">
              <div>
                <p class="font-medium text-[var(--sa-dark)]">Badge Notifications</p>
                <p class="text-sm text-[var(--sa-taupe)]">Show popups when I earn a new badge</p>
              </div>
              <!-- Custom Toggle -->
              <div class="relative w-12 h-6 rounded-full transition-colors duration-300" :class="formData.notify_badges ? 'bg-black' : 'bg-[var(--sa-gray)]'">
                <input type="checkbox" v-model="formData.notify_badges" class="sr-only" @change="saveProfile" />
                <div class="absolute left-1 top-1 w-4 h-4 rounded-full bg-white transition-transform duration-300 shadow-sm" :class="formData.notify_badges ? 'translate-x-6' : 'translate-x-0'"></div>
              </div>
            </label>
          </div>
        </SaCard>

        <!-- Account Actions (Restored red button layout) -->
        <SaCard class="spotlight-card rounded-[2rem] p-8 border border-[var(--sa-gray)] bg-white h-full">
          <div class="relative z-10 flex flex-col h-full justify-between">
            <div>
              <h3 class="font-display text-xl font-semibold text-[var(--sa-dark)] mb-2">Account Actions</h3>
              <p class="text-sm text-[var(--sa-taupe)] mb-6">Manage your session or permanently delete your data.</p>
            </div>
            
            <div class="space-y-4">
              <button @click="handleLogout" class="w-full border-2 border-[var(--sa-gray)] text-[var(--sa-dark)] rounded-xl px-4 py-3 font-semibold hover:border-black hover:bg-gray-50 transition-all">
                Sign Out
              </button>
              <button @click="handleDeleteAccount" class="w-full bg-red-50 text-red-600 rounded-xl px-4 py-3 font-semibold hover:bg-red-100 transition-all">
                Delete Account
              </button>
            </div>
          </div>
        </SaCard>

      </div>
    </div>
  </AppShell>
</template>

<style scoped>
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: translateY(0); }
}
.fade-up {
  animation: fadeUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.spotlight-card {
  position: relative;
  --spot-x: 50%;
  --spot-y: 50%;
}
.spotlight-card::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: inherit;
  background: radial-gradient(500px circle at var(--spot-x) var(--spot-y), rgba(0, 0, 0, 0.03), transparent 45%);
  opacity: 0;
  transition: opacity 0.4s ease;
  pointer-events: none;
  z-index: 1;
}
.spotlight-card:hover::before {
  opacity: 1;
}

@media (prefers-reduced-motion: reduce) {
  .fade-up {
    animation: none !important;
  }
}
</style>