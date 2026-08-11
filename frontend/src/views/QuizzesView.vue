<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useBooksStore } from '@/stores/books'
import { quizApi } from '@/api/quiz'
import { useI18n } from 'vue-i18n'

const booksStore = useBooksStore()
const { t } = useI18n()

const attempts = ref<any[]>([])
const loadingAttempts = ref(true)

onMounted(async () => {
  if (!booksStore.books || booksStore.books.length === 0) {
    await booksStore.loadBooks()
  }

  try {
    const { data } = await quizApi.listMyAttempts()
    attempts.value = data.attempts || []
  } catch (error) {
    console.error('Failed to load quiz attempts:', error)
  } finally {
    loadingAttempts.value = false
  }
})

const availableChapters = computed(() => {
  const book = booksStore.books?.[0]
  if (!book) return []
  
  let chapters = [...(book.chapters?.data || book.chapters || [])]
  
  // SORTING LOGIC: System Context goes to the end
  chapters.sort((a, b) => {
    const aIntro = a.title === 'Introduction & Preface';
    const bIntro = b.title === 'Introduction & Preface';
    const aSys = a.title.includes('System Context');
    const bSys = b.title.includes('System Context');

    if (aIntro) return -1;
    if (bIntro) return 1;
    if (aSys) return 1;
    if (bSys) return -1;
    
    return a.order - b.order;
  });

  // Filter out Intro because it shouldn't have a quiz
  return chapters.filter((c: any) => c.title !== 'Introduction & Preface')
})

const formatDate = (dateString: string) => {
  if (!dateString) return 'In Progress'
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<template>
  <div class="min-h-screen bg-brand-50 text-[#1e293b] p-6 lg:p-10 font-sans mt-16">
    <div class="max-w-5xl mx-auto space-y-10">
      
      <div class="flex items-center justify-between">
        <div>
          <RouterLink to="/dashboard" class="text-sm text-brand-500 hover:text-brand-500 font-semibold mb-2 inline-block">
            {{ $t('quiz.back_dash') }}
          </RouterLink>
          <h1 class="text-3xl sm:text-4xl font-bold text-brand-500">{{ $t('quiz.title') }}</h1>
          <p class="text-brand-500 mt-2 text-sm sm:text-base">{{ $t('quiz.subtitle') }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-2 space-y-4">
          <h2 class="text-xl font-bold text-brand-500 flex items-center gap-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
            {{ $t('quiz.available') }}
          </h2>
          
          <div v-if="booksStore.loading" class="text-sm text-brand-500 animate-pulse">{{ $t('quiz.loading') }}</div>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div 
              v-for="(chapter, index) in availableChapters" :key="chapter.id"
              class="bg-white border border-brand-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between"
            >
              <div>
                <!-- Show index correctly according to actual chapters in the book ignoring internal orders -->
                <div class="text-xs font-bold text-brand-500 uppercase tracking-wider mb-1">Chapter {{ Number(index) + 1 }}</div>
                <h3 class="font-bold text-brand-500 mb-2 line-clamp-2" :title="chapter.title">{{ chapter.title }}</h3>
              </div>
              <RouterLink :to="`/chapters/${chapter.id}/quiz`" class="mt-4 w-full py-2.5 px-4 bg-brand-300 hover:text-white hover:bg-brand-500 text-white text-sm font-bold rounded-xl text-center transition-colors shadow-sm">
                {{ $t('quiz.take') }}
              </RouterLink>
            </div>
          </div>
        </div>

        <div class="space-y-4">
          <h2 class="text-xl font-bold text-brand-500 flex items-center gap-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ $t('quiz.history') }}
          </h2>

          <div class="bg-white border border-brand-200 rounded-2xl shadow-sm overflow-hidden">
            <div v-if="loadingAttempts" class="p-6 text-sm text-brand-500 text-center animate-pulse">
              {{ $t('quiz.loading_hist') }}
            </div>
            
            <div v-else-if="attempts.length === 0" class="p-8 text-center">
              <div class="text-4xl mb-3">📝</div>
              <p class="text-brand-500 font-medium text-sm">{{ $t('quiz.no_attempts') }}</p>
              <p class="text-brand-500 text-xs mt-1">{{ $t('quiz.no_attempts_sub') }}</p>
            </div>

            <div v-else class="divide-y divide-brand-200/50 max-h-150 overflow-y-auto">
              <div v-for="attempt in attempts" :key="attempt.id" class="p-4 hover:bg-brand-50 transition-colors flex items-center justify-between">
                <div class="min-w-0 pr-3">
                  <p class="text-sm font-bold text-brand-500 truncate">{{ attempt.quiz_title || 'Chapter Quiz' }}</p>
                  <p class="text-xs text-brand-500 mt-0.5">{{ formatDate(attempt.submitted_at) }}</p>
                </div>
                
                <div class="flex flex-col items-end shrink-0">
                  <span class="text-lg font-black" :class="attempt.passed ? 'text-emerald-500' : 'text-rose-500'">
                    {{ attempt.score_pct }}%
                  </span>
                  <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full mt-1" :class="attempt.passed ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                    {{ attempt.passed ? $t('quiz.passed') : $t('quiz.failed') }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>