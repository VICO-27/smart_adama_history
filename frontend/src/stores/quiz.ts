import { defineStore } from 'pinia'
import { ref } from 'vue'
import { quizApi } from '@/api/quiz'

export const useQuizStore = defineStore('quiz', () => {
  const currentQuiz = ref<App.Quiz | null>(null)
  const currentAttemptId = ref<string | null>(null)
  const answers = ref<Map<string, string[]>>(new Map())
  const result = ref<App.GradedAttempt | null>(null)
  const badgeEvaluationTriggered = ref(false)
  const loading = ref(false)

  async function startQuiz(quizId: string) {
    loading.value = true
    try {
      const { data } = await quizApi.startAttempt(quizId)
      currentQuiz.value = data.quiz
      currentAttemptId.value = data.attempt_id
      answers.value = new Map()
      result.value = null
      badgeEvaluationTriggered.value = false
    } finally {
      loading.value = false
    }
  }

  function setAnswer(questionId: string, selectedOptionIds: string[]) {
    answers.value.set(questionId, selectedOptionIds)
  }

  async function submitQuiz() {
    if (!currentQuiz.value || !currentAttemptId.value) return

    loading.value = true
    try {
      const payload: App.QuizAnswer[] = Array.from(answers.value.entries()).map(
        ([question_id, selected_option_ids]) => ({ question_id, selected_option_ids }),
      )

      const { data } = await quizApi.submitAttempt(
        currentQuiz.value.id,
        currentAttemptId.value,
        payload,
      )

      result.value = data.attempt
      badgeEvaluationTriggered.value = data.badge_evaluation_triggered
      return data
    } finally {
      loading.value = false
    }
  }

  function reset() {
    currentQuiz.value = null
    currentAttemptId.value = null
    answers.value = new Map()
    result.value = null
    badgeEvaluationTriggered.value = false
  }

  return {
    currentQuiz,
    currentAttemptId,
    answers,
    result,
    badgeEvaluationTriggered,
    loading,
    startQuiz,
    setAnswer,
    submitQuiz,
    reset,
  }
})
