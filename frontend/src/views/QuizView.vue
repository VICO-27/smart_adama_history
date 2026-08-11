<script setup lang="ts">
import { onMounted, computed, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { useBooksStore }  from '@/stores/books'
import { useQuizStore }   from '@/stores/quiz'
import { useProgressStore } from '@/stores/progress'
import AppShell from '@/components/layout/AppShell.vue'
import SaButton from '@/components/ui/SaButton.vue'
import SaCard   from '@/components/ui/SaCard.vue'
import { useI18n } from 'vue-i18n'

const route    = useRoute()
const router   = useRouter()
const books    = useBooksStore()
const quiz     = useQuizStore()
const progress = useProgressStore()
const { t } = useI18n()

const chapterId = computed(() => route.params.chapterId as string)
const step      = ref<'loading' | 'quiz' | 'result' | 'error'>('loading')
const submitting = ref(false)
const errorMsg   = ref('')

const currentQuestionIndex = ref(0)

onMounted(async () => {
  try {
    await books.loadChapterQuiz(chapterId.value)
    if (!books.currentQuiz) { 
      errorMsg.value = 'There are no quizzes available for this chapter yet.'
      step.value = 'error'
      return 
    }
    await quiz.startQuiz(books.currentQuiz.id)
    step.value = 'quiz'
    currentQuestionIndex.value = 0
  } catch(e) {
    errorMsg.value = 'There are no quizzes available for this chapter yet.'
    step.value = 'error'
  }
})

const currentQ = computed(() => quiz.currentQuiz?.questions ?? [])
const activeQuestion = computed(() => currentQ.value[currentQuestionIndex.value])

function isSelected(questionId: string, optionId: string) {
  return (quiz.answers.get(questionId) ?? []).includes(optionId)
}

function toggleOption(questionId: string, optionId: string, type: App.QuizQuestion['type']) {
  const current = quiz.answers.get(questionId) ?? []
  if (type === 'single' || type === 'true_false') {
    quiz.setAnswer(questionId, [optionId])
  } else {
    if (current.includes(optionId)) {
      quiz.setAnswer(questionId, current.filter(id => id !== optionId))
    } else {
      quiz.setAnswer(questionId, [...current, optionId])
    }
  }
}

function nextQuestion() {
  if (currentQuestionIndex.value < currentQ.value.length - 1) {
    currentQuestionIndex.value++
  }
}

function prevQuestion() {
  if (currentQuestionIndex.value > 0) {
    currentQuestionIndex.value--
  }
}

const allAnswered = computed(() =>
  currentQ.value.every(q => (quiz.answers.get(q.id) ?? []).length > 0)
)

async function submit() {
  submitting.value = true
  errorMsg.value = ''
  try {
    await quiz.submitQuiz()
    step.value = 'result'
    if (quiz.result?.passed) {
      setTimeout(() => progress.loadBadges(), 2000)
    }
  } catch {
    errorMsg.value = 'Submission failed. Please try again.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AppShell max-width="max-w-3xl">

    <div v-if="step === 'loading'" class="space-y-4 pt-10 mt-16">
      <div class="h-8 w-1/3 rounded-xl bg-(--sa-gray) animate-pulse mb-6" />
      <div class="h-48 rounded-2xl bg-(--sa-gray) animate-pulse" />
    </div>

    <!-- ERROR STATE (Instead of redirecting automatically) -->
    <div v-else-if="step === 'error'" class="text-center py-12 space-y-4 bg-white rounded-3xl border border-(--sa-gray) mt-24 shadow-sm">
      <div class="text-6xl mb-2">📚</div>
      <h1 class="font-display text-3xl font-bold text-(--sa-dark)">Quiz Not Found</h1>
      <p class="text-(--sa-taupe) font-medium text-lg">{{ errorMsg }}</p>
      
      <div class="mt-8 pt-4">
        <RouterLink to="/study">
          <SaButton variant="secondary" class="shadow-sm">Return to Study</SaButton>
        </RouterLink>
      </div>
    </div>

    <template v-else-if="step === 'quiz' && quiz.currentQuiz && activeQuestion">
      <div class="mb-6 pt-6 mt-16">
        <RouterLink :to="`/study`" class="text-sm text-(--sa-taupe) hover:text-(--sa-dark) flex items-center gap-1 font-medium transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
          {{ $t('quiz.back_chap') }}
        </RouterLink>
        <div class="flex items-end justify-between mt-4 border-b border-(--sa-gray) pb-4">
          <div>
            <h1 class="font-display text-2xl font-bold text-(--sa-dark)">{{ quiz.currentQuiz.title }}</h1>
            <p class="text-sm text-(--sa-taupe) mt-1 font-medium">{{ $t('quiz.pass_req') }}: {{ quiz.currentQuiz.passing_score_pct }}%</p>
          </div>
          <div class="text-right">
            <span class="text-xs font-bold uppercase tracking-wider text-(--sa-taupe)">{{ $t('quiz.progress') }}</span>
            <div class="font-mono text-lg font-bold text-(--sa-dark)">
              {{ currentQuestionIndex + 1 }} <span class="text-(--sa-taupe)">/ {{ currentQ.length }}</span>
            </div>
          </div>
        </div>
        
        <div class="w-full bg-(--sa-gray) h-1.5 mt-4 rounded-full overflow-hidden">
          <div class="bg-(--sa-dark) h-full transition-all duration-300" :style="{ width: `${((currentQuestionIndex + 1) / currentQ.length) * 100}%` }"></div>
        </div>
      </div>

      <div class="space-y-6 min-h-75">
        <SaCard padding="p-6 sm:p-8" class="shadow-md border-(--sa-gray) relative overflow-hidden">
          <div class="absolute top-0 left-0 w-1.5 h-full bg-(--sa-dark)"></div>
          
          <h2 class="text-xl font-medium text-(--sa-dark) mb-6 leading-relaxed">
            {{ activeQuestion.question_text }}
          </h2>
          
          <div class="space-y-3" role="group" :aria-label="activeQuestion.question_text">
            <button
              v-for="opt in activeQuestion.options"
              :key="opt.id"
              :aria-pressed="isSelected(activeQuestion.id, opt.id)"
              :class="[
                'w-full text-left px-5 py-4 rounded-xl border-2 text-base transition-all font-medium flex items-center gap-3',
                isSelected(activeQuestion.id, opt.id)
                  ? 'border-(--sa-dark) bg-brand-50 text-(--sa-dark) shadow-sm'
                  : 'border-(--sa-gray) bg-white text-(--sa-dark) hover:border-brand-200 hover:bg-[#F8FAFC]',
              ]"
              @click="toggleOption(activeQuestion.id, opt.id, activeQuestion.type)"
            >
              <div :class="[
                'w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition-colors',
                isSelected(activeQuestion.id, opt.id) ? 'border-(--sa-dark) bg-(--sa-dark)' : 'border-(--sa-taupe)'
              ]">
                <div v-if="isSelected(activeQuestion.id, opt.id)" class="w-2 h-2 bg-white rounded-full"></div>
              </div>
              {{ opt.option_text }}
            </button>
          </div>
        </SaCard>
      </div>

      <p v-if="errorMsg" class="mt-4 text-sm text-red-600 bg-red-50 p-3 rounded-lg border border-red-100 font-medium" role="alert">{{ errorMsg }}</p>

      <div class="mt-8 pt-6 border-t border-(--sa-gray) flex items-center justify-between">
        <button 
          @click="prevQuestion"
          :disabled="currentQuestionIndex === 0"
          class="px-5 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center gap-2"
          :class="currentQuestionIndex === 0 ? 'opacity-30 cursor-not-allowed text-(--sa-taupe)' : 'text-(--sa-dark) hover:bg-(--sa-gray)'"
        >
          {{ $t('quiz.prev') }}
        </button>

        <button 
          v-if="currentQuestionIndex < currentQ.length - 1"
          @click="nextQuestion"
          class="px-6 py-2.5 rounded-xl font-bold text-sm bg-(--sa-dark) text-white hover:opacity-90 transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5"
        >
          {{ $t('quiz.next') }}
        </button>
        
        <button
          v-else
          :disabled="!allAnswered"
          class="px-6 py-2.5 rounded-xl font-bold text-sm transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5 flex items-center gap-2"
          :class="allAnswered ? 'bg-emerald-500 text-white hover:bg-emerald-600' : 'bg-(--sa-gray) text-(--sa-taupe) cursor-not-allowed'"
          @click="submit"
        >
          <span v-if="submitting" class="animate-spin mr-1">竊ｻ</span>
          {{ submitting ? $t('quiz.submitting') : $t('quiz.submit') }}
        </button>
      </div>
      
      <p v-if="currentQuestionIndex === currentQ.length - 1 && !allAnswered" class="text-right text-xs text-rose-500 font-medium mt-2">
        {{ $t('quiz.answer_all') }}
      </p>
    </template>

    <template v-else-if="step === 'result' && quiz.result">
      <div class="text-center py-12 space-y-4 bg-white rounded-3xl border border-(--sa-gray) mt-24 shadow-sm">
        <div class="text-6xl mb-2">{{ quiz.result.passed ? '醇' : '当' }}</div>
        <h1 class="font-display text-4xl font-bold text-(--sa-dark)">
          {{ quiz.result.score_pct }}%
        </h1>
        <p class="text-(--sa-taupe) font-medium text-lg">
          You got {{ quiz.result.correct_count }} out of {{ quiz.result.total_questions }} correct
        </p>
        
        <div class="inline-block mt-2 px-4 py-2 rounded-full font-bold text-sm uppercase tracking-wider"
             :class="quiz.result.passed ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
          {{ quiz.result.passed ? $t('quiz.chap_comp') : $t('quiz.keep_studying') }}
        </div>

        <div v-if="quiz.badgeEvaluationTriggered" class="mt-4 inline-flex items-center gap-2 text-sm text-amber-600 bg-amber-50 px-4 py-2 rounded-lg font-bold border border-amber-200">
          <span class="animate-bounce">遵</span> {{ $t('quiz.checking_badges') }}
        </div>
      </div>

      <div class="mt-10 space-y-5">
        <h2 class="font-display text-xl font-bold text-(--sa-dark) flex items-center gap-2">
          <svg class="w-5 h-5 text-(--sa-taupe)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
          {{ $t('quiz.review') }}
        </h2>
        <div
          v-for="(pq, index) in quiz.result.per_question"
          :key="pq.question_id"
          class="rounded-2xl border-2 p-5 text-sm transition-all"
          :class="pq.is_correct ? 'border-emerald-100 bg-emerald-50/30' : 'border-rose-100 bg-rose-50/30'"
        >
          <div class="flex items-start justify-between gap-4">
            <p class="font-bold text-(--sa-dark) text-base mb-2">
              <span class="text-(--sa-taupe) mr-1">{{ index + 1 }}.</span> {{ pq.question_text }}
            </p>
            <span :class="['shrink-0 text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-md', pq.is_correct ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700']">
              {{ pq.is_correct ? $t('quiz.correct') : $t('quiz.incorrect') }}
            </span>
          </div>
          
          <div v-if="pq.explanation" class="mt-3 p-3 bg-white/60 rounded-xl border border-white">
            <p class="text-sm text-(--sa-dark) font-medium"><span class="text-(--sa-taupe) font-bold">{{ $t('quiz.explanation') }}:</span> {{ pq.explanation }}</p>
          </div>
        </div>
      </div>

      <div class="mt-10 flex gap-4 justify-center pb-10">
        <RouterLink to="/study">
          <SaButton variant="secondary" class="shadow-sm">{{ $t('quiz.return_book') }}</SaButton>
        </RouterLink>
        <SaButton @click="step = 'quiz'; currentQuestionIndex = 0; quiz.reset()" class="shadow-md hover:shadow-lg hover:-translate-y-0.5">{{ $t('quiz.retake') }}</SaButton>
      </div>
    </template>

  </AppShell>
</template>