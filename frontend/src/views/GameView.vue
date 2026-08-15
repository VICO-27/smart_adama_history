    <script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useProgressStore } from '@/stores/progress'
import AppShell from '@/components/layout/AppShell.vue'

const auth = useAuthStore()
const progress = useProgressStore()

// Theme synchronization
const isDark = ref(false)
let observer: MutationObserver | null = null

// Data Sources
const d = computed(() => progress.dashboard)
const badges = computed(() => progress.badges || [])
const firstName = computed(() => auth.user?.name?.split(' ')[0] || 'Scholar')

// --- Derived Progression System ---
// Since the backend doesn't currently provide raw XP or Levels, we derive it gracefully 
// from real, existing user metrics to avoid faking data.
const currentXP = computed(() => {
  if (!d.value) return 0
  const chapterXP = (d.value.completed_chapters || 0) * 150
  const quizXP = (d.value.quizzes_passed || 0) * 100
  const streakXP = (d.value.current_streak || 0) * 20
  return chapterXP + quizXP + streakXP
})

const currentLevel = computed(() => Math.floor(currentXP.value / 1000) + 1)
const nextLevelXP = computed(() => currentLevel.value * 1000)
const currentLevelXP = computed(() => currentXP.value % 1000)
const xpProgressPct = computed(() => Math.min(100, (currentLevelXP.value / 1000) * 100))

// --- Game Modes Architecture ---
interface GameMode {
  id: string
  title: string
  description: string
  difficulty: 'Easy' | 'Medium' | 'Hard' | 'Expert'
  xpReward: number
  duration: string
  available: boolean
  route: string
}

const gameModes = ref<GameMode[]>([
  {
    id: 'quick-quiz',
    title: 'Quick Quiz',
    description: 'Test your retention on recent chapters with rapid-fire questions.',
    difficulty: 'Medium',
    xpReward: 100,
    duration: '5 min',
    available: true,
    route: '/quizzes'
  },
  {
    id: 'chapter-challenge',
    title: 'Chapter Challenge',
    description: 'Select a specific chapter and master its core concepts deeply.',
    difficulty: 'Hard',
    xpReward: 150,
    duration: '15 min',
    available: true,
    route: '/study'
  },
  {
    id: 'speed-round',
    title: 'Speed Round',
    description: 'Answer as many Smart City questions as possible before time runs out.',
    difficulty: 'Expert',
    xpReward: 250,
    duration: '3 min',
    available: false,
    route: '#'
  },
  {
    id: 'memory-match',
    title: 'Concept Memory',
    description: 'Match civic ideas, infrastructure types, and urban terminology.',
    difficulty: 'Easy',
    xpReward: 50,
    duration: '5 min',
    available: false,
    route: '#'
  }
])

onMounted(() => {
  if (!d.value) progress.loadAll()
  
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
      <div class="w-full h-64 md:h-80 rounded-[2.5rem]" :class="isDark ? 'bg-slate-800' : 'bg-[#D5DEEF]'"></div>
      <div class="w-full h-32 rounded-3xl" :class="isDark ? 'bg-slate-800' : 'bg-[#D5DEEF]'"></div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="h-48 rounded-3xl" :class="isDark ? 'bg-slate-800' : 'bg-[#D5DEEF]'"></div>
        <div class="h-48 rounded-3xl" :class="isDark ? 'bg-slate-800' : 'bg-[#D5DEEF]'"></div>
      </div>
    </div>

    <!-- MAIN GAME HUB -->
    <div v-else class="w-full min-h-screen flex flex-col font-sans transition-colors duration-300" :class="isDark ? 'bg-[#0B0F19]' : 'bg-[#F0F3FA]'">
      
      <!-- 1. HERO SECTION -->
      <section class="relative w-full pt-16 pb-20 reveal-up border-b transition-colors duration-300 overflow-hidden" :class="isDark ? 'border-slate-800 bg-[#0B0F19]' : 'border-[#D5DEEF] bg-[#F0F3FA]'">
        <!-- Cinematic Orbs -->
        <div class="absolute top-0 right-[10%] w-[400px] h-[400px] blur-[120px] rounded-full pointer-events-none animate-float transition-colors duration-500" :class="isDark ? 'bg-blue-600/10' : 'bg-blue-400/20'"></div>
        <div class="absolute bottom-0 left-[5%] w-[300px] h-[300px] blur-[100px] rounded-full pointer-events-none animate-float transition-colors duration-500" style="animation-delay: 2s;" :class="isDark ? 'bg-emerald-500/10' : 'bg-emerald-400/10'"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-6 md:px-12 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
          <div class="lg:col-span-7 flex flex-col">
            <p class="font-bold tracking-widest uppercase text-xs mb-4 text-[#10b981] flex items-center gap-2">
              <span class="w-1.5 h-1.5 rounded-full bg-[#10b981]"></span>
              Smart Adama Interactive
            </p>
            <h1 class="text-4xl md:text-6xl lg:text-7xl font-display font-extrabold mb-4 leading-tight text-balance transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">
              Learn. Challenge.<br>Master.
            </h1>
            <p class="text-lg mb-8 max-w-xl leading-relaxed transition-colors duration-300" :class="isDark ? 'text-slate-400' : 'text-[#638ECB]'">
              Turn what you've learned into challenges, earn points, and build your Smart Adama streak. Knowledge becomes progress.
            </p>
          </div>

          <!-- Hero Visual: Abstract Gamification Object -->
          <div class="lg:col-span-5 flex justify-center lg:justify-end">
            <div class="relative flex items-center justify-center w-64 h-64 md:w-80 md:h-80 group">
              <div class="absolute inset-0 rounded-full scale-75 blur-3xl transition-all duration-700" :class="isDark ? 'bg-emerald-500/10 group-hover:bg-emerald-500/20' : 'bg-emerald-400/10 group-hover:bg-emerald-400/30'"></div>
              <div class="relative z-10 w-48 h-48 md:w-56 md:h-56 rounded-3xl rotate-12 transition-transform duration-700 ease-out group-hover:rotate-0 group-hover:scale-105 border flex items-center justify-center shadow-2xl backdrop-blur-md" :class="isDark ? 'bg-slate-800/40 border-slate-700' : 'bg-white/40 border-white'">
                 <svg class="w-20 h-20 transition-colors duration-300" :class="isDark ? 'text-emerald-400' : 'text-[#10b981]'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- 2. PLAYER PROGRESS RIBBON -->
      <section class="w-full reveal-up delay-100 border-b transition-colors duration-300 z-20 relative" :class="isDark ? 'bg-[#1E293B] border-slate-800' : 'bg-white border-[#D5DEEF]'">
        <div class="max-w-7xl mx-auto px-6 md:px-12 py-8">
          <div class="flex flex-col md:flex-row items-center justify-between gap-8">
            
            <!-- Level & XP -->
            <div class="flex items-center gap-6 w-full md:w-auto">
              <div class="w-16 h-16 rounded-2xl flex items-center justify-center shrink-0 border-2 transition-colors duration-300" :class="isDark ? 'bg-slate-900 border-emerald-500/30 text-emerald-400' : 'bg-[#F0F3FA] border-emerald-200 text-[#10b981]'">
                <span class="font-display font-bold text-2xl">{{ currentLevel }}</span>
              </div>
              <div class="flex-grow md:w-64">
                <div class="flex justify-between items-end mb-2">
                  <span class="text-xs font-bold uppercase tracking-widest transition-colors duration-300" :class="isDark ? 'text-slate-400' : 'text-[#8AAEE0]'">Level {{ currentLevel }}</span>
                  <span class="text-xs font-bold transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">{{ currentXP.toLocaleString() }} XP</span>
                </div>
                <div class="h-2.5 w-full rounded-full overflow-hidden transition-colors duration-300" :class="isDark ? 'bg-slate-800' : 'bg-[#F0F3FA]'">
                  <div class="h-full rounded-full transition-all duration-1000 ease-out bg-[#10b981]" :style="`width: ${xpProgressPct}%`"></div>
                </div>
                <p class="text-[10px] mt-2 font-medium transition-colors duration-300" :class="isDark ? 'text-slate-500' : 'text-[#638ECB]'">{{ (1000 - currentLevelXP).toLocaleString() }} XP to Level {{ currentLevel + 1 }}</p>
              </div>
            </div>

            <!-- Stats -->
            <div class="flex items-center gap-12 w-full md:w-auto justify-between md:justify-end border-t md:border-t-0 pt-6 md:pt-0" :class="isDark ? 'border-slate-800' : 'border-[#D5DEEF]'">
              <div>
                <p class="text-[10px] font-bold uppercase tracking-widest mb-1 transition-colors duration-300" :class="isDark ? 'text-slate-500' : 'text-[#8AAEE0]'">Streak</p>
                <div class="flex items-baseline gap-1">
                  <span class="text-2xl font-display font-bold transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">{{ d?.current_streak || 0 }}</span>
                  <span class="text-xs font-medium transition-colors duration-300" :class="isDark ? 'text-slate-400' : 'text-[#638ECB]'">🔥</span>
                </div>
              </div>
              <div>
                <p class="text-[10px] font-bold uppercase tracking-widest mb-1 transition-colors duration-300" :class="isDark ? 'text-slate-500' : 'text-[#8AAEE0]'">Badges</p>
                <div class="flex items-baseline gap-1">
                  <span class="text-2xl font-display font-bold transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">{{ d?.earned_badge_count || 0 }}</span>
                  <span class="text-xs font-medium transition-colors duration-300" :class="isDark ? 'text-slate-400' : 'text-[#638ECB]'">🎖️</span>
                </div>
              </div>
              <div>
                <p class="text-[10px] font-bold uppercase tracking-widest mb-1 transition-colors duration-300" :class="isDark ? 'text-slate-500' : 'text-[#8AAEE0]'">Quizzes</p>
                <div class="flex items-baseline gap-1">
                  <span class="text-2xl font-display font-bold transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">{{ d?.quizzes_passed || 0 }}</span>
                  <span class="text-xs font-medium transition-colors duration-300" :class="isDark ? 'text-slate-400' : 'text-[#638ECB]'">✓</span>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>

      <div class="max-w-7xl mx-auto px-6 md:px-12 py-16 grid grid-cols-1 lg:grid-cols-12 gap-12 flex-grow">
        
        <!-- LEFT COLUMN: GAMES -->
        <div class="lg:col-span-8 space-y-12">
          
          <!-- Daily Challenge Special Treatment -->
          <section class="reveal-up delay-200">
            <h2 class="text-2xl font-display font-bold mb-6 transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">Today's Priority</h2>
            
            <div class="relative overflow-hidden rounded-[2rem] p-8 md:p-10 border transition-all duration-300 hover:shadow-lg group" :class="isDark ? 'bg-gradient-to-br from-slate-900 to-slate-800 border-slate-700' : 'bg-gradient-to-br from-white to-[#F0F3FA] border-[#D5DEEF]'">
              <div class="absolute top-0 right-0 w-64 h-64 bg-[#10b981] opacity-5 blur-[80px] rounded-full pointer-events-none group-hover:opacity-10 transition-opacity duration-500"></div>
              
              <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                  <div class="flex items-center gap-3 mb-3">
                    <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider transition-colors duration-300" :class="isDark ? 'bg-emerald-900/30 text-emerald-400 border border-emerald-800' : 'bg-emerald-50 text-[#10b981] border border-emerald-100'">Daily Challenge</span>
                    <span class="text-sm font-bold transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">+150 XP</span>
                  </div>
                  <h3 class="text-xl md:text-2xl font-bold mb-2 transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">The Smart City Quiz</h3>
                  <p class="text-sm max-w-md transition-colors duration-300" :class="isDark ? 'text-slate-400' : 'text-[#638ECB]'">Test your knowledge on the core pillars of e-Governance and Enterprise.</p>
                </div>
                
                <RouterLink to="/quizzes" class="inline-flex items-center justify-center gap-2 rounded-2xl px-6 py-4 font-bold text-sm text-white transition-all duration-300 shadow-md hover:-translate-y-1 hover:shadow-xl w-full md:w-auto text-center" :class="isDark ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-[#10b981] hover:bg-emerald-500'">
                  Start Challenge
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </RouterLink>
              </div>
            </div>
          </section>

          <!-- Choose Your Challenge Grid -->
          <section class="reveal-up delay-300">
            <h2 class="text-2xl font-display font-bold mb-6 transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">Choose your challenge</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <template v-for="mode in gameModes" :key="mode.id">
                
                <!-- Game Card -->
                <RouterLink v-if="mode.available" :to="mode.route" class="group relative bg-white dark:bg-slate-900 rounded-[2rem] p-6 border transition-all duration-300 hover:-translate-y-1.5 hover:shadow-[0_10px_40px_rgba(0,0,0,0.05)] dark:hover:shadow-[0_10px_40px_rgba(0,0,0,0.3)] flex flex-col h-full" :class="isDark ? 'border-slate-800 hover:border-slate-600' : 'border-[#D5DEEF] hover:border-[#8AAEE0]'">
                  
                  <div class="flex justify-between items-start mb-6">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-colors duration-300 group-hover:scale-110" :class="isDark ? 'bg-blue-900/30 text-blue-400' : 'bg-[#F0F3FA] text-[#395886]'">
                      <!-- Dynamic Icons based on ID -->
                      <svg v-if="mode.id === 'quick-quiz'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                      <svg v-else-if="mode.id === 'chapter-challenge'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <span class="text-xs font-bold px-2 py-1 rounded-md transition-colors duration-300" :class="isDark ? 'bg-emerald-900/30 text-emerald-400' : 'bg-emerald-50 text-[#10b981]'">+{{ mode.xpReward }} XP</span>
                  </div>

                  <h3 class="font-bold text-lg mb-2 transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">{{ mode.title }}</h3>
                  <p class="text-sm mb-6 leading-relaxed transition-colors duration-300 flex-grow" :class="isDark ? 'text-slate-400' : 'text-[#638ECB]'">{{ mode.description }}</p>

                  <div class="flex items-center justify-between pt-4 border-t transition-colors duration-300" :class="isDark ? 'border-slate-800' : 'border-[#F0F3FA]'">
                    <div class="flex items-center gap-4 text-xs font-medium transition-colors duration-300" :class="isDark ? 'text-slate-500' : 'text-[#8AAEE0]'">
                      <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> {{ mode.duration }}</span>
                      <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg> {{ mode.difficulty }}</span>
                    </div>
                    <span class="text-[#10b981] opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all duration-300">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </span>
                  </div>
                </RouterLink>

                <!-- Disabled / Coming Soon Card -->
                <div v-else class="relative rounded-[2rem] p-6 border flex flex-col h-full opacity-60 cursor-not-allowed transition-colors duration-300" :class="isDark ? 'bg-slate-900 border-slate-800' : 'bg-white border-[#D5DEEF]'">
                  <div class="flex justify-between items-start mb-6 grayscale">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-colors duration-300" :class="isDark ? 'bg-slate-800 text-slate-600' : 'bg-[#F0F3FA] text-[#8AAEE0]'">
                      <svg v-if="mode.id === 'speed-round'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                      <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-md transition-colors duration-300" :class="isDark ? 'bg-slate-800 text-slate-500' : 'bg-[#F0F3FA] text-[#8AAEE0]'">Coming Soon</span>
                  </div>

                  <h3 class="font-bold text-lg mb-2 transition-colors duration-300" :class="isDark ? 'text-slate-400' : 'text-[#638ECB]'">{{ mode.title }}</h3>
                  <p class="text-sm mb-6 leading-relaxed transition-colors duration-300 flex-grow" :class="isDark ? 'text-slate-500' : 'text-[#8AAEE0]'">{{ mode.description }}</p>

                  <div class="flex items-center justify-between pt-4 border-t transition-colors duration-300" :class="isDark ? 'border-slate-800' : 'border-[#F0F3FA]'">
                    <span class="text-xs font-medium transition-colors duration-300" :class="isDark ? 'text-slate-600' : 'text-[#B1C9EF]'">In development</span>
                  </div>
                </div>

              </template>
            </div>
          </section>

        </div>

        <!-- RIGHT COLUMN: LEADERBOARD & ACHIEVEMENTS -->
        <div class="lg:col-span-4 space-y-12">
          
          <!-- Leaderboard (Fallback UI) -->
          <section class="reveal-up delay-300">
            <div class="flex items-center justify-between mb-6">
               <h2 class="text-2xl font-display font-bold transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">Leaderboard</h2>
            </div>
            
            <div class="rounded-[2rem] p-6 border transition-colors duration-300" :class="isDark ? 'bg-slate-900 border-slate-800' : 'bg-white border-[#D5DEEF]'">
              <p class="text-xs font-bold uppercase tracking-widest mb-6 transition-colors duration-300" :class="isDark ? 'text-slate-500' : 'text-[#8AAEE0]'">Global Ranking</p>
              
              <div class="space-y-4">
                <!-- Clean Empty State for Backend Unavailability -->
                <div class="flex items-center gap-4 p-4 rounded-2xl border border-dashed transition-colors duration-300" :class="isDark ? 'border-slate-700 bg-slate-800/50' : 'border-[#D5DEEF] bg-[#F0F3FA]'">
                  <div class="w-10 h-10 rounded-full flex items-center justify-center transition-colors duration-300" :class="isDark ? 'bg-slate-800 text-slate-500' : 'bg-white text-[#B1C9EF]'">
                    <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                  </div>
                  <div>
                    <p class="text-sm font-bold transition-colors duration-300" :class="isDark ? 'text-slate-300' : 'text-[#395886]'">Syncing ranks...</p>
                    <p class="text-xs transition-colors duration-300" :class="isDark ? 'text-slate-500' : 'text-[#8AAEE0]'">Multiplayer data pending.</p>
                  </div>
                </div>

                <!-- Current User Context -->
                <div class="flex items-center justify-between p-4 rounded-2xl transition-colors duration-300" :class="isDark ? 'bg-slate-800' : 'bg-[#F0F3FA]'">
                  <div class="flex items-center gap-3">
                    <span class="text-sm font-bold w-6 text-center transition-colors duration-300" :class="isDark ? 'text-emerald-400' : 'text-[#10b981]'">--</span>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white" :class="isDark ? 'bg-blue-600' : 'bg-[#395886]'">
                      {{ firstName.charAt(0) }}
                    </div>
                    <span class="text-sm font-bold transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">You</span>
                  </div>
                  <span class="text-sm font-bold transition-colors duration-300" :class="isDark ? 'text-slate-400' : 'text-[#638ECB]'">{{ currentXP.toLocaleString() }} XP</span>
                </div>
              </div>
            </div>
          </section>

          <!-- Badges -->
          <section class="reveal-up delay-400">
            <div class="flex items-center justify-between mb-6">
               <h2 class="text-2xl font-display font-bold transition-colors duration-300" :class="isDark ? 'text-white' : 'text-[#395886]'">Achievements</h2>
               <RouterLink to="/dashboard" class="text-xs font-bold uppercase tracking-wider transition-colors duration-300 hover:text-emerald-500" :class="isDark ? 'text-slate-500' : 'text-[#8AAEE0]'">View All</RouterLink>
            </div>
            
            <div class="rounded-[2rem] p-6 border transition-colors duration-300" :class="isDark ? 'bg-slate-900 border-slate-800' : 'bg-white border-[#D5DEEF]'">
              <div class="grid grid-cols-4 gap-3">
                <div v-for="(b, i) in badges.slice(0, 8)" :key="i" class="aspect-square rounded-2xl flex items-center justify-center transition-all duration-300 group relative"
                     :class="isDark ? (b.earned ? 'bg-emerald-900/20 border border-emerald-800/50 hover:bg-emerald-900/40' : 'bg-slate-800 opacity-50') : (b.earned ? 'bg-emerald-50 border border-emerald-100 hover:bg-emerald-100' : 'bg-[#F0F3FA] opacity-50')">
                  
                  <span class="text-xl transition-transform duration-300 group-hover:scale-110" :class="isDark ? (b.earned ? 'text-emerald-400' : 'text-slate-600') : (b.earned ? 'text-[#10b981]' : 'text-[#B1C9EF]')">
                    <svg v-if="b.name.includes('Step')" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    <svg v-else-if="b.name.includes('Learner')" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    <svg v-else-if="b.name.includes('Perfectionist')" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                    <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                  </span>
                  
                  <!-- Tooltip -->
                  <div class="absolute bottom-full mb-2 w-48 p-3 rounded-xl opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-300 z-30 shadow-xl" :class="isDark ? 'bg-slate-800 border border-slate-700' : 'bg-[#395886] text-white'">
                    <p class="text-xs font-bold mb-1">{{ b.name }}</p>
                    <p class="text-[10px] leading-snug" :class="isDark ? 'text-slate-400' : 'text-[#B1C9EF]'">{{ b.description }}</p>
                  </div>
                </div>
              </div>
            </div>
          </section>

        </div>
      </div>
      
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

@keyframes float {
  0%, 100% { transform: translateY(0) scale(1); }
  50% { transform: translateY(-20px) scale(1.05); }
}

@keyframes shimmer {
  100% { transform: translateX(100%); }
}

.reveal-up {
  opacity: 0;
  animation: revealUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.animate-float {
  animation: float 8s ease-in-out infinite;
}

.animate-shimmer {
  animation: shimmer 2.5s infinite;
}

.delay-100 { animation-delay: 100ms; }
.delay-200 { animation-delay: 200ms; }
.delay-300 { animation-delay: 300ms; }
.delay-400 { animation-delay: 400ms; }

.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
</style>