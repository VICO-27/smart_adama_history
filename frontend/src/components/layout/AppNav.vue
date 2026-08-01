<template>
  <!-- Floating Menu Button (Top Center) -->
  <button
    v-if="isNavHidden"
    @click="forceShowNav = true"
    class="fixed top-2 left-1/2 -translate-x-1/2 z-50 px-4 py-1.5 bg-[#444654] text-gray-300 hover:text-white hover:bg-[#565869] rounded-full shadow-lg border border-gray-600 transition-all cursor-pointer pointer-events-auto flex items-center gap-2 text-sm font-medium"
    aria-label="Show Navigation"
  >
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
    Menu
  </button>

  <!-- Main Navigation Wrapper (Hides automatically on Study route) -->
  <div v-show="!isNavHidden" class="fixed top-4 md:top-6 left-0 right-0 z-50 flex flex-col items-center px-4 pointer-events-none">
    
    <!-- SEARCH MODE -->
    <div v-if="search.isOpen" class="pointer-events-auto w-full max-w-6xl ai-glow-wrap" @dblclick.stop="search.close()">
      <div class="ai-glow-inner flex items-center gap-3 px-5 lg:px-6 py-2.5">
        <span class="text-lg" aria-hidden="true">✦</span>
        <input
          ref="searchInput"
          v-model="query"
          type="text"
          placeholder="Ask about the book, the platform, or the developer…"
          class="ai-search-input flex-1 bg-transparent outline-none border-0 text-[var(--sa-dark)] placeholder:text-[var(--sa-taupe)] text-sm md:text-base"
          @keydown.enter="submitSearch"
          @keydown.esc="search.close()"
        />
        <button
          class="w-7 h-7 flex items-center justify-center rounded-full text-[var(--sa-taupe)] hover:bg-[var(--sa-gray)] hover:text-[var(--sa-dark)] transition"
          aria-label="Close search"
          @click="search.close()"
        >
          ✕
        </button>
      </div>
    </div>

    <!-- NORMAL MODE -->
    <nav
      v-else
      class="pointer-events-auto bg-white rounded-full shadow-[0_4px_24px_rgba(0,0,0,0.06)] border border-[var(--sa-gray)] px-4 md:px-8 w-full max-w-6xl flex items-center justify-between gap-2 transition-all duration-300 relative"
      :class="isScrolled ? 'py-1.5 shadow-[0_8px_32px_rgba(0,0,0,0.1)]' : 'py-2'"
      @dblclick="search.open()"
    >
      <!-- Left: brand -->
      <router-link to="/" class="text-lg md:text-xl font-display font-bold text-[var(--sa-dark)] tracking-tight flex-shrink-0" @click="mobileMenuOpen = false">
        Smart Adama
      </router-link>

      <!-- Center: links (desktop) + search trigger (always) -->
      <div class="flex-1 flex items-center justify-center gap-5 min-w-0">
        <template v-if="auth.isAuthenticated">
          <div class="hidden md:flex items-center gap-5 flex-shrink-0">
            <router-link to="/dashboard" class="text-[var(--sa-dark)] hover:text-[var(--sa-taupe)] text-sm font-medium transition">Home</router-link>
            <router-link to="/study" class="text-[var(--sa-dark)] hover:text-[var(--sa-taupe)] text-sm font-medium transition">Study</router-link>
            <router-link to="/game" class="text-[var(--sa-dark)] hover:text-[var(--sa-taupe)] text-sm font-medium transition">Game</router-link>
            <router-link to="/language" class="text-[var(--sa-dark)] hover:text-[var(--sa-taupe)] text-sm font-medium transition">Language</router-link>
          </div>

          <button
            type="button"
            class="flex items-center gap-1.5 px-3 md:px-4 py-1.5 rounded-full border border-[var(--sa-gray)] text-[var(--sa-dark)] text-xs md:text-sm font-medium hover:border-[var(--sa-dark)] transition-colors min-w-0"
            @click="search.open()"
          >
            <span aria-hidden="true">✦</span>
            <span class="hidden sm:inline truncate">Search with AI</span>
          </button>
        </template>
      </div>

      <!-- Right: account (desktop) + hamburger (mobile) / sign-in -->
      <div class="flex items-center gap-2 md:gap-3 flex-shrink-0 relative">
        <template v-if="auth.isAuthenticated">
          <router-link to="/profile" class="hidden md:block text-[var(--sa-dark)] hover:text-[var(--sa-taupe)] text-sm font-medium transition">
            {{ auth.user?.name || 'Account' }}
          </router-link>

          <button
            type="button"
            class="md:hidden w-9 h-9 flex items-center justify-center rounded-full border border-[var(--sa-gray)] text-[var(--sa-dark)]"
            aria-label="Open menu"
            @click.stop="mobileMenuOpen = !mobileMenuOpen"
          >
            <span v-if="!mobileMenuOpen">☰</span>
            <span v-else>✕</span>
          </button>
          
          <!-- Close button for when the nav is temporarily shown in Study Mode -->
          <button 
            v-if="route.meta.hideNav && forceShowNav" 
            @click="forceShowNav = false"
            class="ml-2 w-8 h-8 flex items-center justify-center bg-[var(--sa-gray)] hover:bg-[var(--sa-taupe)] hover:text-white rounded-full transition-colors text-sm"
          >
            ✕
          </button>
        </template>

        <template v-else>
          <button
            @click="$emit('open-auth', 'login')"
            class="px-4 md:px-5 py-2 rounded-full border border-[var(--sa-gray)] text-[var(--sa-dark)] text-sm font-medium hover:border-[var(--sa-dark)] transition-colors cursor-pointer"
          >
            Sign in
          </button>

          <transition name="fade">
            <SaButton v-show="isScrolled" @click="$emit('open-auth', 'register')" variant="primary" size="sm" class="hidden sm:inline-flex rounded-full shadow-sm text-sm bg-black text-white">
              Get Started
            </SaButton>
          </transition>
        </template>

        <!-- Mobile dropdown -->
        <transition name="dropdown">
          <div
            v-if="mobileMenuOpen"
            ref="mobileMenu"
            class="md:hidden absolute right-0 top-12 w-48 bg-white rounded-2xl border border-[var(--sa-gray)] shadow-[0_16px_40px_rgba(0,0,0,0.12)] py-2 flex flex-col"
          >
            <router-link to="/dashboard" class="px-4 py-2.5 text-sm font-medium text-[var(--sa-dark)] hover:bg-[var(--sa-gray)]/50" @click="mobileMenuOpen = false">Home</router-link>
            <router-link to="/study" class="px-4 py-2.5 text-sm font-medium text-[var(--sa-dark)] hover:bg-[var(--sa-gray)]/50" @click="mobileMenuOpen = false">Study</router-link>
            <router-link to="/game" class="px-4 py-2.5 text-sm font-medium text-[var(--sa-dark)] hover:bg-[var(--sa-gray)]/50" @click="mobileMenuOpen = false">Game</router-link>
            <router-link to="/language" class="px-4 py-2.5 text-sm font-medium text-[var(--sa-dark)] hover:bg-[var(--sa-gray)]/50" @click="mobileMenuOpen = false">Language</router-link>
            <div class="h-px bg-[var(--sa-gray)] my-1"></div>
            <router-link to="/profile" class="px-4 py-2.5 text-sm font-medium text-[var(--sa-dark)] hover:bg-[var(--sa-gray)]/50" @click="mobileMenuOpen = false">
              {{ auth.user?.name || 'Account' }}
            </router-link>
          </div>
        </transition>
      </div>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { ref, nextTick, watch, onMounted, onUnmounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useSearchUIStore } from '@/stores/searchUI'
import SaButton from '@/components/ui/SaButton.vue'

const auth = useAuthStore()
const search = useSearchUIStore()
const router = useRouter()
const route = useRoute() 

const isScrolled = ref(false)
const query = ref('')
const searchInput = ref<HTMLInputElement | null>(null)
const mobileMenuOpen = ref(false)
const mobileMenu = ref<HTMLElement | null>(null)

const forceShowNav = ref(false)
const isNavHidden = computed(() => route.meta.hideNav && !forceShowNav.value)

const emit = defineEmits(['open-auth'])

const handleScroll = () => {
  isScrolled.value = window.scrollY > 20
}

const handleClickOutside = (e: MouseEvent) => {
  if (mobileMenuOpen.value && mobileMenu.value && !mobileMenu.value.contains(e.target as Node)) {
    mobileMenuOpen.value = false
  }
}

watch(() => route.path, () => {
  forceShowNav.value = false
})

watch(() => search.isOpen, async (open) => {
  if (open) {
    mobileMenuOpen.value = false
    await nextTick()
    searchInput.value?.focus()
  } else {
    query.value = ''
  }
})

function submitSearch() {
  const q = query.value.trim()
  if (!q) return
  router.push({ path: '/study', query: { q } })
  search.close()
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
  document.addEventListener('click', handleClickOutside)
})
onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1), transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.fade-enter-from, .fade-leave-to {
  opacity: 0; transform: translateX(10px) scale(0.95);
}

.dropdown-enter-active, .dropdown-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.dropdown-enter-from, .dropdown-leave-to {
  opacity: 0; transform: translateY(-8px) scale(0.97);
}

.ai-search-input:focus,
.ai-search-input:focus-visible {
  outline: none !important;
  box-shadow: none !important;
}

@property --glow-angle {
  syntax: '<angle>';
  inherits: false;
  initial-value: 0deg;
}

.ai-glow-wrap {
  border-radius: 9999px;
  padding: 2px;
  background: conic-gradient(
    from var(--glow-angle),
    #0A0A0A,
    #9A9A9A,
    #FFFFFF,
    #9A9A9A,
    #0A0A0A
  );
  animation: glow-spin 3.5s linear infinite;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.14);
}

.ai-glow-inner {
  background: white;
  border-radius: 9999px;
}

@keyframes glow-spin {
  to { --glow-angle: 360deg; }
}

@media (prefers-reduced-motion: reduce) {
  .ai-glow-wrap { animation: none; }
}
</style>