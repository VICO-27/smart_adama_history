<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, shallowRef } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useProgressStore } from '@/stores/progress'
import AppShell from '@/components/layout/AppShell.vue'
import SaCard from '@/components/ui/SaCard.vue'
import ProgressRing from '@/components/ui/ProgressRing.vue'

const auth = useAuthStore()
const progress = useProgressStore()

const d = computed(() => progress.dashboard)

const hour = new Date().getHours()
const firstName = computed(() => auth.user?.name?.split(' ')[0] || 'Ashenafi')

const timeGreeting = computed(() => {
  if (hour < 5) return 'Good night'
  if (hour < 12) return 'Good morning'
  if (hour < 17) return 'Good afternoon'
  if (hour < 21) return 'Good evening'
  return 'Good night'
})

const hasChapters = computed(() => !!d.value && d.value.total_chapters > 0)

const isBookComplete = computed(() => {
  return !!d.value && d.value.total_chapters > 0 && d.value.completed_chapters >= d.value.total_chapters
})

const continueTitle = computed(() => {
  if (!hasChapters.value) return 'Your book is on its way'
  if (isBookComplete.value) return "You've finished every chapter 🎉"
  const next = Math.min((d.value?.completed_chapters ?? 0) + 1, d.value?.total_chapters ?? 0)
  return `Chapter ${next} of ${d.value?.total_chapters}`
})

const continueSubtext = computed(() => {
  if (!hasChapters.value) return 'Chapters will appear here as soon as they\'re published — check back soon.'
  if (isBookComplete.value) return 'You\'ve read the whole Smart Adama Book. Revisit any chapter, anytime.'
  if (d.value?.current_streak && d.value.current_streak > 0) return `You're on a ${d.value.current_streak}-day streak — keep it going.`
  return 'Your progress saves automatically — jump back in whenever you\'re ready.'
})

const continueButtonLabel = computed(() => {
  if (!hasChapters.value) return 'Go to Study'
  if (isBookComplete.value) return 'Revisit the book'
  return 'Continue Reading'
})

const chapterProgressPct = computed(() => {
  if (!d.value || !d.value.total_chapters) return 0
  return Math.min(100, (d.value.completed_chapters / d.value.total_chapters) * 100)
})

const streakSubtext = computed(() => {
  if (!d.value) return ''
  return d.value.current_streak > 0
    ? "You're on fire — keep it up."
    : 'Study today to start a streak.'
})

const quizSubtext = computed(() => {
  if (!d.value) return ''
  return d.value.quizzes_passed > 0
    ? 'Solid work — keep sharpening.'
    : 'Take a quiz to see your average here.'
})

const badgesSubtext = computed(() => {
  if (!d.value) return ''
  return d.value.earned_badge_count > 0
    ? 'Nice work — more badges to unlock.'
    : 'Complete lessons and quizzes to earn your first badge.'
})

const recentChats = ref([
  { id: 1, topic: 'e-Governance Security protocols', time: '2 hours ago', icon: '🛡️' },
  { id: 2, topic: 'Smart Agriculture & Food Security', time: 'Yesterday', icon: '🌾' },
  { id: 3, topic: 'Understanding the Innovation Core', time: 'Aug 3', icon: '💡' },
])

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
})

onUnmounted(() => {
  document.removeEventListener('pointermove', handleSpotlightMove)
  if (spotlightRaf) cancelAnimationFrame(spotlightRaf)
})
</script>

<template>
  <AppShell>
    <div class="max-w-6xl mx-auto px-4 md:px-6 space-y-6 pb-24">

      <!-- 1. UNIFIED COMMAND CENTER CARD -->
      <SaCard
        v-if="d"
        :glass="true"
        class="spotlight-card spring-hover fade-up relative overflow-hidden rounded-[2.5rem] px-6 py-10 md:px-12 md:py-14 border border-[var(--sa-gray)] bg-white"
        style="animation-delay: 0ms;"
      >
        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-12">
          
          <div class="flex-1">
            <p class="text-sm font-medium text-brand-300 tracking-wide uppercase mb-2">{{ timeGreeting }}</p>
            <h1 class="font-display text-4xl md:text-5xl font-semibold text-brand-500 leading-tight mb-8">
              Welcome back, {{ firstName }}
            </h1>
            
            <div class="mb-8">
              <p class="text-lg font-bold text-brand-500">
                {{ continueTitle }}
              </p>
              <p class="text-base text-brand-400 mt-1 max-w-md">
                {{ continueSubtext }}
              </p>
              <div v-if="hasChapters" class="mt-4 h-1.5 w-full max-w-xs rounded-full bg-brand-100 overflow-hidden">
                <div class="h-full rounded-full bg-brand-400 transition-[width] duration-700 ease-out" :style="`width: ${chapterProgressPct}%`" />
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-4">
              <RouterLink to="/study" class="cta-primary inline-flex items-center gap-2 rounded-full px-8 py-3.5 font-bold text-sm md:text-base">
                {{ continueButtonLabel }}
                <span aria-hidden="true">→</span>
              </RouterLink>
              <a href="/smart-adama-book.pdf" target="_blank" rel="noopener" class="cta-secondary inline-flex items-center gap-2 rounded-full px-8 py-3.5 font-bold text-sm md:text-base">
                Get the PDF
              </a>
            </div>
          </div>

          <div class="flex items-center gap-6 lg:border-l lg:border-brand-200 lg:pl-12">
            <ProgressRing :value="d.completion_pct" label="Complete" />
            <div>
              <p class="text-4xl font-display font-semibold text-brand-500">
                {{ d.completed_chapters }}<span class="text-xl font-normal text-brand-300">/{{ d.total_chapters }}</span>
              </p>
              <p class="text-sm font-medium text-brand-400 mt-1">Chapters complete</p>
            </div>
          </div>
          
        </div>
      </SaCard>
      <div v-else class="h-64 rounded-[2.5rem] bg-brand-100 animate-pulse" />

      <!-- 2. Supporting stats (Updated to bg-brand-100 / #D5DEEF) -->
      <div v-if="d" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="spotlight-card spring-hover fade-up flex items-start gap-4 px-6 py-6 bg-brand-100 border border-brand-200 rounded-3xl" style="animation-delay: 100ms;">
          <span class="text-3xl" :class="{ 'flame-pulse': d.current_streak > 0 }">🔥</span>
          <div>
            <p class="text-2xl font-display font-semibold text-brand-500">{{ d.current_streak }}</p>
            <p class="text-xs font-medium text-brand-500 mt-0.5">Day streak</p>
            <p class="text-xs text-brand-400 mt-1">{{ streakSubtext }}</p>
          </div>
        </div>

        <div class="spotlight-card spring-hover fade-up px-6 py-6 bg-brand-100 border border-brand-200 rounded-3xl" style="animation-delay: 200ms;">
          <p class="text-2xl font-display font-semibold text-brand-500">
            {{ d.average_quiz_score !== null ? `${d.average_quiz_score}%` : '—' }}
          </p>
          <p class="text-xs font-medium text-brand-500 mt-0.5">Avg. quiz score · {{ d.quizzes_passed }} passed</p>
          <p class="text-xs text-brand-400 mt-1">{{ quizSubtext }}</p>
        </div>

        <div class="spotlight-card spring-hover fade-up px-6 py-6 bg-brand-100 border border-brand-200 rounded-3xl" style="animation-delay: 300ms;">
          <p class="text-2xl font-display font-semibold text-brand-500">{{ d.earned_badge_count }}</p>
          <p class="text-xs font-medium text-brand-500 mt-0.5">Badges earned</p>
          <p class="text-xs text-brand-400 mt-1">{{ badgesSubtext }}</p>
        </div>
      </div>

      <!-- 3. Badges shelf -->
      <section v-if="d" class="fade-up pt-4" style="animation-delay: 400ms;">
        <h2 class="font-display font-semibold text-brand-500 mb-1">Badges</h2>
        <p class="text-sm text-brand-400 mb-6">Earn these by reading chapters, keeping your streak, and passing quizzes.</p>

        <div v-if="progress.badges.length" class="flex flex-wrap gap-4">
          <div
            v-for="b in progress.badges"
            :key="b.id"
            :title="b.description"
            :class="[
              'spotlight-card spring-hover flex flex-col items-center gap-1.5 p-4 rounded-2xl border text-center w-28 cursor-default',
              b.earned
                ? 'bg-brand-400 border-brand-400'
                : 'bg-white border-brand-200',
            ]"
          >
            <span class="text-2xl" :class="{ 'grayscale opacity-50': !b.earned }">{{ b.icon }}</span>
            <span class="text-[11px] font-semibold leading-tight" :class="b.earned ? 'text-white' : 'text-brand-500'">
              {{ b.name }}
            </span>
            <span class="text-[10px]" :class="b.earned ? 'text-white/70' : 'text-brand-300'">
              <template v-if="b.earned">Earned</template>
              <template v-else-if="b.progress">{{ b.progress.current }}/{{ b.progress.required }}</template>
              <template v-else>Locked</template>
            </span>
            <div v-if="!b.earned && b.progress" class="w-full bg-brand-100 rounded-full h-1 mt-0.5">
              <div
                class="bg-brand-300 h-1 rounded-full"
                :style="`width: ${Math.min(100, (b.progress.current / b.progress.required) * 100)}%`"
              />
            </div>
          </div>
        </div>
      </section>

      <!-- 4. Ecosystem Features Bento Grid -->
      <div v-if="d" class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-4 fade-up" style="animation-delay: 500ms;">
        
        <SaCard class="spotlight-card spring-hover flex flex-col p-8 rounded-[2rem] bg-white border border-[var(--sa-gray)]">
          <div class="flex items-center justify-between mb-6">
            <h3 class="font-display font-semibold text-brand-500 text-xl">Recent AI Chats</h3>
            <RouterLink to="/chat" class="text-sm font-semibold text-brand-300 hover:text-brand-500 transition-colors">View All</RouterLink>
          </div>
          <div class="flex flex-col gap-3">
            <RouterLink 
              v-for="chat in recentChats" 
              :key="chat.id" 
              to="/chat"
              class="group flex items-center justify-between p-4 rounded-2xl border border-brand-200 hover:border-brand-400 hover:bg-brand-50 transition-all cursor-pointer"
            >
              <div class="flex items-center gap-4">
                <span class="text-2xl opacity-80 group-hover:scale-110 transition-transform">{{ chat.icon }}</span>
                <div class="flex flex-col">
                  <span class="font-medium text-brand-500 text-sm">{{ chat.topic }}</span>
                  <span class="text-xs text-brand-400">{{ chat.time }}</span>
                </div>
              </div>
              <span class="text-brand-400 group-hover:text-brand-500 group-hover:translate-x-1 transition-all">→</span>
            </RouterLink>
          </div>
        </SaCard>

        <div class="flex flex-col gap-6">
          <SaCard class="spotlight-card spring-hover flex flex-col p-8 rounded-[2rem] bg-brand-500 text-white border-transparent">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-display font-semibold text-white text-xl">Daily Challenge</h3>
              <span class="px-3 py-1 rounded-full bg-white/20 text-xs font-bold text-white uppercase tracking-wider">+10 XP</span>
            </div>
            <p class="text-brand-50 text-sm mb-6">{{ dailyChallenge.question }}</p>
            
            <div class="flex flex-col gap-2">
              <button 
                v-for="(option, index) in dailyChallenge.options" 
                :key="index"
                @click="answerChallenge(index)"
                :disabled="dailyChallenge.answered"
                class="w-full text-left p-4 rounded-xl text-sm font-medium transition-all duration-300 border"
                :class="[
                  !dailyChallenge.answered ? 'border-brand-400/30 bg-brand-400/20 hover:bg-brand-400/40 text-brand-50' : '',
                  dailyChallenge.answered && index === dailyChallenge.correctIndex ? 'border-emerald-400 bg-emerald-400/20 text-emerald-300' : '',
                  dailyChallenge.answered && index !== dailyChallenge.correctIndex && index === dailyChallenge.selected ? 'border-red-400 bg-red-400/20 text-red-300' : '',
                  dailyChallenge.answered && index !== dailyChallenge.correctIndex && index !== dailyChallenge.selected ? 'border-brand-400/10 bg-transparent text-brand-200 opacity-50' : ''
                ]"
              >
                {{ option }}
                <span v-if="dailyChallenge.answered && index === dailyChallenge.correctIndex" class="float-right">✓</span>
                <span v-if="dailyChallenge.answered && index === dailyChallenge.selected && index !== dailyChallenge.correctIndex" class="float-right">✗</span>
              </button>
            </div>
          </SaCard>

          <SaCard class="spotlight-card spring-hover flex flex-col p-8 rounded-[2rem] bg-white border border-[var(--sa-gray)] flex-1">
            <h3 class="font-display font-semibold text-brand-500 text-xl mb-6">Ecosystem Updates</h3>
            <div class="flex flex-col gap-4">
              <div v-for="news in newsUpdates" :key="news.id" class="flex gap-4 items-start">
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
  50% { filter: drop-shadow(0 0 6px rgba(0, 0, 0, 0.25)); transform: scale(1.08); }
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
  background: radial-gradient(420px circle at var(--spot-x) var(--spot-y), rgba(0, 0, 0, 0.03), transparent 45%);
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
  box-shadow: 0 20px 40px -20px rgba(99, 142, 203, 0.2);
}

.cta-primary {
  background: var(--color-brand-400);
  color: #ffffff;
  box-shadow: 0 12px 24px -12px rgba(99, 142, 203, 0.4);
  transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.25s ease, box-shadow 0.25s ease;
}
.cta-primary:hover {
  opacity: 0.9;
  transform: translateY(-2px);
  box-shadow: 0 15px 25px -10px rgba(99, 142, 203, 0.6);
}
.cta-primary:active { transform: scale(0.97); }

.cta-secondary {
  color: var(--color-brand-500);
  border: 1.5px solid var(--color-brand-400);
  background: #ffffff;
  transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), background 0.25s ease, color 0.25s ease;
}
.cta-secondary:hover {
  background: var(--color-brand-50);
  color: var(--color-brand-500);
  transform: translateY(-2px);
}
.cta-secondary:active { transform: scale(0.97); }

@media (prefers-reduced-motion: reduce) {
  .fade-up, .flame-pulse { animation: none !important; }
  .spring-hover, .spotlight-card::before, .cta-primary, .cta-secondary { transition: none !important; }
  .spring-hover:hover, .cta-primary:hover, .cta-secondary:hover { transform: none; }
}
</style>