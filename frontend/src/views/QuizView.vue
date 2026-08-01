<script setup lang="ts">
import { onMounted, computed, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { useBooksStore }  from '@/stores/books'
import { useQuizStore }   from '@/stores/quiz'
import { useProgressStore } from '@/stores/progress'
import AppShell from '@/components/layout/AppShell.vue'
import SaButton from '@/components/ui/SaButton.vue'
import SaCard   from '@/components/ui/SaCard.vue'

const route    = useRoute()
const router   = useRouter()
const books    = useBooksStore()
const quiz     = useQuizStore()
const progress = useProgressStore()

const chapterId = computed(() => route.params.chapterId as string)
const step      = ref<'loading' | 'quiz' | 'result'>('loading')
const submitting = ref(false)
const errorMsg   = ref('')

onMounted(async () => {
  const { quiz: q } = await books.loadChapterQuiz(chapterId.value)
  if (!q) { router.replace(`/chapters/${chapterId.value}`); return }
  await quiz.startQuiz(q.id)
  step.value = 'quiz'
})

const currentQ = computed(() => quiz.currentQuiz?.questions ?? [])

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
      // Refresh badges in case new ones were awarded
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
  <AppShell max-width="max-w-2xl">

    <!-- Loading -->
    <div v-if="step === 'loading'" class="space-y-4">
      <div v-for="i in 3" :key="i" class="h-24 rounded-2xl bg-[var(--sa-gray)] animate-pulse" />
    </div>

    <!-- Quiz in progress -->
    <template v-else-if="step === 'quiz' && quiz.currentQuiz">
      <div class="mb-6">
        <RouterLink :to="`/chapters/${chapterId}`" class="text-sm text-[var(--sa-taupe)] hover:text-[var(--sa-dark)]">← Chapter</RouterLink>
        <h1 class="font-display text-2xl font-semibold text-[var(--sa-dark)] mt-2">{{ quiz.currentQuiz.title }}</h1>
        <p class="text-sm text-[var(--sa-taupe)] mt-0.5">Pass at {{ quiz.currentQuiz.passing_score_pct }}%</p>
      </div>

      <div class="space-y-6">
        <SaCard
          v-for="(q, idx) in currentQ"
          :key="q.id"
          padding="p-5"
        >
          <p class="font-medium text-[var(--sa-dark)] mb-4">
            <span class="text-[var(--sa-taupe)] mr-2">{{ idx + 1 }}.</span>{{ q.question_text }}
          </p>
          <div class="space-y-2" role="group" :aria-label="q.question_text">
            <button
              v-for="opt in q.options"
              :key="opt.id"
              :aria-pressed="isSelected(q.id, opt.id)"
              :class="[
                'w-full text-left px-4 py-3 rounded-xl border text-sm transition-all',
                isSelected(q.id, opt.id)
                  ? 'border-[var(--sa-dark)] bg-[var(--sa-dark)] text-[var(--sa-bg)]'
                  : 'border-[var(--sa-gray)] bg-white text-[var(--sa-dark)] hover:border-[var(--sa-taupe)]',
              ]"
              @click="toggleOption(q.id, opt.id, q.type)"
            >{{ opt.option_text }}</button>
          </div>
        </SaCard>
      </div>

      <p v-if="errorMsg" class="mt-4 text-sm text-red-600" role="alert">{{ errorMsg }}</p>

      <div class="mt-8 flex justify-end">
        <SaButton
          :disabled="!allAnswered"
          :loading="submitting"
          @click="submit"
        >Submit Quiz</SaButton>
      </div>
    </template>

    <!-- Results -->
    <template v-else-if="step === 'result' && quiz.result">
      <div class="text-center py-12 space-y-4">
        <div class="text-5xl">{{ quiz.result.passed ? '🎉' : '📚' }}</div>
        <h1 class="font-display text-3xl font-semibold text-[var(--sa-dark)]">
          {{ quiz.result.score_pct }}%
        </h1>
        <p class="text-[var(--sa-taupe)]">
          {{ quiz.result.correct_count }} of {{ quiz.result.total_questions }} correct
        </p>
        <p :class="['text-sm font-medium', quiz.result.passed ? 'text-green-600' : 'text-red-600']">
          {{ quiz.result.passed ? '✓ Passed — chapter marked complete!' : '✗ Not passed. Keep studying!' }}
        </p>
        <div v-if="quiz.badgeEvaluationTriggered" class="text-sm text-[var(--sa-taupe)]">
          🏅 Checking for new badges…
        </div>
      </div>

      <!-- Per-question breakdown -->
      <div class="mt-8 space-y-4">
        <h2 class="font-display font-semibold text-[var(--sa-dark)]">Question breakdown</h2>
        <div
          v-for="pq in quiz.result.per_question"
          :key="pq.question_id"
          :class="['rounded-2xl border p-4 text-sm', pq.is_correct ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50']"
        >
          <p class="font-medium text-[var(--sa-dark)] mb-1">{{ pq.question_text }}</p>
          <p :class="['text-xs font-medium', pq.is_correct ? 'text-green-700' : 'text-red-700']">
            {{ pq.is_correct ? '✓ Correct' : '✗ Incorrect' }}
          </p>
          <p v-if="pq.explanation" class="mt-1 text-xs text-[var(--sa-taupe)]">{{ pq.explanation }}</p>
        </div>
      </div>

      <div class="mt-8 flex gap-3 justify-center">
        <RouterLink :to="`/chapters/${chapterId}`">
          <SaButton variant="secondary">Back to chapter</SaButton>
        </RouterLink>
        <SaButton @click="step = 'quiz'; quiz.reset()">Retake Quiz</SaButton>
      </div>
    </template>

  </AppShell>
</template>
