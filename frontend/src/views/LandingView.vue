<template>
  <div class="flex flex-col min-h-screen bg-brand-50">
    
    <!-- Hero Wrapper -->
    <div class="max-w-6xl mx-auto px-4 pt-28 pb-16 flex flex-col gap-6 w-full min-h-[90vh]">
      
      <!-- 1. Glass Card Hero Section -->
      <div v-reveal class="glass rounded-[2.5rem] overflow-hidden flex-grow relative shadow-lg border border-brand-200 spotlight-card">
        <div class="grid grid-cols-1 lg:grid-cols-5 h-full min-h-[500px]">
          
          <div class="lg:col-span-2 p-10 md:p-16 flex flex-col justify-center items-start gap-6 relative z-10">
            <h1 v-reveal style="transition-delay: 100ms;" class="text-5xl font-display font-bold text-black leading-[1.1] tracking-tight">
              Empowering a brilliant smart society.
            </h1>
            <p v-reveal style="transition-delay: 200ms;" class="text-xl text-brand-500 max-w-md">
              A world-class digital ecosystem for every citizen. Built by top innovators to shape the future of Adama.
            </p>
            
            <div v-reveal style="transition-delay: 300ms;" class="flex flex-col w-full max-w-sm mt-4">
              <button class="spring-button w-full rounded-full bg-brand-400 text-white py-4 font-bold hover:bg-brand-500 shadow-md hover:shadow-xl hover:-translate-y-1 active:scale-95" @click="handleLearnMore">
                Learn Smart Adama
              </button>
            </div>
          </div>
          
          <div v-reveal style="transition-delay: 400ms;" class="lg:col-span-3 p-6 lg:p-8 lg:pl-0 h-full flex flex-col">
            <div class="w-full h-full min-h-[300px] flex-grow bg-brand-200 rounded-[2rem] overflow-hidden relative shadow-2xl">
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

      <!-- Bottom 3 Cards (Updated to bg-brand-100) -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div v-reveal style="transition-delay: 100ms;" class="spotlight-card spotlight-light spring-hover flex flex-col items-center text-center p-8 rounded-[2rem] cursor-pointer hover:shadow-xl hover:-translate-y-2 bg-brand-100 border border-brand-200" @click="handleStudyAI">
          <div class="relative z-10 flex flex-col items-center">
            <div class="text-4xl mb-3 opacity-80">🤖</div>
            <h3 class="text-xl font-bold font-display text-black mb-2">Study with AI</h3>
            <p class="text-brand-500 text-sm font-medium">Sign in to interact with the Smart Adama book.</p>
          </div>
        </div>
        
        <div v-reveal style="transition-delay: 200ms;" class="spotlight-card spotlight-light spring-hover flex flex-col items-center text-center p-8 rounded-[2rem] cursor-pointer hover:shadow-xl hover:-translate-y-2 bg-brand-100 border border-brand-200" @click="handleDownloadBook">
          <div class="relative z-10 flex flex-col items-center">
            <div class="text-4xl mb-3 opacity-80">📚</div>
            <h3 class="text-xl font-bold font-display text-black mb-2">Take me to the book</h3>
            <p class="text-brand-500 text-sm font-medium">View or download the official PDF.</p>
          </div>
        </div>
        
        <div v-reveal style="transition-delay: 300ms;" class="spotlight-card spotlight-light spring-hover flex flex-col items-center text-center p-8 rounded-[2rem] cursor-pointer hover:shadow-xl hover:-translate-y-2 bg-brand-100 border border-brand-200" @click="$router.push('/about')">
          <div class="relative z-10 flex flex-col items-center">
            <div class="text-4xl mb-3 opacity-80">👨‍💻</div>
            <h3 class="text-xl font-bold font-display text-black mb-2">About the developers</h3>
            <p class="text-brand-500 text-sm font-medium">Meet the interns behind the ecosystem.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. The Linear.app Dark Section -->
    <div id="framework-section" class="cv-lg w-full bg-brand-500 text-white overflow-hidden py-24 md:py-32">
      
      <!-- Auto-Scrolling Marquee -->
      <div v-reveal class="w-full overflow-hidden whitespace-nowrap mb-24 lg:mb-32 flex group">
        <div class="inline-flex animate-marquee-slow hover:[animation-play-state:paused] items-center text-sm md:text-base font-mono tracking-widest font-bold uppercase gap-8 md:gap-12 px-4 text-brand-100">
          <router-link to="/study" class="marquee-link flex items-center gap-2 hover:text-white">▲ Smart Mobility</router-link>
          <router-link to="/study" class="marquee-link flex items-center gap-2 hover:text-white">⬡ Smart People</router-link>
          <router-link to="/study" class="marquee-link flex items-center gap-2 hover:text-white">❖ Smart Environment</router-link>
          <router-link to="/study" class="marquee-link flex items-center gap-2 hover:text-white">◈ Smart Security</router-link>
          <router-link to="/study" class="marquee-link flex items-center gap-2 hover:text-white">◎ Smart Tourism</router-link>
        </div>
        <div class="inline-flex animate-marquee-slow hover:[animation-play-state:paused] items-center text-sm md:text-base font-mono tracking-widest font-bold uppercase gap-8 md:gap-12 px-4 text-brand-100" aria-hidden="true">
          <router-link to="/study" class="marquee-link flex items-center gap-2 hover:text-white">▲ Smart Mobility</router-link>
          <router-link to="/study" class="marquee-link flex items-center gap-2 hover:text-white">⬡ Smart People</router-link>
          <router-link to="/study" class="marquee-link flex items-center gap-2 hover:text-white">❖ Smart Environment</router-link>
          <router-link to="/study" class="marquee-link flex items-center gap-2 hover:text-white">◈ Smart Security</router-link>
          <router-link to="/study" class="marquee-link flex items-center gap-2 hover:text-white">◎ Smart Tourism</router-link>
        </div>
      </div>

      <div class="max-w-6xl mx-auto px-6">
        <h2 v-reveal class="text-4xl md:text-5xl lg:text-7xl font-display font-medium leading-[1.1] max-w-4xl tracking-tight mb-20 lg:mb-32">
          <span class="text-white">A Model for Smart Adama Initiatives. </span>
          <span class="text-brand-300">A detailed conceptual framework.</span>
        </h2>

        <!-- Abstract Concept Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 overflow-hidden px-2 py-4 -mx-2">
          <div v-for="(core, index) in ['e-Governance', 'Innovation', 'Enterprise', 'Food Security']" :key="index" v-reveal :style="`transition-delay: ${(index + 1) * 100}ms;`" @click="$router.push('/study')" class="slide-from-right spotlight-card spring-hover group border border-brand-400 bg-brand-400/20 rounded-3xl p-8 min-h-[300px] flex flex-col hover:bg-brand-400/40 hover:-translate-y-2 cursor-pointer relative overflow-hidden">
            <span class="text-xs text-brand-200 font-mono tracking-widest relative z-10">CORE 0{{ index + 1 }}</span>
            <div class="mt-auto relative z-10">
              <h3 class="text-2xl font-display font-medium text-white mb-3">{{ core }}</h3>
              <p class="text-sm text-brand-100 leading-relaxed">Integration and expansion of Smart Adama pillars.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. The Smart Adama Book Showcase -->
    <div class="cv-lg w-full bg-white pt-32 pb-24">
      <div v-reveal class="max-w-6xl mx-auto px-6 text-center mb-16">
        <h2 class="text-4xl md:text-5xl lg:text-6xl font-display font-bold text-black mb-6 tracking-tight">
          Master the framework.
        </h2>
        <p class="text-xl text-brand-500 max-w-2xl mx-auto font-medium">
          Dive into the official Smart Adama book. Interact with the AI tutor, complete chapters, take quizzes, and earn your badges.
        </p>
      </div>

      <div v-reveal class="w-full shadow-2xl bg-brand-50 border-y border-brand-200">
        <video
          ref="bookVideoRef" data-src="/videos/smart-adama-book.mp4"
          loop muted playsinline preload="none" fetchpriority="low"
          class="w-full h-auto max-h-[800px] object-cover mix-blend-multiply"
        ></video>
      </div>
    </div>
    
    <!-- 4. Monochrome Ambient Luxury Transition -->
    <div class="cv-md w-full bg-white relative flex items-center justify-center py-40 md:py-56 overflow-hidden">
      <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-brand-200 to-transparent w-full max-w-3xl mx-auto"></div>

      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] md:w-[900px] h-[600px] md:h-[900px] bg-brand-50 rounded-full blur-[100px] md:blur-[150px] animate-breathe-smooth pointer-events-none"></div>
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[400px] md:w-[600px] h-[400px] md:h-[600px] bg-brand-100/40 rounded-full blur-[80px] md:blur-[120px] animate-breathe-delayed pointer-events-none"></div>

      <div v-reveal class="relative z-10 text-center max-w-4xl px-6 flex flex-col items-center">
        <div class="mb-8 px-5 py-1.5 rounded-full border border-brand-200 bg-white backdrop-blur-md text-xs font-mono tracking-[0.2em] text-brand-400 uppercase">
          The Vision
        </div>
        <h3 class="text-3xl md:text-5xl lg:text-6xl font-display font-medium text-transparent bg-clip-text bg-gradient-to-b from-black via-brand-500 to-brand-400 leading-tight tracking-tight">
          "A city built not just for tomorrow, <br class="hidden md:block" /> but for the brilliant minds shaping it today."
        </h3>
        <div class="w-px h-24 md:h-32 bg-gradient-to-b from-brand-300 to-transparent mt-16"></div>
      </div>
    </div>

    <!-- 5. Visit Adama (Text overlays removed completely) -->
    <div class="cv-lg w-full bg-brand-500 py-24 overflow-hidden relative group/carousel">
      <div v-reveal class="max-w-6xl mx-auto px-6 mb-12">
        <h2 class="text-4xl md:text-5xl font-display font-bold text-white tracking-tight">Visit Adama.</h2>
        <p class="text-brand-100 mt-3 text-lg">Explore our streets, culture, and innovations.</p>
      </div>

      <div class="relative w-full">
        <div ref="carouselRef" class="w-full overflow-x-auto flex gap-0 pb-8 snap-x snap-mandatory animate-slide-in-right no-scrollbar scroll-smooth">
          <div 
            v-for="i in 9" 
            :key="i"
            class="w-[280px] md:w-[320px] h-[500px] md:h-[568px] flex-shrink-0 snap-center overflow-hidden bg-brand-400 relative border-r border-brand-400/30 group"
          >
            <video
              :ref="(el) => setCarouselRef(el, i)"
              :data-src="`/videos/tiktok-${i}.mp4`"
              loop
              muted
              playsinline
              preload="none"
              fetchpriority="low"
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

    <!-- 6. Final Call to Action -->
    <div v-reveal class="cv-sm w-full bg-brand-50 py-32 text-center px-6">
      <h2 class="text-5xl md:text-6xl font-display font-bold text-black mb-6 tracking-tight">Ready to join the ecosystem?</h2>
      <p class="text-xl text-brand-500 mb-10 max-w-2xl mx-auto">Start exploring the Smart Adama book with AI, track your progress, and earn badges.</p>
      <div class="flex flex-col md:flex-row justify-center items-center gap-4">
        <button class="spring-button w-full md:w-auto px-10 rounded-full bg-brand-400 text-white py-4 font-bold hover:bg-brand-500 shadow-md hover:shadow-xl hover:-translate-y-1 active:scale-95" @click="handleStudyAI">
          Get Started
        </button>
        <button @click="handleDownloadBook" class="spring-button px-10 py-4 rounded-full border-2 border-brand-400 text-brand-500 font-bold hover:border-brand-500 hover:bg-brand-100 w-full md:w-auto">
          Read the Book
        </button>
      </div>
    </div>

    <!-- 7. Professional Footer -->
    <footer class="cv-md w-full bg-brand-500 pt-16 pb-8 border-t border-brand-400 text-sm text-brand-200">
      <div class="max-w-6xl mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
          
          <div class="col-span-1 md:col-span-2">
            <div class="flex items-center gap-2 font-display font-bold text-white text-2xl mb-4">
              ⚡️ Smart Adama
            </div>
            <p class="max-w-xs text-brand-100 leading-relaxed">
              Empowering the next generation of citizens with AI-driven education and smart city infrastructure.
            </p>
          </div>
          
          <div>
            <h4 class="text-white font-bold mb-4 uppercase tracking-wider text-xs">Ecosystem</h4>
            <ul class="flex flex-col gap-3">
              <li><router-link to="/study" class="hover:text-white transition-colors duration-300">AI Study Companion</router-link></li>
              <li><router-link to="/game" class="hover:text-white transition-colors duration-300">Gamification & Badges</router-link></li>
              <li><router-link to="/language" class="hover:text-white transition-colors duration-300">Language Center</router-link></li>
              <li><a href="/smart-adama-book.pdf" target="_blank" class="hover:text-white transition-colors duration-300">Download PDF</a></li>
            </ul>
          </div>
          
          <div>
            <h4 class="text-white font-bold mb-4 uppercase tracking-wider text-xs">Legal & Community</h4>
            <ul class="flex flex-col gap-3">
              <li><router-link to="/about" class="hover:text-white transition-colors duration-300">About the Developers</router-link></li>
              <li><a href="#" class="hover:text-white transition-colors duration-300">Privacy Policy</a></li>
              <li><a href="#" class="hover:text-white transition-colors duration-300">Terms of Service</a></li>
              <li><a href="#" class="hover:text-white transition-colors duration-300">Contact Us</a></li>
            </ul>
          </div>
        </div>
        
        <div class="pt-8 border-t border-brand-400 flex flex-col md:flex-row items-center justify-between gap-4">
          <p>© 2026 Adama Smart City Ecosystem. All rights reserved.</p>
          <div class="flex gap-4">
            <span class="text-brand-200">English / Afaan Oromoo / Amharic Support Coming Soon</span>
          </div>
        </div>
      </div>
    </footer>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const heroVideoRef = ref<HTMLVideoElement | null>(null)
const bookVideoRef = ref<HTMLVideoElement | null>(null)
const carouselVideoRefs: (HTMLVideoElement | null)[] = []

function setCarouselRef(el: Element | null, i: number) {
  carouselVideoRefs[i - 1] = (el as HTMLVideoElement) ?? null
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
    router.push('/chat')
  } else {
    router.push('/login?redirect=/chat')
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