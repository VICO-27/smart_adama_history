import apiClient from './client'

export const quizApi = {
  startAttempt: (quizId: string) =>
    apiClient.post<{ attempt_id: string; quiz: App.Quiz }>(`/quizzes/${quizId}/attempts`),

  submitAttempt: (quizId: string, attemptId: string, answers: App.QuizAnswer[]) =>
    apiClient.post<{ attempt: App.GradedAttempt; badge_evaluation_triggered: boolean }>(
      `/quizzes/${quizId}/attempts/${attemptId}/submit`,
      { answers },
    ),

  listMyAttempts: (page = 1) =>
    apiClient.get<{ attempts: App.QuizAttemptSummary[]; meta: App.PaginationMeta }>(
      '/users/me/quiz-attempts',
      { params: { page } },
    ),
}
