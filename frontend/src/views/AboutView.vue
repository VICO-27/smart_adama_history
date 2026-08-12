<template>
  <div class="bg-[#030303] text-[#e2e8f0] min-h-screen font-sans selection:bg-brand-500 selection:text-white relative overflow-hidden">
    
    <!-- Cinematic Film Grain Overlay -->
    <div class="pointer-events-none fixed inset-0 z-50 opacity-[0.03] mix-blend-overlay" style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 200 200%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noiseFilter%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.65%22 numOctaves=%223%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noiseFilter)%22/%3E%3C/svg%3E');"></div>

    <!-- Minimal Header -->
    <header class="fixed top-0 w-full px-6 md:px-12 py-8 z-40 flex justify-between items-center mix-blend-difference">
      <router-link to="/" class="text-sm font-bold tracking-[0.2em] uppercase text-white hover:text-brand-300 transition-colors">
        {{ $t('nav.brand') }}
      </router-link>
      <router-link to="/dashboard" class="text-xs font-mono font-bold uppercase tracking-widest text-white/60 hover:text-white transition-colors relative group">
        Return to Dashboard
        <span class="absolute -bottom-2 left-0 w-0 h-px bg-white transition-all duration-500 group-hover:w-full"></span>
      </router-link>
    </header>

    <!-- 1. THE HERO (Origin) -->
    <section class="relative w-full min-h-screen flex flex-col justify-center px-6 md:px-12 pt-20">
      <div class="max-w-7xl mx-auto w-full">
        <p class="reveal-text text-xs md:text-sm font-mono font-bold tracking-[0.3em] text-white/50 uppercase mb-8 md:mb-12">
          {{ $t('about.tag') }}
        </p>
        
        <h1 class="font-display font-medium text-[12vw] md:text-[8vw] leading-[0.9] tracking-tighter text-white mb-12">
          <span class="block reveal-up">{{ $t('about.hero1') }}</span>
          <span class="block reveal-up delay-100">{{ $t('about.hero2') }}</span>
          <span class="block reveal-up delay-200 text-white/40">{{ $t('about.hero3') }}</span>
        </h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-12 mt-20 md:mt-32">
          <div class="md:col-span-1 reveal-opacity delay-300">
            <p class="text-white/50 text-xs font-mono tracking-[0.2em] uppercase mb-4">{{ $t('about.mission') }}</p>
            <div class="w-8 h-px bg-white/20 mb-6"></div>
          </div>
          <div class="md:col-span-2 reveal-opacity delay-400">
            <p class="text-xl md:text-3xl font-medium leading-tight text-white/80 max-w-3xl">
              {{ $t('about.mission_desc') }}
            </p>
          </div>
        </div>
      </div>
      
      <!-- Scroll Indicator -->
      <div class="absolute bottom-12 left-6 md:left-12 flex items-center gap-4 reveal-opacity delay-700">
        <div class="w-[1px] h-12 bg-gradient-to-b from-white to-transparent"></div>
        <span class="text-[10px] font-mono tracking-widest text-white/40 uppercase rotate-180" style="writing-mode: vertical-rl;">Scroll</span>
      </div>
    </section>

    <!-- 2. THE PHILOSOPHY (Cinematic Typographic Scroll) -->
    <section class="py-40 md:py-64 px-6 md:px-12 bg-[#030303] relative z-10">
      <div class="max-w-6xl mx-auto text-center">
        <p class="text-brand-400 text-xs font-mono tracking-[0.3em] uppercase mb-12 reveal-up">{{ $t('about.approach') }}</p>
        
        <h2 class="text-3xl md:text-5xl lg:text-7xl font-display font-medium leading-[1.1] tracking-tight uppercase">
          <span class="block text-white/30 reveal-up">{{ $t('about.approach1') }}</span>
          <span class="block text-white/30 reveal-up delay-100">{{ $t('about.approach2') }}</span>
          <span class="block text-white reveal-up delay-200 mt-8">{{ $t('about.approach3') }}</span>
          <span class="block text-white reveal-up delay-300">{{ $t('about.approach4') }}</span>
        </h2>

        <div class="mt-32 reveal-opacity">
          <div class="inline-block border border-white/10 bg-white/5 backdrop-blur-md px-10 py-6 rounded-full">
            <h3 class="text-xl md:text-3xl font-display font-bold text-white tracking-wide">
              {{ $t('about.ai_support') }}
            </h3>
          </div>
        </div>
      </div>
    </section>

    <!-- 3. THE ARCHITECTURE (Craft & Technology) -->
    <section class="py-32 px-6 md:px-12 border-t border-white/5 bg-gradient-to-b from-[#030303] to-[#0a0a0a]">
      <div class="max-w-7xl mx-auto">
        <div class="mb-24 reveal-opacity">
          <p class="text-white/50 text-xs font-mono tracking-[0.2em] uppercase mb-4">{{ $t('about.ecosystem') }}</p>
          <h2 class="text-4xl md:text-6xl font-display font-bold text-white">{{ $t('about.tools') }}</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-x-8 gap-y-16">
          <div class="border-t border-white/20 pt-6 reveal-up delay-100">
            <span class="text-brand-400 font-mono text-xs tracking-widest mb-6 block">01 / FOUNDATION</span>
            <h4 class="text-2xl text-white font-medium mb-3">PostgreSQL</h4>
            <p class="text-white/50 text-sm leading-relaxed">Robust relational database design powering the entire platform architecture.</p>
          </div>
          <div class="border-t border-white/20 pt-6 reveal-up delay-200">
            <span class="text-brand-400 font-mono text-xs tracking-widest mb-6 block">02 / LOGIC</span>
            <h4 class="text-2xl text-white font-medium mb-3">Laravel</h4>
            <p class="text-white/50 text-sm leading-relaxed">Secure, scalable backend infrastructure managing RESTful APIs and authentication.</p>
          </div>
          <div class="border-t border-white/20 pt-6 reveal-up delay-300">
            <span class="text-brand-400 font-mono text-xs tracking-widest mb-6 block">03 / INTERFACE</span>
            <h4 class="text-2xl text-white font-medium mb-3">Vue.js</h4>
            <p class="text-white/50 text-sm leading-relaxed">Reactive frontend development delivering a seamless, premium user experience.</p>
          </div>
          <div class="border-t border-white/20 pt-6 reveal-up delay-400">
            <span class="text-brand-400 font-mono text-xs tracking-widest mb-6 block">04 / INTELLIGENCE</span>
            <h4 class="text-2xl text-white font-medium mb-3">AI / RAG</h4>
            <p class="text-white/50 text-sm leading-relaxed">Advanced LLM integration providing grounded answers with exact book references.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- 4. THE BUILDERS (Sticky Scroll Storytelling) -->
    <section class="py-32 md:py-48 px-6 md:px-12 relative">
      <div class="max-w-7xl mx-auto flex flex-col lg:flex-row gap-20">
        
        <!-- Sticky Left Column -->
        <div class="lg:w-1/3 relative">
          <div class="sticky top-40 reveal-opacity">
            <p class="text-white/50 text-xs font-mono tracking-[0.2em] uppercase mb-4">{{ $t('about.interns') }}</p>
            <h2 class="text-5xl md:text-7xl font-display font-medium text-white mb-6">{{ $t('about.engineers') }}</h2>
            <p class="text-white/60 leading-relaxed mb-8">
              {{ $t('about.engineers_desc') }}
            </p>
            <div class="flex items-center gap-3 text-xs font-mono tracking-widest text-brand-400 uppercase">
              <span class="w-2 h-2 bg-brand-400 rounded-full animate-pulse"></span>
              Scroll to meet the team
            </div>
          </div>
        </div>

        <!-- Scrolling Right Column -->
        <div class="lg:w-2/3 flex flex-col gap-32 pt-20 lg:pt-0">
          
          <!-- Ashenafi -->
          <div class="group reveal-up">
            <div class="flex flex-col md:flex-row gap-8 items-start">
              <div class="w-full md:w-48 h-64 md:h-64 shrink-0 overflow-hidden rounded-sm bg-white/5 relative">
                <div class="absolute inset-0 bg-gradient-to-br from-brand-600/20 to-black mix-blend-multiply z-10 transition-opacity group-hover:opacity-0"></div>
                <img src="/profile/ashenafi.png" alt="Ashenafi Deresa" class="w-full h-full object-cover filter grayscale group-hover:grayscale-0 transition-all duration-700 scale-105 group-hover:scale-100" />
              </div>
              <div>
                <span class="text-brand-400 font-mono text-[10px] tracking-widest uppercase border border-brand-400/30 px-3 py-1 rounded-full mb-6 inline-block">Project Manager & Technical Integration Lead</span>
                <h3 class="text-3xl md:text-4xl font-display font-bold text-white mb-4">Ashenafi Deresa (Vico)</h3>
                <p class="text-white/60 text-base leading-relaxed mb-6">
                  Computer Science and Engineering student responsible for architecture oversight, full-stack integration, and stakeholder communication. Passionate about leveraging AI to build systems that scale and solve meaningful problems.
                </p>
                <div class="flex gap-4">
                  <a href="https://github.com/VICO-27" target="_blank" class="text-white hover:text-brand-400 transition-colors uppercase text-xs font-mono tracking-widest flex items-center gap-2">
                    Github <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                  </a>
                  <a href="https://www.linkedin.com/in/ashenafi-deresa-computer-science-engineer/" target="_blank" class="text-white hover:text-brand-400 transition-colors uppercase text-xs font-mono tracking-widest flex items-center gap-2">
                    LinkedIn <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Nigusu -->
          <div class="group reveal-up">
            <div class="flex flex-col md:flex-row gap-8 items-start opacity-70 hover:opacity-100 transition-opacity duration-500">
              <div class="w-24 h-24 shrink-0 overflow-hidden rounded-full bg-white/5 border border-white/10 relative">
                <img src="/profile/nigusu.png" alt="Nigusu Wario" class="w-full h-full object-cover filter grayscale group-hover:grayscale-0 transition-all duration-700" />
              </div>
              <div>
                <h3 class="text-2xl font-display font-bold text-white mb-2">Nigusu Wario</h3>
                <p class="text-white/40 text-sm font-mono tracking-wide uppercase mb-4">QA, DevOps, Documentation & Release Lead</p>
                <p class="text-white/60 text-sm leading-relaxed">
                  Driving testing, CI/CD pipelines, and ensuring seamless release management across the ecosystem.
                </p>
              </div>
            </div>
          </div>

          <!-- Kidus -->
          <div class="group reveal-up">
            <div class="flex flex-col md:flex-row gap-8 items-start opacity-70 hover:opacity-100 transition-opacity duration-500">
              <div class="w-24 h-24 shrink-0 overflow-hidden rounded-full bg-white/5 border border-white/10 relative">
                <img src="/profile/kidus.png" alt="Kidus Tilahun" class="w-full h-full object-cover filter grayscale group-hover:grayscale-0 transition-all duration-700" />
              </div>
              <div>
                <h3 class="text-2xl font-display font-bold text-white mb-2">Kidus Tilahun Shibeshi</h3>
                <p class="text-white/40 text-sm font-mono tracking-wide uppercase mb-4">Backend & PostgreSQL Lead</p>
                <p class="text-white/60 text-sm leading-relaxed">
                  Architecting high-performance RESTful APIs and database structures to power the platform's core.
                </p>
              </div>
            </div>
          </div>

          <!-- Getamesay -->
          <div class="group reveal-up">
            <div class="flex flex-col md:flex-row gap-8 items-start opacity-70 hover:opacity-100 transition-opacity duration-500">
              <div class="w-24 h-24 shrink-0 overflow-hidden rounded-full bg-white/5 border border-white/10 relative">
                <img src="/profile/getinet.png" alt="Getamesay Mekcha" class="w-full h-full object-cover filter grayscale group-hover:grayscale-0 transition-all duration-700" />
              </div>
              <div>
                <h3 class="text-2xl font-display font-bold text-white mb-2">Getamesay Mekcha Abebe</h3>
                <p class="text-white/40 text-sm font-mono tracking-wide uppercase mb-4">Frontend & UI/UX Lead</p>
                <p class="text-white/60 text-sm leading-relaxed">
                  Crafting responsive, intuitive user interfaces and defining the visual experience using Vue.js.
                </p>
              </div>
            </div>
          </div>

          <!-- Abinet -->
          <div class="group reveal-up">
            <div class="flex flex-col md:flex-row gap-8 items-start opacity-70 hover:opacity-100 transition-opacity duration-500">
              <div class="w-24 h-24 shrink-0 overflow-hidden rounded-full bg-white/5 border border-white/10 relative">
                <img src="/profile/abinet.jpg" alt="Abinet Tesfaye" class="w-full h-full object-cover filter grayscale group-hover:grayscale-0 transition-all duration-700" />
              </div>
              <div>
                <h3 class="text-2xl font-display font-bold text-white mb-2">Abinet Tesfaye Ayele</h3>
                <p class="text-white/40 text-sm font-mono tracking-wide uppercase mb-4">AI/RAG & Learning Intelligence Lead</p>
                <p class="text-white/60 text-sm leading-relaxed">
                  Integrating advanced Language Models and Natural Language Processing to ground answers with references.
                </p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- 5. CONTACT US FORM -->
    <section class="py-32 px-6 md:px-12 bg-[#0a0a0a] border-t border-white/5 relative z-10">
      <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-16">
        
        <div class="reveal-opacity">
          <p class="text-brand-400 text-xs font-mono tracking-[0.2em] uppercase mb-4">Get in Touch</p>
          <h2 class="text-4xl md:text-6xl font-display font-bold text-white mb-6">Contact Us.</h2>
          <p class="text-white/60 leading-relaxed mb-10 max-w-md">
            Have a question about the Smart Adama platform, the book, or our tech stack? Reach out directly to the lead developer.
          </p>

          <div class="flex flex-col gap-6">
            <a href="mailto:ashenafi.deresa.cse@gmail.com" class="flex items-center gap-4 text-white hover:text-brand-400 transition-colors group">
              <div class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center border border-white/10 group-hover:border-brand-400/50 transition-colors text-xl">
                ✉️
              </div>
              <span class="font-mono text-sm tracking-wide">ashenafi.deresa.cse@gmail.com</span>
            </a>
            <a href="https://t.me/vico_27" target="_blank" class="flex items-center gap-4 text-white hover:text-brand-400 transition-colors group">
              <div class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center border border-white/10 group-hover:border-brand-400/50 transition-colors text-xl">
                ✈️
              </div>
              <span class="font-mono text-sm tracking-wide">@vico_27 on Telegram</span>
            </a>
            <a href="tel:+251930034371" class="flex items-center gap-4 text-white hover:text-brand-400 transition-colors group">
              <div class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center border border-white/10 group-hover:border-brand-400/50 transition-colors text-xl">
                📞
              </div>
              <span class="font-mono text-sm tracking-wide">+251 93 003 4371</span>
            </a>
          </div>
        </div>

        <div class="reveal-up delay-200">
          <div class="bg-white/5 border border-white/10 p-8 md:p-10 rounded-[2.5rem] backdrop-blur-sm shadow-2xl">
            <form @submit.prevent class="flex flex-col gap-6">
              <div class="flex flex-col gap-2">
                <label class="text-xs font-mono tracking-widest text-white/50 uppercase">Your Name</label>
                <input v-model="contactForm.name" type="text" required class="bg-black/50 border border-white/10 rounded-xl px-5 py-4 text-white focus:outline-none focus:border-brand-500 transition-colors" placeholder="John Doe" />
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-xs font-mono tracking-widest text-white/50 uppercase">Your Email</label>
                <input v-model="contactForm.email" type="email" required class="bg-black/50 border border-white/10 rounded-xl px-5 py-4 text-white focus:outline-none focus:border-brand-500 transition-colors" placeholder="john@example.com" />
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-xs font-mono tracking-widest text-white/50 uppercase">Message</label>
                <textarea v-model="contactForm.message" required rows="4" class="bg-black/50 border border-white/10 rounded-xl px-5 py-4 text-white focus:outline-none focus:border-brand-500 transition-colors resize-none" placeholder="How can we help you?"></textarea>
              </div>

              <div class="flex flex-col sm:flex-row gap-4 mt-4">
                <button type="button" @click="sendViaEmail" class="flex-1 bg-white text-black font-bold py-4 rounded-xl hover:bg-brand-50 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2">
                  Send via Email
                </button>
                <button type="button" @click="sendViaTelegram" class="flex-1 bg-[#0088cc] text-white font-bold py-4 rounded-xl hover:bg-[#0099e6] hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2">
                  Send via Telegram
                </button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </section>

    <!-- 6. FINAL CTA (Vision) -->
    <section class="py-40 md:py-64 px-6 md:px-12 bg-black flex flex-col items-center justify-center text-center relative">
      <div class="absolute inset-0 bg-gradient-to-t from-brand-900/20 to-transparent pointer-events-none"></div>
      <div class="relative z-10 max-w-4xl">
        <h2 class="text-5xl md:text-8xl font-display font-medium text-white mb-12 tracking-tight reveal-up">
          {{ $t('about.future') }} <br/><span class="text-white/40">{{ $t('about.future_sub') }}</span>
        </h2>
        <div class="flex flex-wrap justify-center gap-8 reveal-opacity delay-200">
          <router-link to="/study" class="text-white hover:text-brand-400 text-sm font-mono uppercase tracking-widest transition-colors pb-1 border-b border-white/20 hover:border-brand-400">
            {{ $t('about.exp') }}
          </router-link>
          <a href="https://github.com/VICO-27" target="_blank" class="text-white hover:text-brand-400 text-sm font-mono uppercase tracking-widest transition-colors pb-1 border-b border-white/20 hover:border-brand-400">
            {{ $t('about.code') }}
          </a>
        </div>
      </div>
    </section>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

// Form State
const contactForm = ref({
  name: '',
  email: '',
  message: ''
})

// Submission Handlers
const sendViaEmail = () => {
  if (!contactForm.value.name || !contactForm.value.message) {
    alert('Please fill in your name and message before sending.')
    return
  }
  const subject = encodeURIComponent(`Smart Adama Inquiry from ${contactForm.value.name}`)
  const body = encodeURIComponent(`Name: ${contactForm.value.name}\nEmail: ${contactForm.value.email}\n\nMessage:\n${contactForm.value.message}`)
  window.location.href = `mailto:ashenafi.deresa.cse@gmail.com?subject=${subject}&body=${body}`
}

const sendViaTelegram = () => {
  if (!contactForm.value.name || !contactForm.value.message) {
    alert('Please fill in your name and message before sending.')
    return
  }
  const text = encodeURIComponent(`*New Smart Adama Inquiry*\n\n*Name:* ${contactForm.value.name}\n*Email:* ${contactForm.value.email}\n\n*Message:*\n${contactForm.value.message}`)
  window.open(`https://t.me/vico_27?text=${text}`, '_blank')
}

// Scroll Animations
let observer: IntersectionObserver | null = null

onMounted(() => {
  observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible')
        observer?.unobserve(entry.target)
      }
    })
  }, {
    rootMargin: '0px 0px -10% 0px',
    threshold: 0.1
  })

  const elements = document.querySelectorAll('.reveal-up, .reveal-opacity, .reveal-text')
  elements.forEach((el) => observer?.observe(el))
})

onUnmounted(() => {
  if (observer) {
    observer.disconnect()
  }
})
</script>

<style scoped>
.reveal-up {
  opacity: 0;
  transform: translateY(40px);
  transition: opacity 1s cubic-bezier(0.2, 0.8, 0.2, 1), 
              transform 1.2s cubic-bezier(0.2, 0.8, 0.2, 1);
  will-change: opacity, transform;
}

.reveal-opacity {
  opacity: 0;
  transition: opacity 1.5s cubic-bezier(0.2, 0.8, 0.2, 1);
  will-change: opacity;
}

.reveal-text {
  clip-path: polygon(0 100%, 100% 100%, 100% 100%, 0% 100%);
  opacity: 0;
  transform: translateY(20px);
  transition: clip-path 1.2s cubic-bezier(0.16, 1, 0.3, 1),
              opacity 1.2s cubic-bezier(0.16, 1, 0.3, 1),
              transform 1.2s cubic-bezier(0.16, 1, 0.3, 1);
  will-change: clip-path, opacity, transform;
}

.is-visible.reveal-up {
  opacity: 1;
  transform: translateY(0);
}

.is-visible.reveal-opacity {
  opacity: 1;
}

.is-visible.reveal-text {
  clip-path: polygon(0 0, 100% 0, 100% 100%, 0% 100%);
  opacity: 1;
  transform: translateY(0);
}

.delay-100 { transition-delay: 100ms; }
.delay-200 { transition-delay: 200ms; }
.delay-300 { transition-delay: 300ms; }
.delay-400 { transition-delay: 400ms; }
.delay-700 { transition-delay: 700ms; }

@media (prefers-reduced-motion: reduce) {
  .reveal-up, .reveal-opacity, .reveal-text {
    opacity: 1 !important;
    transform: none !important;
    clip-path: none !important;
    transition: none !important;
  }
}
</style>