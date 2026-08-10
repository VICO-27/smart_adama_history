<template>
  <div class="flex flex-col min-h-screen bg-brand-50 relative">
    
    <AppNav @open-auth="openAuthModal" />

    <div class="max-w-6xl mx-auto px-4 pt-28 pb-16 flex flex-col gap-6 w-full min-h-125">
      
      <div v-reveal class="glass rounded-4xl overflow-hidden grow relative shadow-lg border border-brand-200 spotlight-card">
        <div class="grid grid-cols-1 lg:grid-cols-5 h-full min-h-125">
          
          <div class="lg:col-span-2 p-10 md:p-16 flex flex-col justify-center items-start gap-6 relative z-10">
            <h1 v-reveal style="transition-delay: 100ms;" class="text-5xl font-display font-bold text-black leading-[1.1] tracking-tight">
              {{ $t('landing.hero_title') }}
            </h1>
            <p v-reveal style="transition-delay: 200ms;" class="text-xl text-brand-500 max-w-md">
              {{ $t('landing.hero_subtitle') }}
            </p>
            
            <div v-reveal style="transition-delay: 300ms;" class="flex flex-col w-full max-w-sm mt-4">
              <button class="spring-button w-full rounded-full bg-emerald-500 text-white py-4 font-bold hover:bg-emerald-600 shadow-[0_8px_30px_rgb(16,185,129,0.3)] hover:shadow-[0_8px_30px_rgb(16,185,129,0.5)] hover:-translate-y-1 active:scale-95 transition-all" @click="handleLearnMore">
                {{ $t('landing.hero_btn') }}
              </button>
            </div>
          </div>
          
          <div v-reveal style="transition-delay: 400ms;" class="lg:col-span-3 p-6 lg:p-8 lg:pl-0 h-full flex flex-col">
            <div class="w-full h-full min-h-75 grow bg-brand-200 rounded-4xl overflow-hidden relative shadow-2xl">
              <video
                ref="heroVideoRef"
                class="absolute inset-0 w-full h-full object-cover hero-video-parallax"
                autoplay
                loop
                muted
                playsinline
                preload="auto"
                fetchpriority="high"
                poster="@/assets/adama.png"
              >
                <source src="/videos/hero.mp4" type="video/mp4" />
              </video>
            </div>
          </div>
          
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-2">
        <div v-reveal style="transition-delay: 100ms;" class="group flex flex-col p-8 rounded-4xl bg-white border border-[#E2E8F0] shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] hover:shadow-[0_20px_40px_-12px_rgba(0,0,0,0.1)] hover:-translate-y-2 transition-all duration-400 cursor-pointer" @click="handleStudyAI">
          <div class="w-16 h-16 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-3xl mb-6 group-hover:scale-110 group-hover:rotate-3 transition-transform duration-400 shadow-sm">🤖</div>
          <h3 class="text-xl font-bold font-display text-slate-900 mb-2">{{ $t('landing.cards.study_title') }}</h3>
          <p class="text-slate-500 text-sm font-medium leading-relaxed">{{ $t('landing.cards.study_desc') }}</p>
        </div>
        
        <div v-reveal style="transition-delay: 200ms;" class="group flex flex-col p-8 rounded-4xl bg-white border border-[#E2E8F0] shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] hover:shadow-[0_20px_40px_-12px_rgba(0,0,0,0.1)] hover:-translate-y-2 transition-all duration-400 cursor-pointer" @click="handleDownloadBook">
          <div class="w-16 h-16 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-3xl mb-6 group-hover:scale-110 group-hover:-rotate-3 transition-transform duration-400 shadow-sm">📚</div>
          <h3 class="text-xl font-bold font-display text-slate-900 mb-2">{{ $t('landing.cards.book_title') }}</h3>
          <p class="text-slate-500 text-sm font-medium leading-relaxed">{{ $t('landing.cards.book_desc') }}</p>
        </div>
        
        <div v-reveal style="transition-delay: 300ms;" class="group flex flex-col p-8 rounded-4xl bg-white border border-[#E2E8F0] shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] hover:shadow-[0_20px_40px_-12px_rgba(0,0,0,0.1)] hover:-translate-y-2 transition-all duration-400 cursor-pointer" @click="$router.push('/about')">
          <div class="w-16 h-16 rounded-2xl bg-amber-50 border border-amber-100 flex items-center justify-center text-3xl mb-6 group-hover:scale-110 group-hover:rotate-3 transition-transform duration-400 shadow-sm">👨‍💻</div>
          <h3 class="text-xl font-bold font-display text-slate-900 mb-2">{{ $t('landing.cards.devs_title') }}</h3>
          <p class="text-slate-500 text-sm font-medium leading-relaxed">{{ $t('landing.cards.devs_desc') }}</p>
        </div>
      </div>
    </div>

    <div id="framework-section" class="cv-lg w-full bg-brand-500 text-white overflow-hidden py-24 md:py-32">
      <div class="max-w-6xl mx-auto px-6">
        <h2 v-reveal class="text-4xl md:text-5xl lg:text-7xl font-display font-medium leading-[1.1] max-w-4xl tracking-tight mb-16 lg:mb-24">
          <span class="text-white">{{ $t('landing.framework_title_1') }} </span>
          <span class="text-brand-300">{{ $t('landing.framework_title_2') }}</span>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 px-2 py-4 -mx-2">
          <div v-for="(item, index) in coreInitiatives" :key="index" v-reveal :style="`transition-delay: ${(index + 1) * 100}ms;`" @click="$router.push('/study')" class="group relative bg-white/3 backdrop-blur-2xl border border-white/10 rounded-4xl p-8 hover:bg-white/10 transition-all duration-500 hover:-translate-y-3 cursor-pointer overflow-hidden flex flex-col min-h-80 shadow-2xl">
            <div class="absolute -top-20 -right-20 w-48 h-48 rounded-full blur-3xl opacity-20 group-hover:opacity-40 transition-opacity duration-700" :class="item.glowColor"></div>
            <div class="flex justify-between items-start mb-auto relative z-10">
               <span class="text-[10px] font-mono font-bold tracking-widest text-white/60 bg-white/5 px-3 py-1.5 rounded-full border border-white/10">
                 {{ $t('landing.cores.label') }} 0{{ index + 1 }}
               </span>
               <div class="w-12 h-12 flex items-center justify-center bg-white/5 rounded-2xl border border-white/10 text-2xl filter grayscale opacity-70 group-hover:grayscale-0 group-hover:opacity-100 group-hover:scale-110 transition-all duration-500">
                 {{ item.icon }}
               </div>
            </div>
            <div class="relative z-10 mt-12">
              <h3 class="text-2xl font-display font-bold text-white mb-3 group-hover:text-white transition-colors flex items-center gap-2">
                {{ item.title }}
                <svg class="w-5 h-5 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
              </h3>
              <p class="text-brand-100/70 text-sm leading-relaxed font-medium">{{ item.desc }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="cv-lg w-full bg-white pt-32 pb-24">
      <div v-reveal class="max-w-6xl mx-auto px-6 text-center mb-16">
        <h2 class="text-4xl md:text-5xl lg:text-6xl font-display font-bold text-black mb-6 tracking-tight">
          {{ $t('landing.book_title') }}
        </h2>
        <p class="text-xl text-brand-500 max-w-2xl mx-auto font-medium">
          {{ $t('landing.book_desc') }}
        </p>
      </div>

      <div v-reveal class="w-full shadow-2xl bg-brand-50 border-y border-brand-200">
        <video
          ref="bookVideoRef" data-src="/videos/smart-adama-book.mp4"
          loop muted playsinline preload="none" fetchpriority="low"
          class="w-full h-auto max-h-200 object-cover dark:mix-blend-normal mix-blend-multiply"
        ></video>
      </div>
    </div>
    
    <div class="cv-md w-full bg-white relative flex items-center justify-center py-40 md:py-56 overflow-hidden">
      <div class="absolute top-0 inset-x-0 h-px bg-linear-to-r from-transparent via-brand-200 to-transparent w-full max-w-3xl mx-auto"></div>
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-150 md:w-225 h-150 md:h-225 bg-brand-50 rounded-full blur-[100px] md:blur-[150px] animate-breathe-smooth pointer-events-none"></div>
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-100 md:w-150 h-100 md:h-150 bg-brand-100/40 rounded-full blur-[80px] md:blur-[120px] animate-breathe-delayed pointer-events-none"></div>

      <div v-reveal class="relative z-10 text-center max-w-4xl px-6 flex flex-col items-center">
        <div class="mb-8 px-5 py-1.5 rounded-full border border-brand-200 bg-white backdrop-blur-md text-xs font-mono tracking-[0.2em] text-brand-400 uppercase">
          {{ $t('landing.vision_badge') }}
        </div>
        <h3 class="text-3xl md:text-5xl lg:text-6xl font-display font-medium text-transparent bg-clip-text bg-linear-to-b from-black via-brand-500 to-brand-400 leading-tight tracking-tight">
          {{ $t('landing.vision_quote') }}
        </h3>
        <div class="w-px h-24 md:h-32 bg-linear-to-b from-brand-300 to-transparent mt-16"></div>
      </div>
    </div>

    <div class="cv-lg w-full bg-brand-500 py-24 overflow-hidden relative group/carousel">
      <div v-reveal class="max-w-6xl mx-auto px-6 mb-12">
        <h2 class="text-4xl md:text-5xl font-display font-bold text-white tracking-tight">{{ $t('landing.visit_title') }}</h2>
        <p class="text-brand-100 mt-3 text-lg">{{ $t('landing.visit_desc') }}</p>
      </div>

      <div class="relative w-full">
        <div ref="carouselRef" class="w-full overflow-x-auto flex gap-0 pb-8 snap-x snap-mandatory animate-slide-in-right no-scrollbar scroll-smooth">
          <div 
            v-for="i in 9" 
            :key="i"
            class="w-70 md:w-80 h-125 md:h-142 shrink-0 snap-center overflow-hidden bg-brand-400 relative border-r border-brand-400/30 group"
          >
            <video
              :ref="(el) => setCarouselRef(el as HTMLVideoElement | null, i)"
              :data-src="`/videos/tiktok-${i}.mp4`"
              loop muted playsinline preload="none" fetchpriority="low"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 bg-brand-400"
            ></video>
          </div>
        </div>

        <button 
          @click="scrollNext" 
          class="spring-button absolute right-6 top-1/2 -translate-y-1/2 z-10 w-14 h-14 flex items-center justify-center bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 text-white rounded-full shadow-2xl transform hover:scale-110 active:scale-95 opacity-0 group-hover/carousel:opacity-100 focus:opacity-100"
          aria-label="Next Video"
        >
          <svg class="w-6 h-6 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
        </button>
      </div>
    </div>

    <div v-reveal class="cv-sm w-full bg-brand-50 py-32 text-center px-6">
      <h2 class="text-5xl md:text-6xl font-display font-bold text-black mb-6 tracking-tight">{{ $t('landing.cta_title') }}</h2>
      <p class="text-xl text-brand-500 mb-10 max-w-2xl mx-auto">{{ $t('landing.cta_desc') }}</p>
      <div class="flex flex-col md:flex-row justify-center items-center gap-4">
        <button class="spring-button w-full md:w-auto px-10 rounded-full bg-emerald-500 text-white py-4 font-bold hover:bg-emerald-600 shadow-md hover:shadow-xl hover:-translate-y-1 active:scale-95 transition-all" @click="handleStudyAI">
          {{ $t('landing.cta_btn_1') }}
        </button>
        <button @click="handleDownloadBook" class="spring-button px-10 py-4 rounded-full border-2 border-brand-400 text-brand-500 font-bold hover:border-brand-500 hover:bg-brand-100 w-full md:w-auto transition-all">
          {{ $t('landing.cta_btn_2') }}
        </button>
      </div>
    </div>

    <footer class="cv-md w-full bg-brand-500 pt-16 pb-8 border-t border-brand-400 text-sm text-brand-200">
      <div class="max-w-6xl mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
          
          <div class="col-span-1 md:col-span-2">
            <div class="flex items-center gap-2 font-display font-bold text-white text-2xl mb-4">
              ⚡️ {{ $t('nav.brand') }}
            </div>
            <p class="max-w-xs text-brand-100 leading-relaxed">
              {{ $t('landing.footer.tagline') }}
            </p>
          </div>
          
          <div>
            <h4 class="text-white font-bold mb-4 uppercase tracking-wider text-xs">{{ $t('landing.footer.ecosystem') }}</h4>
            <ul class="flex flex-col gap-3">
              <li><router-link to="/study" class="hover:text-white transition-colors duration-300">{{ $t('landing.footer.study') }}</router-link></li>
              <li><router-link to="/game" class="hover:text-white transition-colors duration-300">{{ $t('landing.footer.game') }}</router-link></li>
              <li><router-link to="/language" class="hover:text-white transition-colors duration-300">{{ $t('landing.footer.language') }}</router-link></li>
              <li><a href="/smart-adama-book.pdf" target="_blank" class="hover:text-white transition-colors duration-300">{{ $t('landing.footer.download') }}</a></li>
            </ul>
          </div>
          
          <div>
            <h4 class="text-white font-bold mb-4 uppercase tracking-wider text-xs">{{ $t('landing.footer.legal') }}</h4>
            <ul class="flex flex-col gap-3">
              <li><router-link to="/about" class="hover:text-white transition-colors duration-300">{{ $t('landing.footer.about') }}</router-link></li>
              <li><a href="#" class="hover:text-white transition-colors duration-300">{{ $t('landing.footer.privacy') }}</a></li>
              <li><a href="#" class="hover:text-white transition-colors duration-300">{{ $t('landing.footer.terms') }}</a></li>
              <li><a href="#" class="hover:text-white transition-colors duration-300">{{ $t('landing.footer.contact') }}</a></li>
            </ul>
          </div>
        </div>
        
        <div class="pt-8 border-t border-brand-400 flex flex-col md:flex-row items-center justify-between gap-4">
          <p>{{ $t('landing.footer.copyright') }}</p>
          <div class="flex gap-4">
            <span class="text-brand-200">English / Afaan Oromoo / Amharic</span>
          </div>
        </div>
      </div>
    </footer>

    <AuthModal :is-open="isAuthModalOpen" :initial-mode="authMode" @close="isAuthModalOpen = false" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppNav from '@/components/layout/AppNav.vue'
import AuthModal from '@/components/auth/AuthModal.vue'
import { useI18n } from 'vue-i18n'

const router = useRouter()
const auth = useAuthStore()
const { t } = useI18n()

const isAuthModalOpen = ref(false)
const authMode = ref<'login' | 'register'>('login')

const openAuthModal = (mode: 'login' | 'register') => {
  authMode.value = mode
  isAuthModalOpen.value = true
}

const heroVideoRef = ref<HTMLVideoElement | null>(null)
const bookVideoRef = ref<HTMLVideoElement | null>(null)
const carouselVideoRefs: (HTMLVideoElement | null)[] = []

const coreInitiatives = computed(() => [
  { title: t('landing.cores.c1_title'), desc: t('landing.cores.c1_desc'), icon: '🏛️', glowColor: 'bg-blue-400' },
  { title: t('landing.cores.c2_title'), desc: t('landing.cores.c2_desc'), icon: '💡', glowColor: 'bg-amber-400' },
  { title: t('landing.cores.c3_title'), desc: t('landing.cores.c3_desc'), icon: '📈', glowColor: 'bg-emerald-400' },
  { title: t('landing.cores.c4_title'), desc: t('landing.cores.c4_desc'), icon: '🌾', glowColor: 'bg-green-400' },
])

function setCarouselRef(el: HTMLVideoElement | null, i: number) {
  carouselVideoRefs[i - 1] = el
}

const carouselRef = ref<HTMLElement | null>(null)

let loadObserver: IntersectionObserver | null = null
let playObserver: IntersectionObserver | null = null
let visibilityHandler: (() => void) | null = null
let spotlightRaf: number | null = null
let lastPointerEvent: PointerEvent | null = null

function lazyLoadVideo(video: HTMLVideoElement) {
  const src = video.dataset.src
  if (src && !video.src) {
    video.src = src
    video.load()
  }
}

function handleSpotlightMove(e: PointerEvent) {
  lastPointerEvent = e
  if (spotlightRaf) return
  spotlightRaf = requestAnimationFrame(() => {
    spotlightRaf = null
    const ev = lastPointerEvent
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
  loadObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        lazyLoadVideo(entry.target as HTMLVideoElement)
        loadObserver?.unobserve(entry.target)
      }
    })
  }, { rootMargin: '600px 0px', threshold: 0 })

  playObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      const video = entry.target as HTMLVideoElement
      if (entry.isIntersecting) {
        if (video.src) video.play().catch(() => {})
      } else {
        video.pause()
      }
    })
  }, { threshold: 0.15 })

  const lazyTargets = [bookVideoRef.value, ...carouselVideoRefs].filter(
    (v): v is HTMLVideoElement => !!v
  )
  const playTargets = [heroVideoRef.value, bookVideoRef.value, ...carouselVideoRefs].filter(
    (v): v is HTMLVideoElement => !!v
  )

  lazyTargets.forEach((v) => loadObserver?.observe(v))
  playTargets.forEach((v) => playObserver?.observe(v))

  visibilityHandler = () => {
    playTargets.forEach((video) => {
      if (document.hidden) {
        video.pause()
      } else {
        const rect = video.getBoundingClientRect()
        const isVisible = rect.top < window.innerHeight && rect.bottom > 0
        if (isVisible && video.src) video.play().catch(() => {})
      }
    })
  }
  document.addEventListener('visibilitychange', visibilityHandler)
  document.addEventListener('pointermove', handleSpotlightMove, { passive: true })
})

onUnmounted(() => {
  loadObserver?.disconnect()
  playObserver?.disconnect()
  if (visibilityHandler) document.removeEventListener('visibilitychange', visibilityHandler)
  document.removeEventListener('pointermove', handleSpotlightMove)
  if (spotlightRaf) cancelAnimationFrame(spotlightRaf)
})

const handleLearnMore = () => {
  const section = document.getElementById('framework-section')
  if (section) section.scrollIntoView({ behavior: 'smooth' })
}

const handleStudyAI = () => {
  if (auth.isAuthenticated) {
    router.push('/study')
  } else {
    router.push('/login?redirect=/study')
  }
}

const handleDownloadBook = () => {
  window.open('/smart-adama-book.pdf', '_blank')
}

const scrollNext = () => {
  if (carouselRef.value) {
    carouselRef.value.scrollBy({
      left: 320,
      behavior: 'smooth'
    })
  }
}
</script>

<style scoped>
@keyframes marquee {
  0% { transform: translateX(0%); }
  100% { transform: translateX(-100%); }
}
.animate-marquee-slow {
  animation: marquee 70s linear infinite;
  will-change: transform;
}
.marquee-link {
  transition: color 0.3s ease, transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  display: inline-flex;
}
.marquee-link:hover {
  transform: scale(1.12);
}

@keyframes slideInRight {
  from { opacity: 0; transform: translateX(100px); }
  to { opacity: 1; transform: translateX(0); }
}
.animate-slide-in-right {
  animation: slideInRight 1.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.slide-from-right {
  opacity: 0;
  transform: translateX(100px);
}
[v-reveal].revealed .slide-from-right,
.revealed.slide-from-right,
.is-visible.slide-from-right,
.slide-from-right.is-visible {
  opacity: 1;
  transform: translateX(0);
}

@keyframes breathe-smooth {
  0%, 100% { 
    transform: translate(-50%, -50%) scale(1); 
    opacity: 0.6; 
  }
  50% { 
    transform: translate(-50%, -50%) scale(1.05);
    opacity: 1; 
  }
}

.animate-breathe-smooth {
  animation: breathe-smooth 10s cubic-bezier(0.4, 0, 0.2, 1) infinite;
  will-change: transform, opacity;
}
.animate-breathe-delayed {
  animation: breathe-smooth 14s cubic-bezier(0.4, 0, 0.2, 1) infinite 4s;
  will-change: transform, opacity;
}

.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.spring-hover {
  transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1),
              box-shadow 0.45s ease-out,
              border-color 0.45s ease-out,
              background-color 0.45s ease-out;
}
.spring-button {
  transition: transform 0.45s cubic-bezier(0.34, 1.56, 0.64, 1),
              box-shadow 0.4s ease-out,
              background-color 0.4s ease-out;
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
  background: radial-gradient(500px circle at var(--spot-x) var(--spot-y), rgba(255, 255, 255, 0.10), transparent 45%);
  opacity: 0;
  transition: opacity 0.4s ease;
  pointer-events: none;
  z-index: 1;
}
.spotlight-card:hover::before {
  opacity: 1;
}
.spotlight-card.spotlight-light::before {
  background: radial-gradient(500px circle at var(--spot-x) var(--spot-y), rgba(0, 0, 0, 0.05), transparent 45%);
}

.cv-lg {
  content-visibility: auto;
  contain-intrinsic-size: auto 1400px;
}
.cv-md {
  content-visibility: auto;
  contain-intrinsic-size: auto 900px;
}
.cv-sm {
  content-visibility: auto;
  contain-intrinsic-size: auto 600px;
}

@supports (animation-timeline: scroll()) {
  .hero-video-parallax {
    animation: heroParallax linear both;
    animation-timeline: scroll(root);
    animation-range: 0 70vh;
  }
  @keyframes heroParallax {
    from { transform: scale(1); }
    to { transform: scale(1.08); }
  }
}

@media (prefers-reduced-motion: reduce) {
  .animate-marquee-slow,
  .animate-breathe-smooth,
  .animate-breathe-delayed,
  .animate-slide-in-right,
  .hero-video-parallax,
  .marquee-link {
    animation: none !important;
    transition: none !important;
  }
  .spring-hover,
  .spring-button,
  .spotlight-card::before {
    transition: none !important;
  }
}
</style>