<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, shallowRef } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useProgressStore } from '@/stores/progress'
import { useChatStore } from '@/stores/chat'
import AppShell from '@/components/layout/AppShell.vue'
import SaCard from '@/components/ui/SaCard.vue'
import { useI18n } from 'vue-i18n'

const auth = useAuthStore()
const progress = useProgressStore()
const chatStore = useChatStore()
const { t } = useI18n()

const d = computed(() => progress.dashboard)
const hour = new Date().getHours()
const firstName = computed(() => auth.user?.name?.split(' ')[0] || 'Ashenafi')

const timeGreeting = computed(() => {
  if (hour < 5) return t('dashboard.gn')
  if (hour < 12) return t('dashboard.gm')
  if (hour < 17) return t('dashboard.ga')
  if (hour < 21) return t('dashboard.ge')
  return t('dashboard.gn')
})

const hasChapters = computed(() => !!d.value && d.value.total_chapters > 0)
const isBookComplete = computed(() => {
  return !!d.value && d.value.total_chapters > 0 && d.value.completed_chapters >= d.value.total_chapters
})

const continueTitle = computed(() => {
  if (!hasChapters.value) return t('dashboard.book_ready')
  if (isBookComplete.value) return t('dashboard.book_done')
  const next = Math.min((d.value?.completed_chapters ?? 0) + 1, d.value?.total_chapters ?? 0)
  return t('dashboard.chap_of', { next, total: d.value?.total_chapters })
})

const continueSubtext = computed(() => {
  if (!hasChapters.value) return t('dashboard.sub_ready')
  if (isBookComplete.value) return t('dashboard.sub_done')
  if (d.value?.current_streak && d.value.current_streak > 0) return t('dashboard.sub_streak', { streak: d.value.current_streak })
  return t('dashboard.sub_normal')
})

const continueButtonLabel = computed(() => {
  if (!hasChapters.value) return t('dashboard.btn_study')
  if (isBookComplete.value) return t('dashboard.btn_revisit')
  return t('dashboard.btn_continue')
})

const chapterProgressPct = computed(() => {
  if (!d.value || !d.value.total_chapters) return 0
  return Math.min(100, (d.value.completed_chapters / d.value.total_chapters) * 100)
})

const streakSubtext = computed(() => {
  if (!d.value) return ''
  return d.value.current_streak > 0 ? t('dashboard.streak_sub1') : t('dashboard.streak_sub2')
})

const quizSubtext = computed(() => {
  if (!d.value) return ''
  return d.value.quizzes_passed > 0 ? t('dashboard.quiz_sub1') : t('dashboard.quiz_sub2')
})

const badgesSubtext = computed(() => {
  if (!d.value) return ''
  return d.value.earned_badge_count > 0 ? t('dashboard.badge_sub1') : t('dashboard.badge_sub2')
})

function formatChatTime(dateStr: string | undefined): string {
  if (!dateStr) return 'Recently'
  const date = new Date(dateStr)
  const diffHours = Math.floor((Date.now() - date.getTime()) / (1000 * 60 * 60))
  if (diffHours < 1) return 'Just now'
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffHours < 48) return 'Yesterday'
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

const liveRecentChats = computed(() => {
  const sessions = chatStore.sessions || []
  return sessions.slice(0, 3).map((session: any) => ({
    id: session.id,
    topic: session.title || 'Smart Adama AI Session',
    time: formatChatTime(session.updated_at || session.created_at),
    icon: '💬'
  }))
})

const dailyChallenge = ref({
  question: 'Which core pillar focuses on supporting startups and local digital economic growth?',
  options: ['e-Governance', 'Enterprise', 'Innovation'],
  correctIndex: 1,
  answered: false,
  selected: null as number | null
})

const newsUpdates = ref([
  { id: 1, date: 'Today', title: 'Chapter 4: Food Security is now fully available.', isNew: true },
  { id: 2, date: 'Aug 1', title: 'Smart Adama Ecosystem Beta officially launched.', isNew: false },
])

const answerChallenge = (index: number) => {
  if (dailyChallenge.value.answered) return
  dailyChallenge.value.selected = index
  dailyChallenge.value.answered = true
}

const defaultBadges = [
  { id: '1', icon: '📖', name: 'First Step', description: 'Read 1 chapter', earned: false, progress: { current: 0, required: 1 } },
  { id: '2', icon: '🎯', name: 'Committed Learner', description: 'Read 5 chapters', earned: false, progress: { current: 0, required: 5 } },
  { id: '3', icon: '💯', name: 'Perfectionist', description: 'Score 100% on a quiz', earned: false, progress: null },
  { id: '4', icon: '🔥', name: 'On a Roll', description: 'Maintain a 3-day streak', earned: false, progress: { current: 0, required: 3 } },
  { id: '5', icon: '⚡', name: 'Week Warrior', description: 'Maintain a 7-day streak', earned: false, progress: { current: 0, required: 7 } },
  { id: '6', icon: '🏆', name: 'Unstoppable', description: 'Maintain a 30-day streak', earned: false, progress: { current: 0, required: 30 } },
  { id: '7', icon: '🌟', name: 'Smart Adama Master', description: 'Complete all 12 chapters', earned: false, progress: { current: 0, required: 12 } },
  { id: '8', icon: '🎓', name: 'Quiz Ace', description: 'Pass 10 quizzes', earned: false, progress: { current: 0, required: 10 } }
]

const badgeColors = [
  { bg: 'bg-blue-50', text: 'text-blue-500', border: 'border-blue-100', bar: 'bg-blue-400', grad: 'from-blue-400 to-blue-600' },
  { bg: 'bg-emerald-50', text: 'text-emerald-500', border: 'border-emerald-100', bar: 'bg-emerald-400', grad: 'from-emerald-400 to-emerald-600' },
  { bg: 'bg-purple-50', text: 'text-purple-500', border: 'border-purple-100', bar: 'bg-purple-400', grad: 'from-purple-400 to-purple-600' },
  { bg: 'bg-orange-50', text: 'text-orange-500', border: 'border-orange-100', bar: 'bg-orange-400', grad: 'from-orange-400 to-orange-600' },
  { bg: 'bg-amber-50', text: 'text-amber-500', border: 'border-amber-100', bar: 'bg-amber-400', grad: 'from-amber-400 to-amber-600' },
  { bg: 'bg-rose-50', text: 'text-rose-500', border: 'border-rose-100', bar: 'bg-rose-400', grad: 'from-rose-400 to-rose-600' },
  { bg: 'bg-indigo-50', text: 'text-indigo-500', border: 'border-indigo-100', bar: 'bg-indigo-400', grad: 'from-indigo-400 to-indigo-600' },
  { bg: 'bg-cyan-50', text: 'text-cyan-500', border: 'border-cyan-100', bar: 'bg-cyan-400', grad: 'from-cyan-400 to-cyan-600' }
]

const displayBadges = computed(() => {
  const source = (progress.badges && progress.badges.length > 0) ? progress.badges : defaultBadges
  return source.map((badge, index) => ({
    ...badge,
    theme: badgeColors[index % badgeColors.length]
  }))
})

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
  chatStore.loadSessions(1)
  document.addEventListener('pointermove', handleSpotlightMove, { passive: true })
})

onUnmounted(() => {
  document.removeEventListener('pointermove', handleSpotlightMove)
  if (spotlightRaf) cancelAnimationFrame(spotlightRaf)
})
</script>

<template>
  <AppShell>
    <div class="max-w-6xl mx-auto px-4 md:px-6 space-y-6 pb-24 mt-16">

      <SaCard
        v-if="d"
        class="spotlight-card spring-hover fade-up relative overflow-hidden rounded-[2.5rem] px-6 py-10 md:px-12 md:py-14 bg-gradient-to-br from-emerald-500 to-emerald-700 text-white shadow-xl border-transparent"
        style="animation-delay: 0ms;"
      >
        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-12">
          
          <div class="flex-1">
            <p class="text-sm font-bold text-emerald-200 tracking-wide uppercase mb-2">{{ timeGreeting }}</p>
            <h1 class="font-display text-4xl md:text-5xl font-semibold text-white leading-tight mb-8">
              {{ $t('dashboard.welcome') }}, {{ firstName }}
            </h1>
            
            <div class="mb-8">
              <p class="text-lg font-bold text-white">{{ continueTitle }}</p>
              <p class="text-base text-emerald-50 mt-1 max-w-md">{{ continueSubtext }}</p>
              <div v-if="hasChapters" class="mt-4 h-1.5 w-full max-w-xs rounded-full bg-emerald-900/30 overflow-hidden shadow-inner">
                <div class="h-full rounded-full bg-white transition-[width] duration-700 ease-out" :style="`width: ${chapterProgressPct}%`" />
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-4">
              <RouterLink to="/study" class="inline-flex items-center gap-2 rounded-full px-8 py-3.5 font-bold text-sm md:text-base bg-white text-emerald-700 hover:bg-emerald-50 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-300">
                {{ continueButtonLabel }}
                <span aria-hidden="true">→</span>
              </RouterLink>
              <a href="/smart-adama-book.pdf" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full px-8 py-3.5 font-bold text-sm md:text-base bg-transparent border-2 border-emerald-300/50 hover:border-white text-white hover:bg-white/10 hover:-translate-y-0.5 transition-all duration-300">
                {{ $t('dashboard.btn_pdf') }}
              </a>
            </div>
          </div>

          <div class="flex items-center gap-6 lg:border-l lg:border-emerald-400/40 lg:pl-12">
            <div class="relative flex items-center justify-center w-[100px] h-[100px] shrink-0">
              <svg class="w-full h-full transform -rotate-90 drop-shadow-md" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="42" fill="transparent" stroke="rgba(255,255,255,0.2)" stroke-width="8" />
                <circle cx="50" cy="50" r="42" fill="transparent" stroke="white" stroke-width="8" stroke-linecap="round"
                        stroke-dasharray="263.89" :stroke-dashoffset="263.89 - (263.89 * Math.min(100, d.completion_pct || 0)) / 100"
                        class="transition-all duration-1000 ease-out" />
              </svg>
              <div class="absolute flex flex-col items-center justify-center">
                <span class="text-xl font-bold text-white leading-none">{{ d.completion_pct || 0 }}%</span>
                <span class="text-[9px] font-bold uppercase tracking-wider text-emerald-100 mt-1">{{ $t('dashboard.pct_complete') }}</span>
              </div>
            </div>

            <div>
              <p class="text-4xl font-display font-semibold text-white drop-shadow-sm leading-none">
                {{ d.completed_chapters || 0 }}<span class="text-xl font-normal text-emerald-200">/{{ d.total_chapters || 0 }}</span>
              </p>
              <p class="text-sm font-medium text-emerald-100 mt-2">{{ $t('dashboard.chaps_complete') }}</p>
            </div>
          </div>
        </div>
        
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-white opacity-10 rounded-full blur-3xl pointer-events-none"></div>
      </SaCard>
      
      <div v-else class="h-64 rounded-[2.5rem] bg-brand-100 animate-pulse" />

      <div v-if="d" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="spotlight-card spring-hover fade-up flex items-center gap-5 px-6 py-6 bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200 rounded-3xl shadow-sm cursor-default" style="animation-delay: 100ms;">
          <div class="w-14 h-14 rounded-2xl bg-white shadow-sm flex items-center justify-center shrink-0 border border-orange-100">
             <span class="text-3xl drop-shadow-sm" :class="{ 'flame-pulse': d.current_streak > 0 }">🔥</span>
          </div>
          <div>
            <p class="text-3xl font-display font-bold text-orange-600 leading-none">{{ d.current_streak }}</p>
            <p class="text-[11px] font-extrabold text-orange-500/80 uppercase tracking-widest mt-1 mb-0.5">{{ $t('dashboard.streak_label') }}</p>
            <p class="text-xs font-medium text-orange-600/70 leading-snug">{{ streakSubtext }}</p>
          </div>
        </div>

        <div class="spotlight-card spring-hover fade-up flex items-center gap-5 px-6 py-6 bg-gradient-to-br from-indigo-50 to-blue-100 border border-indigo-200 rounded-3xl shadow-sm cursor-default" style="animation-delay: 200ms;">
          <div class="w-14 h-14 rounded-2xl bg-white shadow-sm flex items-center justify-center shrink-0 border border-indigo-100">
             <span class="text-3xl drop-shadow-sm">🧠</span>
          </div>
          <div>
            <p class="text-3xl font-display font-bold text-indigo-600 leading-none">
              {{ d.average_quiz_score !== null ? `${d.average_quiz_score}%` : '—' }}
            </p>
            <p class="text-[11px] font-extrabold text-indigo-500/80 uppercase tracking-widest mt-1 mb-0.5">{{ $t('dashboard.quiz_label') }}</p>
            <p class="text-xs font-medium text-indigo-600/70 leading-snug">{{ quizSubtext }}</p>
          </div>
        </div>

        <div class="spotlight-card spring-hover fade-up flex items-center gap-5 px-6 py-6 bg-gradient-to-br from-fuchsia-50 to-pink-100 border border-fuchsia-200 rounded-3xl shadow-sm cursor-default" style="animation-delay: 300ms;">
          <div class="w-14 h-14 rounded-2xl bg-white shadow-sm flex items-center justify-center shrink-0 border border-fuchsia-100">
             <span class="text-3xl drop-shadow-sm">🎖️</span>
          </div>
          <div>
            <p class="text-3xl font-display font-bold text-fuchsia-600 leading-none">{{ d.earned_badge_count }}</p>
            <p class="text-[11px] font-extrabold text-fuchsia-500/80 uppercase tracking-widest mt-1 mb-0.5">{{ $t('dashboard.badge_label') }}</p>
            <p class="text-xs font-medium text-fuchsia-600/70 leading-snug">{{ badgesSubtext }}</p>
          </div>
        </div>
      </div>

      <section v-if="d" class="fade-up pt-4" style="animation-delay: 400ms;">
        <h2 class="font-display font-semibold text-brand-500 mb-1">{{ $t('dashboard.badges_title') }}</h2>
        <p class="text-sm text-brand-400 mb-6">{{ $t('dashboard.badges_desc') }}</p>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3 sm:gap-4">
          <div
            v-for="b in displayBadges"
            :key="b.id"
            :title="b.description"
            class="relative spotlight-card spring-hover flex flex-col items-center px-2 py-4 sm:py-5 rounded-[1.5rem] text-center w-full overflow-hidden transition-all duration-300"
            :class="b.earned ? `bg-gradient-to-br ${b.theme.grad} border-transparent shadow-lg text-white scale-[1.02]` : `bg-white border ${b.theme.border}`"
          >
            <div v-if="!b.earned" class="absolute inset-0 opacity-[0.15] pointer-events-none" :class="b.theme.bg"></div>
            <div class="w-12 h-12 flex items-center justify-center rounded-2xl mb-3 text-[1.6rem] shadow-sm transition-transform group-hover:scale-110 z-10 shrink-0" :class="b.earned ? 'bg-white/20' : `${b.theme.bg} ${b.theme.text}`" :style="!b.earned ? 'filter: grayscale(0.5);' : ''">
              {{ b.icon }}
            </div>
            <span class="text-[11px] sm:text-xs font-bold leading-snug mb-1.5 z-10 px-1" :class="b.earned ? 'text-white' : 'text-brand-500'">{{ b.name }}</span>
            <span class="text-[9px] font-bold uppercase tracking-wider mb-2 z-10 shrink-0" :class="b.earned ? 'text-white/80' : 'text-brand-300'">
              <template v-if="b.earned">{{ $t('dashboard.unlocked') }}</template>
              <template v-else-if="b.progress">{{ b.progress.current }} / {{ b.progress.required }}</template>
              <template v-else>{{ $t('dashboard.locked') }}</template>
            </span>
            <div v-if="!b.earned && b.progress" class="w-10/12 sm:w-full max-w-[80%] bg-brand-50 rounded-full h-1.5 mt-auto z-10 overflow-hidden shadow-inner shrink-0">
              <div class="h-full rounded-full transition-all duration-700 ease-out" :class="b.theme.bar" :style="`width: ${Math.min(100, (b.progress.current / b.progress.required) * 100)}%`" />
            </div>
          </div>
        </div>
      </section>

      <div v-if="d" class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-4 fade-up" style="animation-delay: 500ms;">
        <SaCard class="spotlight-card spring-hover flex flex-col p-8 rounded-[2rem] bg-gradient-to-br from-brand-50 to-brand-100/40 border border-brand-200 shadow-sm">
          <div class="flex items-center justify-between mb-6">
            <h3 class="font-display font-semibold text-brand-500 text-xl">{{ $t('dashboard.ai_chats') }}</h3>
            <RouterLink to="/study" class="text-sm font-semibold text-brand-400 hover:text-brand-500 transition-colors">{{ $t('dashboard.view_all') }}</RouterLink>
          </div>
          
          <div v-if="liveRecentChats.length > 0" class="flex flex-col gap-3">
            <RouterLink 
              v-for="chat in liveRecentChats" :key="chat.id" to="/study"
              class="group flex items-center justify-between p-4 bg-white rounded-2xl border border-brand-100 shadow-sm hover:border-brand-400 hover:shadow-md transition-all cursor-pointer"
            >
              <div class="flex items-center gap-4">
                <span class="text-2xl opacity-80 group-hover:scale-110 transition-transform">{{ chat.icon }}</span>
                <div class="flex flex-col">
                  <span class="font-medium text-brand-500 text-sm line-clamp-1">{{ chat.topic }}</span>
                  <span class="text-xs text-brand-400">{{ chat.time }}</span>
                </div>
              </div>
              <span class="text-brand-400 group-hover:text-brand-500 group-hover:translate-x-1 transition-all">→</span>
            </RouterLink>
          </div>

          <div v-else class="flex flex-col items-center justify-center py-10 border border-dashed border-brand-300 rounded-2xl text-center px-4 bg-white/50">
            <span class="text-3xl mb-2">💬</span>
            <p class="text-sm font-medium text-brand-500">{{ $t('dashboard.no_chats') }}</p>
            <p class="text-xs text-brand-400 mt-1 mb-4">{{ $t('dashboard.no_chats_sub') }}</p>
            <RouterLink to="/study" class="px-4 py-2 bg-brand-400 text-white rounded-full text-xs font-bold hover:bg-brand-500 shadow-md transition-colors">
              {{ $t('dashboard.start_chat') }}
            </RouterLink>
          </div>
        </SaCard>

        <div class="flex flex-col gap-6">
          <SaCard class="spotlight-card spring-hover flex flex-col p-8 rounded-[2rem] bg-gradient-to-br from-brand-400 to-brand-500 text-white border-transparent relative overflow-hidden shadow-md">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
              <div class="flex items-center gap-2">
                <h3 class="font-display font-semibold text-white text-xl">{{ $t('dashboard.daily_challenge') }}</h3>
                <span class="px-2.5 py-0.5 rounded-full bg-amber-400/20 text-amber-300 border border-amber-400/30 text-[10px] font-bold uppercase tracking-wider">
                  {{ $t('dashboard.coming_soon') }}
                </span>
              </div>
              <span class="px-3 py-1 rounded-full bg-white/20 text-xs font-bold text-white uppercase tracking-wider">+10 XP</span>
            </div>

            <p class="text-white font-medium text-sm md:text-base leading-relaxed mb-6">{{ dailyChallenge.question }}</p>
            
            <div class="flex flex-col gap-2.5">
              <button 
                v-for="(option, index) in dailyChallenge.options" :key="index" @click="answerChallenge(index)" :disabled="dailyChallenge.answered"
                class="w-full text-left p-4 rounded-xl text-sm font-semibold transition-all duration-300 border shadow-sm"
                :class="[
                  !dailyChallenge.answered ? 'border-white/20 bg-white/10 hover:bg-white/20 text-white cursor-pointer' : '',
                  dailyChallenge.answered && index === dailyChallenge.correctIndex ? 'border-emerald-400 bg-emerald-500/30 text-emerald-100 font-bold' : '',
                  dailyChallenge.answered && index !== dailyChallenge.correctIndex && index === dailyChallenge.selected ? 'border-rose-400 bg-red-500/30 text-rose-100' : '',
                  dailyChallenge.answered && index !== dailyChallenge.correctIndex && index !== dailyChallenge.selected ? 'border-white/10 bg-transparent text-white/40' : ''
                ]"
              >
                {{ option }}
                <span v-if="dailyChallenge.answered && index === dailyChallenge.correctIndex" class="float-right text-emerald-300 font-bold">✓</span>
                <span v-if="dailyChallenge.answered && index === dailyChallenge.selected && index !== dailyChallenge.correctIndex" class="float-right text-rose-300 font-bold">✗</span>
              </button>
            </div>
          </SaCard>

          <SaCard class="spotlight-card spring-hover flex flex-col p-8 rounded-[2rem] bg-gradient-to-b from-brand-50 to-brand-100/30 border border-brand-200 flex-1 shadow-sm">
            <h3 class="font-display font-semibold text-brand-500 text-xl mb-6">{{ $t('dashboard.ecosystem') }}</h3>
            <div class="flex flex-col gap-4">
              <div v-for="news in newsUpdates" :key="news.id" class="flex gap-4 items-start p-3 bg-white border border-brand-100 rounded-xl shadow-sm">
                <div class="mt-1">
                  <div v-if="news.isNew" class="w-2 h-2 rounded-full bg-brand-400 animate-pulse"></div>
                  <div v-else class="w-2 h-2 rounded-full bg-brand-200"></div>
                </div>
                <div class="flex flex-col">
                  <span class="text-sm font-medium text-brand-500 leading-snug">{{ news.title }}</span>
                  <span class="text-xs text-brand-400 mt-1">{{ news.date }}</span>
                </div>
              </div>
            </div>
          </SaCard>
        </div>
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

@keyframes flamePulse {
  0%, 100% { filter: drop-shadow(0 0 0 rgba(0, 0, 0, 0)); transform: scale(1); }
  50% { filter: drop-shadow(0 0 8px rgba(251, 146, 60, 0.4)); transform: scale(1.08); }
}
.flame-pulse {
  display: inline-block;
  animation: flamePulse 2.4s ease-in-out infinite;
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
  background: radial-gradient(420px circle at var(--spot-x) var(--spot-y), rgba(255, 255, 255, 0.15), transparent 45%);
  opacity: 0;
  transition: opacity 0.4s ease;
  pointer-events: none;
  z-index: 1;
}
.spotlight-card:hover::before {
  opacity: 1;
}

.spring-hover {
  transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1),
              box-shadow 0.45s ease-out,
              border-color 0.45s ease-out;
}
.spring-hover:hover {
  transform: translateY(-4px);
}

@media (prefers-reduced-motion: reduce) {
  .fade-up, .flame-pulse { animation: none !important; }
  .spring-hover, .spotlight-card::before { transition: none !important; }
  .spring-hover:hover { transform: none; }
}
</style>