<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useProgressStore } from '@/stores/progress'
import { useChatStore } from '@/stores/chat'
import AppShell from '@/components/layout/AppShell.vue'
import { useI18n } from 'vue-i18n'

const auth = useAuthStore()
const progress = useProgressStore()
const chatStore = useChatStore()
const { t } = useI18n()

const isDark = ref(false)
let observer: MutationObserver | null = null

const d = computed(() => progress.dashboard)
const hour = new Date().getHours()
const firstName = computed(() => auth.user?.name?.split(' ')[0] || 'Ashenafi')

const timeGreeting = computed(() => {
  if (hour < 5) return t('dashboard.gn') || 'Good Night'
  if (hour < 12) return t('dashboard.gm') || 'Good Morning'
  if (hour < 17) return t('dashboard.ga') || 'Good Afternoon'
  if (hour < 21) return t('dashboard.ge') || 'Good Evening'
  return t('dashboard.gn') || 'Good Night'
})

const hasChapters = computed(() => !!d.value && d.value.total_chapters > 0)
const isBookComplete = computed(() => {
  return !!d.value && d.value.total_chapters > 0 && d.value.completed_chapters >= d.value.total_chapters
})

const continueTitle = computed(() => {
  if (!hasChapters.value) return t('dashboard.book_ready') || 'Ready to Start'
  if (isBookComplete.value) return t('dashboard.book_done') || 'Course Complete'
  const next = Math.min((d.value?.completed_chapters ?? 0) + 1, d.value?.total_chapters ?? 0)
  return t('dashboard.chap_of', { next, total: d.value?.total_chapters }) || `Chapter ${next} of ${d.value?.total_chapters}`
})

const continueSubtext = computed(() => {
  if (!hasChapters.value) return t('dashboard.sub_ready') || 'Your learning journey awaits.'
  if (isBookComplete.value) return t('dashboard.sub_done') || 'You have mastered this content.'
  return 'Your progress saves automatically — jump back in whenever you are ready.'
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
    time: formatChatTime(session.updated_at || session.created_at)
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
  { id: '1', name: 'First Step', description: 'Complete your first chapter.', earned: false, progress: { current: 0, required: 1 } },
  { id: '2', name: 'Committed Learner', description: 'Complete 5 chapters.', earned: false, progress: { current: 0, required: 5 } },
  { id: '3', name: 'Perfectionist', description: 'Score 100% on any quiz.', earned: false, progress: null },
  { id: '4', name: 'On a Roll', description: 'Maintain a 3-day learning streak.', earned: false, progress: { current: 0, required: 3 } },
  { id: '5', name: 'Week Warrior', description: 'Maintain a 7-day learning streak.', earned: false, progress: { current: 0, required: 7 } },
]

const displayBadges = computed(() => {
  return (progress.badges && progress.badges.length > 0) ? progress.badges : defaultBadges
})

onMounted(() => {
  progress.loadAll()
  chatStore.loadSessions(1)
  
  isDark.value = document.documentElement.classList.contains('dark')
  observer = new MutationObserver(() => {
    isDark.value = document.documentElement.classList.contains('dark')
  })
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
})

onUnmounted(() => {
  if (observer) observer.disconnect()
})
</script>

<template>
  <AppShell>
    <!-- SKELETON LOADER -->
    <div v-if="!d" class="max-w-7xl mx-auto px-6 md:px-12 pt-16 pb-20 space-y-12 animate-pulse min-h-screen" :class="isDark ? 'bg-[#0B0F19]' : 'bg-[#F0F3FA]'">
      <div class="w-full h-64 md:h-96 rounded-[2.5rem]" :class="isDark ? 'bg-slate-800' : 'bg-[#D5DEEF]'"></div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="h-28 rounded-3xl" :class="isDark ? 'bg-slate-800' : 'bg-[#D5DEEF]'"></div>
        <div class="h-28 rounded-3xl" :class="isDark ? 'bg-slate-800' : 'bg-[#D5DEEF]'"></div>
        <div class="h-28 rounded-3xl" :class="isDark ? 'bg-slate-800' : 'bg-[#D5DEEF]'"></div>
      </div>
    </div>

    <!-- MAIN DASHBOARD CONTENT -->
    <div v-else class="w-full min-h-screen flex flex-col font-sans transition-colors duration-300" :class="isDark ? 'bg-[#0B0F19]' : 'bg-[#F0F3FA]'">
      
      <!-- 1. HERO SECTION -->
      <section class="w-full pt-16 pb-20 reveal-up border-b transition-colors duration-300" :class="isDark ? 'border-slate-800 bg-[#0B0F19]' : 'border-[#D5DEEF] bg-[#F0F3FA]'">
        <div class="max-w-7xl mx-auto px-6 md:px-12 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
          
          <div class="lg:col-span-7 flex flex-col">
            <!-- Removed the dot -->
            <p class="font-bold tracking-widest uppercase text-xs mb-4 text-[#10b981]">
              {{ timeGreeting }}, {{ firstName }}
            </p>
            
            <h1 class="text-4xl md:text-6xl font-display font-bold mb-6 leading-tight text-balance transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">
              {{ continueTitle }}
            </h1>
            
            <p class="text-lg mb-10 max-w-xl leading-relaxed transition-colors duration-300" :class="isDark ? 'text-slate-400' : 'text-[#638ECB]'">
              {{ continueSubtext }}
            </p>

            <div class="flex flex-wrap items-center gap-4">
              <RouterLink to="/study" class="group inline-flex items-center gap-3 rounded-full px-8 py-4 font-bold text-sm text-white hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300" :class="isDark ? 'bg-blue-600 hover:bg-blue-500' : 'bg-[#395886] hover:bg-[#638ECB]'">
                Continue Reading
                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
              </RouterLink>
              <!-- Updated Read PDF to point to public/books/SA-Book.pdf -->
              <a href="/books/SA-Book.pdf" target="_blank" class="inline-flex items-center gap-2 rounded-full px-8 py-4 font-bold text-sm bg-transparent border-2 transition-all duration-300" :class="isDark ? 'border-slate-700 text-white hover:bg-slate-800' : 'border-[#D5DEEF] text-[#395886] hover:bg-white'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Read PDF
              </a>
            </div>
          </div>

          <div class="lg:col-span-5 flex justify-center lg:justify-end">
            <div class="relative flex items-center justify-center w-64 h-64 md:w-80 md:h-80">
              <svg class="w-full h-full transform -rotate-90 drop-shadow-2xl" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="42" fill="transparent" stroke-width="3" :class="isDark ? 'stroke-slate-800' : 'stroke-[#D5DEEF]'" />
                <circle cx="50" cy="50" r="42" fill="transparent" stroke="#10b981" stroke-width="3" stroke-linecap="round"
                        stroke-dasharray="263.89" :stroke-dashoffset="263.89 - (263.89 * Math.min(100, d.completion_pct || 0)) / 100"
                        class="transition-all duration-1500 ease-out" />
              </svg>
              <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-6xl font-display font-bold tracking-tighter transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">{{ d.completion_pct || 0 }}<span class="text-3xl text-[#10b981]">%</span></span>
                <span class="text-xs font-bold uppercase tracking-widest mt-2 transition-colors duration-300" :class="isDark ? 'text-slate-500' : 'text-[#8AAEE0]'">Complete</span>
                <div class="mt-4 px-4 py-1.5 rounded-full backdrop-blur-sm border transition-colors duration-300" :class="isDark ? 'bg-slate-800/60 border-slate-700' : 'bg-white/60 border-[#D5DEEF]'">
                  <p class="text-sm font-medium" :class="isDark ? 'text-slate-300' : 'text-[#638ECB]'">
                    <span class="font-bold" :class="isDark ? 'text-white' : 'text-[#395886]'">{{ d.completed_chapters || 0 }}</span> / {{ d.total_chapters || 0 }} Ch.
                  </p>
                </div>
              </div>
            </div>
          </div>

        </div>
      </section>

      <!-- 2. METRICS RIBBON -->
      <section class="w-full reveal-up delay-100 border-b transition-colors duration-300" :class="isDark ? 'bg-[#0B0F19] border-slate-800' : 'bg-white border-[#D5DEEF]'">
        <div class="max-w-7xl mx-auto px-6 md:px-12 py-12">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-12 divide-y md:divide-y-0 md:divide-x" :class="isDark ? 'divide-slate-800' : 'divide-[#D5DEEF]'">
            
            <div class="flex items-center gap-6 md:px-8 first:pl-0 last:pr-0 pt-6 md:pt-0 first:pt-0">
              <div class="w-16 h-16 rounded-full flex items-center justify-center shrink-0 transition-colors duration-300" :class="isDark ? 'bg-slate-800 text-blue-400' : 'bg-[#F0F3FA] text-[#638ECB]'">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"></path></svg>
              </div>
              <div>
                <p class="text-xs font-bold uppercase tracking-widest mb-1 transition-colors duration-300" :class="isDark ? 'text-slate-500' : 'text-[#8AAEE0]'">Learning Streak</p>
                <div class="flex items-baseline gap-2">
                  <span class="text-4xl font-display font-bold transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">{{ d.current_streak || 0 }}</span>
                  <span class="text-sm font-medium transition-colors duration-300" :class="isDark ? 'text-slate-400' : 'text-[#638ECB]'">days</span>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-6 md:px-8 pt-6 md:pt-0">
              <div class="w-16 h-16 rounded-full flex items-center justify-center shrink-0 transition-colors duration-300" :class="isDark ? 'bg-slate-800 text-blue-400' : 'bg-[#F0F3FA] text-[#638ECB]'">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
              </div>
              <div>
                <p class="text-xs font-bold uppercase tracking-widest mb-1 transition-colors duration-300" :class="isDark ? 'text-slate-500' : 'text-[#8AAEE0]'">Average Score</p>
                <div class="flex items-baseline gap-2">
                  <span class="text-4xl font-display font-bold transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">{{ d.average_quiz_score !== null ? `${d.average_quiz_score}%` : '—' }}</span>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-6 md:px-8 pt-6 md:pt-0">
              <div class="w-16 h-16 rounded-full flex items-center justify-center shrink-0 transition-colors duration-300" :class="isDark ? 'bg-slate-800 text-blue-400' : 'bg-[#F0F3FA] text-[#638ECB]'">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
              </div>
              <div>
                <p class="text-xs font-bold uppercase tracking-widest mb-1 transition-colors duration-300" :class="isDark ? 'text-slate-500' : 'text-[#8AAEE0]'">Badges Earned</p>
                <div class="flex items-baseline gap-2">
                  <span class="text-4xl font-display font-bold transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">{{ d.earned_badge_count || 0 }}</span>
                  <span class="text-sm font-medium transition-colors duration-300" :class="isDark ? 'text-slate-400' : 'text-[#638ECB]'">unlocked</span>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>

      <!-- 3. AI SECTION -->
      <section class="w-full reveal-up delay-200 transition-colors duration-300" :class="isDark ? 'bg-[#0B0F19]' : 'bg-[#395886]'">
        <div class="max-w-7xl mx-auto px-6 md:px-12 py-20 lg:py-24">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            
            <div class="flex flex-col items-start">
              <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border text-[10px] font-bold uppercase tracking-widest mb-6" :class="isDark ? 'bg-white/5 border-slate-800 text-slate-300' : 'bg-white/10 border-white/20 text-[#D5DEEF]'">
                <span class="w-1.5 h-1.5 rounded-full" :class="isDark ? 'bg-blue-400' : 'bg-[#B1C9EF]'"></span> Smart Adama AI
              </div>
              <h2 class="text-3xl md:text-5xl font-display font-bold text-white mb-6 text-balance leading-tight">
                Master Smart City concepts faster.
              </h2>
              <p class="mb-10 text-lg leading-relaxed max-w-lg" :class="isDark ? 'text-slate-400' : 'text-[#B1C9EF]'">
                Your AI tutor is embedded directly into the curriculum. Ask questions, request summaries, and explore the Adama ecosystem deeply.
              </p>
              <RouterLink to="/study" class="group inline-flex items-center gap-3 px-8 py-4 rounded-full hover:shadow-xl font-bold transition-all duration-300" :class="isDark ? 'bg-blue-600 text-white hover:bg-blue-500' : 'bg-white text-[#395886] hover:bg-[#F0F3FA]'">
                Start a Chat
                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
              </RouterLink>
            </div>

            <div class="w-full">
              <div class="rounded-3xl p-8 border shadow-2xl transition-colors duration-300" :class="isDark ? 'bg-[#1E293B] border-slate-800' : 'bg-[#2A4265] border-white/10'">
                <div class="flex items-center justify-between mb-8">
                  <h3 class="text-white font-semibold text-sm flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="isDark ? 'text-blue-400' : 'text-[#8AAEE0]'"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                    Recent Conversations
                  </h3>
                  <RouterLink to="/study" class="text-xs font-bold hover:text-white transition-colors uppercase tracking-wider" :class="isDark ? 'text-blue-400' : 'text-[#8AAEE0]'">View All</RouterLink>
                </div>

                <div v-if="liveRecentChats.length > 0" class="flex flex-col gap-3">
                  <RouterLink v-for="chat in liveRecentChats" :key="chat.id" to="/study" class="group flex items-center justify-between p-5 rounded-2xl border border-transparent transition-all duration-300" :class="isDark ? 'bg-slate-800 hover:bg-slate-700' : 'bg-white/5 hover:bg-white/10'">
                    <div class="flex flex-col gap-1.5">
                      <span class="font-medium text-base truncate max-w-[200px] sm:max-w-xs" :class="isDark ? 'text-white' : 'text-[#F0F3FA]'">{{ chat.topic }}</span>
                      <span class="text-xs" :class="isDark ? 'text-slate-400' : 'text-[#8AAEE0]'">{{ chat.time }}</span>
                    </div>
                    <svg class="w-4 h-4 group-hover:text-white group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="isDark ? 'text-slate-500' : 'text-[#638ECB]'"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                  </RouterLink>
                </div>
                
                <div v-else class="flex flex-col items-center justify-center py-12 text-center rounded-2xl border transition-colors duration-300" :class="isDark ? 'bg-slate-800/50 border-slate-800' : 'bg-white/5 border-white/5'">
                  <svg class="w-12 h-12 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="isDark ? 'text-slate-600' : 'text-[#638ECB]'"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                  <p class="text-base font-medium" :class="isDark ? 'text-white' : 'text-[#F0F3FA]'">No active chats</p>
                  <p class="text-sm mt-2" :class="isDark ? 'text-slate-400' : 'text-[#8AAEE0]'">Start a conversation in study mode.</p>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>

      <!-- 4. BADGES & ECOSYSTEM -->
      <section class="w-full pt-20 pb-32 reveal-up delay-300 transition-colors duration-300" :class="isDark ? 'bg-[#0B0F19]' : 'bg-[#F0F3FA]'">
        <div class="max-w-7xl mx-auto px-6 md:px-12 space-y-20">
          
          <!-- Badges -->
          <div>
            <div class="mb-8">
              <h2 class="text-3xl font-display font-bold mb-2 transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">Learning Milestones</h2>
              <p class="text-base transition-colors duration-300" :class="isDark ? 'text-slate-400' : 'text-[#638ECB]'">Earn badges by mastering chapters and passing assessments.</p>
            </div>

            <div class="flex overflow-x-auto gap-6 pb-8 hide-scrollbar snap-x -mx-6 px-6 md:mx-0 md:px-0">
              <div v-for="b in displayBadges" :key="b.id" class="snap-start shrink-0 w-[260px]">
                <div class="h-full rounded-[2rem] p-6 border transition-all duration-300 flex flex-col hover:-translate-y-1 hover:shadow-lg"
                     :class="isDark ? (b.earned ? 'bg-[#1E293B] border-emerald-500/50' : 'bg-[#1E293B] border-slate-700 opacity-80 hover:opacity-100') : (b.earned ? 'bg-white border-[#10b981] shadow-sm' : 'bg-white border-[#D5DEEF] opacity-80 hover:opacity-100')">
                  
                  <div class="absolute top-6 right-6">
                    <span v-if="b.earned" :class="isDark ? 'text-emerald-400' : 'text-[#10b981]'">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    </span>
                    <span v-else :class="isDark ? 'text-slate-600' : 'text-[#B1C9EF]'">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                    </span>
                  </div>

                  <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-6 shadow-sm border transition-colors duration-300"
                       :class="isDark ? (b.earned ? 'bg-emerald-900/30 border-emerald-800 text-emerald-400' : 'bg-slate-800 border-slate-700 text-slate-500') : (b.earned ? 'bg-emerald-50 border-emerald-100 text-[#10b981]' : 'bg-[#F0F3FA] border-[#D5DEEF] text-[#8AAEE0]')">
                    <svg v-if="b.name.includes('Step')" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    <svg v-else-if="b.name.includes('Learner')" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    <svg v-else-if="b.name.includes('Perfectionist')" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                    <svg v-else-if="b.name.includes('Roll')" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"></path></svg>
                    <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                  </div>

                  <h3 class="font-bold text-lg mb-2 transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">{{ b.name }}</h3>
                  <p class="text-sm mb-8 leading-relaxed transition-colors duration-300" :class="isDark ? 'text-slate-400' : 'text-[#638ECB]'">{{ b.description }}</p>

                  <div class="mt-auto">
                    <div class="flex justify-between items-end mb-2 transition-colors duration-300">
                      <span class="text-[10px] font-bold uppercase tracking-wider" :class="isDark ? (b.earned ? 'text-emerald-400' : 'text-slate-500') : (b.earned ? 'text-[#10b981]' : 'text-[#8AAEE0]')">
                        {{ b.earned ? 'Unlocked' : 'Locked' }}
                      </span>
                      <span v-if="!b.earned && b.progress" class="text-xs font-bold" :class="isDark ? 'text-slate-300' : 'text-[#395886]'">
                        {{ b.progress.current }} / {{ b.progress.required }}
                      </span>
                    </div>
                    
                    <div v-if="!b.earned && b.progress" class="h-2 w-full rounded-full overflow-hidden transition-colors duration-300" :class="isDark ? 'bg-slate-800' : 'bg-[#F0F3FA]'">
                      <div class="h-full rounded-full transition-all duration-1000" :class="isDark ? 'bg-slate-600' : 'bg-[#B1C9EF]'" :style="`width: ${(b.progress.current / b.progress.required) * 100}%`"></div>
                    </div>
                    <div v-else-if="b.earned" class="h-2 w-full rounded-full overflow-hidden transition-colors duration-300" :class="isDark ? 'bg-emerald-900/50' : 'bg-emerald-100'">
                      <div class="h-full bg-[#10b981] rounded-full w-full"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Split -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Daily Challenge -->
            <div class="rounded-[2.5rem] p-10 border shadow-sm flex flex-col justify-between transition-colors duration-500" :class="isDark ? 'bg-[#1E293B] border-slate-800' : 'bg-white border-[#D5DEEF]'">
              <div>
                <div class="flex items-center justify-between mb-8">
                  <h2 class="text-2xl font-display font-bold transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">Daily Challenge</h2>
                  <div class="flex gap-3">
                    <span class="px-3 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-wider transition-colors duration-300" :class="isDark ? 'bg-slate-800 text-slate-400' : 'bg-[#F0F3FA] text-[#638ECB]'">Coming Soon</span>
                    <span class="px-3 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-wider border transition-colors duration-300" :class="isDark ? 'bg-emerald-900/30 text-emerald-400 border-emerald-800' : 'bg-emerald-50 text-[#10b981] border-emerald-100'">+10 XP</span>
                  </div>
                </div>
                <p class="text-lg font-medium leading-relaxed mb-8 transition-colors duration-300" :class="isDark ? 'text-slate-200' : 'text-[#395886]'">"{{ dailyChallenge.question }}"</p>
              </div>
              
              <div class="space-y-4">
                <button v-for="(option, index) in dailyChallenge.options" :key="index" @click="answerChallenge(index)" :disabled="dailyChallenge.answered"
                  class="w-full text-left p-5 rounded-2xl text-base font-medium transition-all duration-300 border-2"
                  :class="[
                    !dailyChallenge.answered ? (isDark ? 'border-slate-700 bg-slate-900 hover:border-slate-500 hover:bg-slate-800 text-slate-300 cursor-pointer' : 'border-[#F0F3FA] bg-white hover:border-[#8AAEE0] hover:bg-[#F0F3FA] text-[#638ECB] cursor-pointer') : '',
                    dailyChallenge.answered && index === dailyChallenge.correctIndex ? (isDark ? 'border-emerald-500 bg-emerald-900/30 text-emerald-400' : 'border-[#10b981] bg-emerald-50 text-[#10b981]') : '',
                    dailyChallenge.answered && index !== dailyChallenge.correctIndex && index === dailyChallenge.selected ? (isDark ? 'border-rose-600 bg-rose-900/30 text-rose-400' : 'border-rose-400 bg-rose-50 text-rose-600') : '',
                    dailyChallenge.answered && index !== dailyChallenge.correctIndex && index !== dailyChallenge.selected ? (isDark ? 'border-slate-800 bg-slate-800 text-slate-600 opacity-50' : 'border-[#F0F3FA] bg-[#F0F3FA] text-[#8AAEE0] opacity-50') : ''
                  ]"
                >
                  <div class="flex items-center justify-between">
                    <span>{{ option }}</span>
                  </div>
                </button>
              </div>
            </div>

            <!-- Ecosystem Updates -->
            <div class="rounded-[2.5rem] p-10 border transition-colors duration-300" :class="isDark ? 'bg-[#1E293B] border-slate-800' : 'bg-[#F0F3FA] border-[#D5DEEF]'">
              <h2 class="text-2xl font-display font-bold mb-8 transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">Ecosystem Updates</h2>
              
              <div class="relative border-l-2 ml-3 space-y-10 pb-4" :class="isDark ? 'border-slate-700' : 'border-[#D5DEEF]'">
                <div v-for="news in newsUpdates" :key="news.id" class="relative pl-8">
                  <div class="absolute -left-[9px] top-1 w-4 h-4 rounded-full border-4 transition-colors duration-300" :class="isDark ? (news.isNew ? 'bg-blue-500 border-slate-900' : 'bg-slate-600 border-slate-900') : (news.isNew ? 'bg-[#395886] border-[#F0F3FA]' : 'bg-[#B1C9EF] border-[#F0F3FA]')"></div>
                  <div class="flex flex-col">
                    <span class="text-xs font-bold uppercase tracking-wider mb-2 transition-colors duration-300" :class="isDark ? 'text-slate-500' : 'text-[#8AAEE0]'">{{ news.date }}</span>
                    <p class="text-base font-medium transition-colors duration-300" :class="isDark ? 'text-slate-200' : 'text-[#395886]'">{{ news.title }}</p>
                  </div>
                </div>
              </div>

              <div class="mt-8 pt-8 border-t transition-colors duration-300" :class="isDark ? 'border-slate-700' : 'border-[#D5DEEF]'">
                <p class="text-xs font-bold uppercase tracking-wider text-center flex items-center justify-center gap-2 transition-colors duration-300" :class="isDark ? 'text-slate-500' : 'text-[#8AAEE0]'">
                  <span class="w-2 h-2 rounded-full bg-[#10b981]"></span> Smart Adama Platform
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- FOOTER -->
      <footer class="w-full mt-auto pt-16 pb-8 transition-colors duration-300" :class="isDark ? 'bg-slate-900 border-t border-slate-800' : 'bg-[#395886] border-t border-[#2A4265]'">
        <div class="max-w-7xl mx-auto px-6 md:px-12">
          
          <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
            
            <div class="md:col-span-2">
              <h3 class="text-xl font-display font-bold text-white mb-4">Smart Adama</h3>
              <p class="text-sm leading-relaxed max-w-sm transition-colors duration-300" :class="isDark ? 'text-slate-400' : 'text-[#B1C9EF]'">
                Empowering the next generation of citizens with AI-driven education and smart city infrastructure.
              </p>
            </div>

            <div>
              <h4 class="font-bold text-white mb-4">Ecosystem</h4>
              <ul class="space-y-3">
                <li><a href="#" class="text-sm transition-colors duration-300 hover:text-white" :class="isDark ? 'text-slate-400' : 'text-[#B1C9EF]'">AI Study Companion</a></li>
                <li><a href="#" class="text-sm transition-colors duration-300 hover:text-white" :class="isDark ? 'text-slate-400' : 'text-[#B1C9EF]'">Gamification & Badges</a></li>
                <li><a href="#" class="text-sm transition-colors duration-300 hover:text-white" :class="isDark ? 'text-slate-400' : 'text-[#B1C9EF]'">Language Center</a></li>
                <li><a href="/books/SA-Book.pdf" target="_blank" class="text-sm transition-colors duration-300 hover:text-white" :class="isDark ? 'text-slate-400' : 'text-[#B1C9EF]'">Download PDF</a></li>
              </ul>
            </div>

            <div>
              <h4 class="font-bold text-white mb-4">Legal & Community</h4>
              <ul class="space-y-3">
                <li><a href="#" class="text-sm transition-colors duration-300 hover:text-white" :class="isDark ? 'text-slate-400' : 'text-[#B1C9EF]'">About the Developers</a></li>
                <li><a href="#" class="text-sm transition-colors duration-300 hover:text-white" :class="isDark ? 'text-slate-400' : 'text-[#B1C9EF]'">Privacy Policy</a></li>
                <li><a href="#" class="text-sm transition-colors duration-300 hover:text-white" :class="isDark ? 'text-slate-400' : 'text-[#B1C9EF]'">Terms of Service</a></li>
                <li><a href="#" class="text-sm transition-colors duration-300 hover:text-white" :class="isDark ? 'text-slate-400' : 'text-[#B1C9EF]'">Contact Us</a></li>
              </ul>
            </div>

          </div>

          <div class="flex flex-col md:flex-row items-center justify-between gap-4 pt-8 border-t transition-colors duration-300" :class="isDark ? 'border-slate-800' : 'border-[#2A4265]'">
            <p class="text-sm font-medium transition-colors duration-300" :class="isDark ? 'text-slate-500' : 'text-[#8AAEE0]'">
              &copy; {{ new Date().getFullYear() }} Adama Smart City Ecosystem. All rights reserved.
            </p>
            <div class="flex items-center gap-4">
              <span class="text-sm font-medium transition-colors duration-300" :class="isDark ? 'text-slate-500' : 'text-[#8AAEE0]'">English / Afaan Oromoo / Amharic</span>
            </div>
          </div>
        </div>
      </footer>

    </div>
  </AppShell>
</template>

<style scoped>
@keyframes revealUp {
  0% { opacity: 0; transform: translateY(30px); }
  100% { opacity: 1; transform: translateY(0); }
}

.reveal-up {
  opacity: 0;
  animation: revealUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.delay-100 { animation-delay: 100ms; }
.delay-200 { animation-delay: 200ms; }
.delay-300 { animation-delay: 300ms; }

.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
</style>