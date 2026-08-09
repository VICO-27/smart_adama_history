<template>
  <!-- Main Navigation Wrapper -->
  <div class="fixed top-4 md:top-6 left-0 right-0 z-50 flex flex-col items-center px-4 pointer-events-none">

    <!-- SEARCH MODE WITH INSTANT AI OUTPUT DROPDOWN -->
    <div v-if="search.isOpen" class="pointer-events-auto w-full max-w-2xl ai-glow-wrap relative" @click.stop>
      <div class="ai-glow-inner flex items-center gap-3 px-5 lg:px-6 py-3 bg-white text-[var(--sa-dark)]">
        <span class="text-lg text-emerald-500 animate-pulse" aria-hidden="true">✦</span>
        <input
          ref="searchInput"
          v-model="query"
          type="text"
          :placeholder="$t('nav.search_placeholder')"
          class="ai-search-input flex-1 bg-transparent outline-none border-0 text-slate-900 placeholder:text-slate-400 text-sm md:text-base"
          @input="handleSearchInput"
          @keydown.enter="submitSearch"
          @keydown.esc="search.close()"
        />
        <span v-if="isSearching" class="text-xs text-brand-400 animate-spin">⏳</span>
        <button
          class="w-7 h-7 flex items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-900 transition"
          aria-label="Close search"
          @click="search.close()"
        >
          ✕
        </button>
      </div>

      <!-- Instant AI Output & Source Results Dropdown -->
      <div v-if="aiAnswer || searchResults.length > 0 || hasSearched" class="absolute top-full left-0 right-0 mt-3 bg-white border border-slate-200 rounded-2xl shadow-2xl p-5 max-h-[480px] overflow-y-auto z-50 space-y-4">
        
        <!-- Instant AI Answer Box -->
        <div v-if="aiAnswer" class="bg-gradient-to-br from-brand-50 to-emerald-50/40 border border-emerald-200/60 rounded-xl p-4 text-slate-800 shadow-xs">
          <div class="flex items-center gap-2 text-xs font-bold text-emerald-600 uppercase tracking-wider mb-1.5">
            <span>✦ Smart Adama AI Answer</span>
          </div>
          <p class="text-sm leading-relaxed font-medium text-slate-700">{{ aiAnswer }}</p>
        </div>

        <!-- Matching Book Sections -->
        <div v-if="searchResults.length > 0" class="space-y-2">
          <p class="text-[11px] font-mono font-bold uppercase tracking-wider text-slate-400 px-1">Grounded Book References</p>
          <div 
            v-for="res in searchResults" 
            :key="res.id"
            @click="navigateToResult(res.chapter_id)"
            class="p-3 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors border border-slate-100 hover:border-slate-300 flex flex-col gap-1"
          >
            <div class="flex items-center justify-between text-xs text-slate-500">
              <span class="font-bold truncate max-w-[250px]">{{ res.chapter }} — {{ res.title }}</span>
              <span class="font-mono bg-slate-100 px-2 py-0.5 rounded-full text-[10px] text-emerald-600 font-semibold">{{ Math.round(res.similarity * 100) }}% Match</span>
            </div>
            <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">{{ res.snippet }}</p>
          </div>
        </div>

        <div v-else-if="hasSearched && !isSearching && !aiAnswer" class="p-6 text-center text-sm text-slate-500">
          No information found in the Smart Adama content for "{{ query }}".
        </div>
      </div>
    </div>

    <!-- NORMAL MODE -->
    <nav
      v-else
      class="pointer-events-auto bg-white rounded-full shadow-[0_4px_24px_rgba(0,0,0,0.06)] border border-[var(--sa-gray)] px-4 md:px-8 w-full max-w-6xl flex items-center justify-between gap-2 transition-all duration-300 relative"
      :class="isScrolled ? 'py-1.5 shadow-[0_8px_32px_rgba(0,0,0,0.1)]' : 'py-2'"
      @dblclick="search.open()"
    >
      <router-link to="/" class="text-lg md:text-xl font-display font-bold text-[var(--sa-dark)] tracking-tight flex-shrink-0" @click="mobileMenuOpen = false">
        {{ $t('nav.brand') }}
      </router-link>

      <div class="flex-1 flex items-center justify-center gap-5 min-w-0">
        <template v-if="auth.isAuthenticated">
          <div class="hidden md:flex items-center gap-5 flex-shrink-0">
            <router-link to="/dashboard" class="text-[var(--sa-dark)] hover:text-[var(--sa-taupe)] text-sm font-medium transition">{{ $t('nav.home') }}</router-link>
            <router-link to="/study" class="text-[var(--sa-dark)] hover:text-[var(--sa-taupe)] text-sm font-medium transition">{{ $t('nav.study') }}</router-link>
            <router-link to="/game" class="text-[var(--sa-dark)] hover:text-[var(--sa-taupe)] text-sm font-medium transition">{{ $t('nav.game') }}</router-link>
          </div>

          <button
            type="button"
            class="flex items-center gap-1.5 px-3 md:px-4 py-1.5 rounded-full border border-[var(--sa-gray)] text-[var(--sa-dark)] text-xs md:text-sm font-medium hover:border-[var(--sa-dark)] transition-colors min-w-0"
            @click="search.open()"
          >
            <span class="text-emerald-500" aria-hidden="true">✦</span>
            <span class="hidden sm:inline truncate">{{ $t('nav.search') }}</span>
          </button>
        </template>
      </div>

      <div class="flex items-center gap-3 md:gap-4 flex-shrink-0 relative">
        <div class="relative z-[60] py-2" @mouseenter="openLangMenu" @mouseleave="closeLangMenu">
          <button 
            @click="langMenuOpen = !langMenuOpen"
            class="text-[var(--sa-dark)] hover:text-[var(--sa-taupe)] text-sm font-medium transition uppercase tracking-widest flex items-center gap-1"
          >
            {{ currentLangDisplay }}
            <svg class="w-3 h-3 transition-transform" :class="{'rotate-180': langMenuOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
          </button>

          <transition name="dropdown">
            <div 
              v-if="langMenuOpen" 
              class="absolute top-full right-0 mt-0 w-32 bg-white rounded-xl border border-[var(--sa-gray)] shadow-[0_16px_40px_rgba(0,0,0,0.12)] py-2 flex flex-col"
            >
              <button @click="changeLanguage('en')" class="px-4 py-2 text-sm text-left font-medium hover:bg-[var(--sa-gray)]/50 transition-colors" :class="{ 'text-emerald-500 font-bold': locale === 'en', 'text-[var(--sa-dark)]': locale !== 'en' }">English</button>
              <button @click="changeLanguage('om')" class="px-4 py-2 text-sm text-left font-medium hover:bg-[var(--sa-gray)]/50 transition-colors" :class="{ 'text-emerald-500 font-bold': locale === 'om', 'text-[var(--sa-dark)]': locale !== 'om' }">Afaan Oromoo</button>
              <button @click="changeLanguage('am')" class="px-4 py-2 text-sm text-left font-medium hover:bg-[var(--sa-gray)]/50 transition-colors" :class="{ 'text-emerald-500 font-bold': locale === 'am', 'text-[var(--sa-dark)]': locale !== 'am' }">አማርኛ</button>
            </div>
          </transition>
        </div>

        <template v-if="auth.isAuthenticated">
          <router-link to="/profile" class="hidden md:block text-[var(--sa-dark)] hover:text-[var(--sa-taupe)] text-sm font-medium transition">
            {{ auth.user?.name || $t('nav.account') }}
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
        </template>

        <template v-else>
          <button
            @click="$emit('open-auth', 'login')"
            class="px-4 md:px-5 py-2 rounded-full border border-[var(--sa-gray)] text-[var(--sa-dark)] text-sm font-medium hover:border-[var(--sa-dark)] transition-colors cursor-pointer"
          >
            {{ $t('nav.signin') }}
          </button>

          <transition name="fade">
            <SaButton v-show="isScrolled" @click="$emit('open-auth', 'register')" variant="primary" size="sm" class="hidden sm:inline-flex rounded-full shadow-sm text-sm bg-black text-white">
              {{ $t('nav.get_started') }}
            </SaButton>
          </transition>
        </template>
      </div>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, watch, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useSearchUIStore } from '@/stores/searchUI'
import SaButton from '@/components/ui/SaButton.vue'
import { useI18n } from 'vue-i18n'
import { setLanguage } from '@/i18n'
import apiClient from '@/api/client'

const auth = useAuthStore()
const search = useSearchUIStore()
const router = useRouter()
const { t, locale } = useI18n()

const isScrolled = ref(false)
const query = ref('')
const searchInput = ref<HTMLInputElement | null>(null)
const mobileMenuOpen = ref(false)
const langMenuOpen = ref(false)

const searchResults = ref<any[]>([])
const aiAnswer = ref<string | null>(null)
const isSearching = ref(false)
const hasSearched = ref(false)
let searchTimeout: any = null
let langTimeout: any = null

const openLangMenu = () => {
  if (langTimeout) clearTimeout(langTimeout)
  langMenuOpen.value = true
}

const closeLangMenu = () => {
  langTimeout = setTimeout(() => {
    langMenuOpen.value = false
  }, 250)
}

const currentLangDisplay = computed(() => {
  if (locale.value === 'am') return 'AM'
  if (locale.value === 'om') return 'OM'
  return 'EN'
})

const changeLanguage = (lang: 'en' | 'am' | 'om') => {
  setLanguage(lang)
  langMenuOpen.value = false
}

const handleScroll = () => {
  isScrolled.value = window.scrollY > 20
}

watch(() => search.isOpen, async (open) => {
  if (open) {
    mobileMenuOpen.value = false
    await nextTick()
    searchInput.value?.focus()
  } else {
    query.value = ''
    searchResults.value = []
    aiAnswer.value = null
    hasSearched.value = false
  }
})

const handleSearchInput = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  const q = query.value.trim()
  if (!q) {
    searchResults.value = []
    aiAnswer.value = null
    hasSearched.value = false
    return
  }

  isSearching.value = true
  searchTimeout = setTimeout(async () => {
    try {
      const { data } = await apiClient.get('/v1/ai-search', { params: { q } })
      searchResults.value = data.results || []
      aiAnswer.value = data.ai_answer || null
      hasSearched.value = true
    } catch (e) {
      console.error('AI Search failed:', e)
      searchResults.value = []
      aiAnswer.value = null
    } finally {
      isSearching.value = false
    }
  }, 400)
}

function submitSearch() {
  const q = query.value.trim()
  if (!q) return
  
  // If we already have an AI answer or results, keep the user on the page 
  // and let them read the dropdown, or press enter a second time to go to study.
  if (aiAnswer.value || searchResults.value.length > 0) {
    return; // Keep dropdown open with the output
  }

  // Fallback to study redirection if no results are showing yet
  router.push({ path: '/study', query: { q } })
  search.close()
}

function navigateToResult(chapterId: string) {
  router.push({ path: '/study', query: { chapter: chapterId } })
  search.close()
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
})
onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
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
    #10B981,
    #6366F1,
    #3B82F6,
    #6366F1,
    #10B981
  );
  animation: glow-spin 3.5s linear infinite;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.ai-glow-inner {
  border-radius: 9999px;
}

@keyframes glow-spin {
  to { --glow-angle: 360deg; }
}
</style>