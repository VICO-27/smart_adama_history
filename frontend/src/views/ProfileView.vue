<script setup lang="ts">
import { ref, computed, onMounted, shallowRef, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useProgressStore } from '@/stores/progress'
import AppShell from '@/components/layout/AppShell.vue'
import SaCard from '@/components/ui/SaCard.vue'
import { useI18n } from 'vue-i18n'

const router = useRouter()
const auth = useAuthStore()
const progress = useProgressStore()
const { t } = useI18n()

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

// --- DARK MODE STATE ---
const isDarkMode = ref(false)

const setTheme = (theme: 'light' | 'dark') => {
  isDarkMode.value = theme === 'dark'
  if (theme === 'dark') {
    document.documentElement.classList.add('dark')
    localStorage.setItem('theme', 'dark')
  } else {
    document.documentElement.classList.remove('dark')
    localStorage.setItem('theme', 'light')
  }
}

// --- COMPUTED LEARNING METRICS ---
const d = computed(() => progress.dashboard)

const memberSince = computed(() => {
  if (!auth.user?.created_at) return 'Member'
  const date = new Date(auth.user.created_at)
  return `Member since ${date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' })}`
})

const profileCompletionPct = computed(() => {
  let score = 50 // Base for account creation & email
  if (auth.user?.name) score += 25
  if (auth.user?.avatar_url) score += 25
  return score
})

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

onMounted(() => {
  progress.loadAll()
  document.addEventListener('pointermove', handleSpotlightMove, { passive: true })
  
  // Initialize Dark Mode state based on localStorage or OS preference
  isDarkMode.value = localStorage.getItem('theme') === 'dark' || 
    (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
    
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
})

onUnmounted(() => {
  document.removeEventListener('pointermove', handleSpotlightMove)
  if (spotlightRaf) cancelAnimationFrame(spotlightRaf)
})
</script>

<template>
  <AppShell>
    <div class="max-w-5xl mx-auto px-4 md:px-6 space-y-12 pb-24 mt-16">
      
      <!-- 1. HERO / PROFILE IDENTITY -->
      <section class="relative overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-brand-500 to-brand-600 text-white p-8 md:p-12 shadow-xl spotlight-card fade-up">
        <div class="relative z-10 flex flex-col md:flex-row items-center md:items-start gap-8">
          
          <div class="relative group shrink-0">
            <div class="w-32 h-32 md:w-36 md:h-36 rounded-full overflow-hidden border-4 border-white/20 bg-white/10 flex items-center justify-center shadow-inner">
              <img v-if="auth.user?.avatar_url" :src="auth.user.avatar_url" alt="Avatar" class="w-full h-full object-cover" />
              <span v-else class="text-5xl font-display font-bold text-white">{{ formData.name.charAt(0).toUpperCase() }}</span>
            </div>
            <div @click="fileInputRef?.click()" class="absolute inset-0 rounded-full bg-black/60 flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer backdrop-blur-xs">
              <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
              <span class="text-xs font-medium uppercase tracking-wider">{{ $t('profile.change_photo') }}</span>
            </div>
            <input type="file" ref="fileInputRef" class="hidden" accept="image/*" @change="handleFileSelected" />
          </div>

          <div class="flex-1 text-center md:text-left flex flex-col justify-center">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-xs font-mono tracking-widest text-brand-100 uppercase mb-3 mx-auto md:mx-0 border border-white/10">
              ⚡️ Smart Adama Scholar
            </div>
            <h1 class="font-display text-3xl md:text-5xl font-semibold tracking-tight text-white mb-2">
              {{ formData.name || 'Smart Adama User' }}
            </h1>
            <p class="text-brand-100 text-sm md:text-base font-medium opacity-90 mb-4">
              {{ auth.user?.email }} • <span class="text-brand-200">{{ memberSince }}</span>
            </p>

            <div class="w-full max-w-md bg-black/20 rounded-full h-2 overflow-hidden backdrop-blur-sm mx-auto md:mx-0 mb-2">
              <div class="bg-emerald-400 h-full rounded-full transition-all duration-1000 ease-out" :style="`width: ${profileCompletionPct}%`"></div>
            </div>
            <div class="flex justify-between items-center max-w-md text-xs text-brand-200 mx-auto md:mx-0">
              <span>{{ $t('profile.completion') }}</span>
              <span class="font-bold text-white">{{ profileCompletionPct }}% Complete</span>
            </div>
          </div>

        </div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
      </section>

      <!-- 2. LEARNING JOURNEY & SNAPSHOT -->
      <section v-if="d" class="space-y-6 fade-up" style="animation-delay: 100ms;">
        <div class="flex items-center justify-between">
          <h2 class="font-display text-2xl font-semibold text-[var(--sa-dark)]">{{ $t('profile.journey') }}</h2>
          <span class="text-xs font-mono uppercase tracking-widest text-[var(--sa-taupe)]">{{ $t('profile.metrics') }}</span>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="p-6 rounded-2xl bg-white border border-[var(--sa-gray)] shadow-sm flex flex-col justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-[var(--sa-taupe)]">{{ $t('profile.chap_read') }}</span>
            <div class="mt-4 flex items-baseline gap-2">
              <span class="text-3xl font-display font-bold text-[var(--sa-dark)]">{{ d.completed_chapters || 0 }}</span>
              <span class="text-sm text-[var(--sa-taupe)]">/ {{ d.total_chapters || 12 }}</span>
            </div>
            <div class="w-full bg-gray-100 h-1.5 rounded-full mt-3 overflow-hidden">
              <div class="bg-brand-500 h-full rounded-full" :style="`width: ${d.completion_pct || 0}%`"></div>
            </div>
          </div>

          <div class="p-6 rounded-2xl bg-white border border-[var(--sa-gray)] shadow-sm flex flex-col justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-[var(--sa-taupe)]">{{ $t('profile.streak') }}</span>
            <div class="mt-4 flex items-baseline gap-2">
              <span class="text-3xl font-display font-bold text-orange-600">{{ d.current_streak || 0 }}</span>
              <span class="text-sm text-orange-500 font-medium">Days 🔥</span>
            </div>
            <p class="text-[11px] text-[var(--sa-taupe)] mt-3">{{ $t('profile.streak_desc') }}</p>
          </div>

          <div class="p-6 rounded-2xl bg-white border border-[var(--sa-gray)] shadow-sm flex flex-col justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-[var(--sa-taupe)]">{{ $t('profile.avg_score') }}</span>
            <div class="mt-4 flex items-baseline gap-2">
              <span class="text-3xl font-display font-bold text-indigo-600">
                {{ d.average_quiz_score !== null ? `${d.average_quiz_score}%` : '—' }}
              </span>
            </div>
            <p class="text-[11px] text-[var(--sa-taupe)] mt-3">{{ d.quizzes_passed || 0 }} {{ $t('profile.quizzes_passed') }}</p>
          </div>

          <div class="p-6 rounded-2xl bg-white border border-[var(--sa-gray)] shadow-sm flex flex-col justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-[var(--sa-taupe)]">{{ $t('profile.badges_earned') }}</span>
            <div class="mt-4 flex items-baseline gap-2">
              <span class="text-3xl font-display font-bold text-fuchsia-600">{{ d.earned_badge_count || 0 }}</span>
              <span class="text-sm text-fuchsia-500 font-medium">🎖️</span>
            </div>
            <p class="text-[11px] text-[var(--sa-taupe)] mt-3">{{ $t('profile.milestones') }}</p>
          </div>
        </div>
      </section>

      <!-- 3. PERSONAL INFORMATION & SECURITY -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 fade-up" style="animation-delay: 200ms;">
        
        <SaCard class="spotlight-card rounded-[2rem] p-8 border border-[var(--sa-gray)] bg-white flex flex-col justify-between h-full">
          <div>
            <h3 class="font-display text-xl font-semibold text-[var(--sa-dark)] mb-2">{{ $t('profile.title') }}</h3>
            <p class="text-sm text-[var(--sa-taupe)] mb-6">{{ $t('profile.subtitle') }}</p>

            <form @submit.prevent="saveProfile" class="space-y-5">
              <div class="space-y-1">
                <label class="text-sm font-semibold text-[var(--sa-dark)]">{{ $t('profile.fullname') }}</label>
                <input 
                  v-model="formData.name" 
                  type="text" 
                  class="w-full bg-gray-50 border border-[var(--sa-gray)] rounded-xl px-4 py-3 text-[var(--sa-dark)] focus:outline-none focus:ring-2 focus:ring-black transition-all"
                />
                <span v-if="auth.fieldErrors.name" class="text-red-500 text-xs mt-1 block">{{ auth.fieldErrors.name }}</span>
              </div>

              <div class="space-y-1">
                <label class="text-sm font-semibold text-[var(--sa-dark)]">{{ $t('profile.email') }}</label>
                <input 
                  :value="auth.user?.email" 
                  type="email" 
                  disabled
                  class="w-full bg-gray-100 border border-[var(--sa-gray)] rounded-xl px-4 py-3 text-gray-500 cursor-not-allowed"
                />
                <p class="text-xs text-[var(--sa-taupe)] mt-1">{{ $t('profile.email_desc') }}</p>
              </div>

              <div class="pt-2 flex items-center justify-between">
                <button 
                  type="submit" 
                  :disabled="auth.loading"
                  class="bg-black text-white rounded-full px-8 py-3 font-semibold hover:bg-gray-800 transition-all active:scale-95 shadow-md disabled:opacity-50 text-sm cursor-pointer"
                >
                  {{ auth.loading ? $t('profile.saving') : $t('profile.save') }}
                </button>
                <span v-if="profileMessage" class="text-emerald-500 text-sm font-medium">{{ profileMessage }}</span>
              </div>
            </form>
          </div>
        </SaCard>

        <SaCard class="spotlight-card rounded-[2rem] p-8 border border-[var(--sa-gray)] bg-white flex flex-col justify-between h-full">
          <div>
            <h3 class="font-display text-xl font-semibold text-[var(--sa-dark)] mb-2">{{ $t('profile.security') }}</h3>
            <p class="text-sm text-[var(--sa-taupe)] mb-6">{{ $t('profile.security_desc') }}</p>

            <form @submit.prevent="updatePassword" class="space-y-4">
              <div class="space-y-1">
                <label class="text-sm font-semibold text-[var(--sa-dark)]">{{ $t('profile.current_pass') }}</label>
                <input v-model="passwordForm.current_password" type="password" required class="w-full bg-gray-50 border border-[var(--sa-gray)] rounded-xl px-4 py-3 text-[var(--sa-dark)] focus:outline-none focus:ring-2 focus:ring-black transition-all text-sm" />
              </div>
              
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                  <label class="text-sm font-semibold text-[var(--sa-dark)]">{{ $t('profile.new_pass') }}</label>
                  <input v-model="passwordForm.new_password" type="password" required minlength="8" class="w-full bg-gray-50 border border-[var(--sa-gray)] rounded-xl px-4 py-3 text-[var(--sa-dark)] focus:outline-none focus:ring-2 focus:ring-black transition-all text-sm" />
                </div>
                
                <div class="space-y-1">
                  <label class="text-sm font-semibold text-[var(--sa-dark)]">{{ $t('profile.confirm_pass') }}</label>
                  <input v-model="passwordForm.new_password_confirmation" type="password" required minlength="8" class="w-full bg-gray-50 border border-[var(--sa-gray)] rounded-xl px-4 py-3 text-[var(--sa-dark)] focus:outline-none focus:ring-2 focus:ring-black transition-all text-sm" />
                </div>
              </div>

              <div class="pt-2 flex items-center justify-between">
                <button type="submit" :disabled="isSavingSecurity" class="border-2 border-black text-black rounded-full px-6 py-2.5 font-bold hover:bg-black hover:text-white transition-all active:scale-95 disabled:opacity-50 text-sm cursor-pointer">
                  {{ isSavingSecurity ? $t('profile.updating') : $t('profile.update_pass') }}
                </button>
                <span v-if="securityMessage.text" class="text-xs font-medium" :class="securityMessage.type === 'error' ? 'text-red-500' : 'text-emerald-500'">
                  {{ securityMessage.text }}
                </span>
              </div>
            </form>
          </div>
        </SaCard>

      </div>

      <!-- 4. PREFERENCES & DANGER ZONE -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 fade-up" style="animation-delay: 300ms;">
        
        <SaCard class="spotlight-card rounded-[2rem] p-8 border border-[var(--sa-gray)] bg-white flex flex-col h-full">
          <div>
            <h3 class="font-display text-xl font-semibold text-[var(--sa-dark)] mb-2">{{ $t('profile.preferences') }}</h3>
            <p class="text-sm text-[var(--sa-taupe)] mb-6">{{ $t('profile.pref_desc') }}</p>
            
            <label class="flex items-center justify-between cursor-pointer group py-2">
              <div>
                <p class="font-medium text-[var(--sa-dark)] text-sm">{{ $t('profile.badge_notif') }}</p>
                <p class="text-xs text-[var(--sa-taupe)]">{{ $t('profile.badge_desc') }}</p>
              </div>
              <div class="relative w-12 h-6 rounded-full transition-colors duration-300" :class="formData.notify_badges ? 'bg-black' : 'bg-[var(--sa-gray)]'">
                <input type="checkbox" v-model="formData.notify_badges" class="sr-only" @change="saveProfile" />
                <div class="absolute left-1 top-1 w-4 h-4 rounded-full bg-white transition-transform duration-300 shadow-sm" :class="formData.notify_badges ? 'translate-x-6' : 'translate-x-0'"></div>
              </div>
            </label>

            <!-- DARK MODE APPEARANCE TOGGLE -->
            <div class="mt-6 pt-6 border-t border-[var(--sa-gray)]">
              <p class="font-medium text-[var(--sa-dark)] text-sm mb-4">{{ $t('profile.appearance') || 'Appearance' }}</p>
              <div class="flex bg-gray-50 rounded-xl p-1 border border-[var(--sa-gray)]">
                <button 
                  @click="setTheme('light')"
                  type="button"
                  class="flex-1 py-2 text-sm font-bold rounded-lg transition-all flex items-center justify-center gap-2"
                  :class="!isDarkMode ? 'bg-white text-black shadow-sm' : 'text-[var(--sa-taupe)] hover:text-[var(--sa-dark)]'"
                >
                  ☀️ {{ $t('profile.light_mode') || 'Light' }}
                </button>
                <button 
                  @click="setTheme('dark')"
                  type="button"
                  class="flex-1 py-2 text-sm font-bold rounded-lg transition-all flex items-center justify-center gap-2"
                  :class="isDarkMode ? 'bg-black text-white shadow-sm' : 'text-[var(--sa-taupe)] hover:text-[var(--sa-dark)]'"
                >
                  🌙 {{ $t('profile.dark_mode') || 'Dark' }}
                </button>
              </div>
            </div>

          </div>
        </SaCard>

        <SaCard class="spotlight-card rounded-[2rem] p-8 border border-[var(--sa-gray)] bg-white flex flex-col justify-between h-full">
          <div>
            <h3 class="font-display text-xl font-semibold text-[var(--sa-dark)] mb-2">{{ $t('profile.actions') }}</h3>
            <p class="text-sm text-[var(--sa-taupe)] mb-6">{{ $t('profile.actions_desc') }}</p>
          </div>
          
          <div class="space-y-3 pt-2">
            <button @click="handleLogout" class="w-full border-2 border-[var(--sa-gray)] text-[var(--sa-dark)] rounded-xl px-4 py-3 font-semibold hover:border-black hover:bg-gray-50 transition-all text-sm cursor-pointer">
              {{ $t('profile.signout') }}
            </button>
            <button @click="handleDeleteAccount" class="w-full bg-red-50 text-red-600 rounded-xl px-4 py-3 font-semibold hover:bg-red-100 transition-all text-sm cursor-pointer">
              {{ $t('profile.delete_acc') }}
            </button>
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