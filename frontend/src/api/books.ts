import apiClient from './client'

export const booksApi = {
  list: () =>
    apiClient.get<{ books: App.Book[] }>('/books'),

  getChapter: (chapterId: string) =>
    apiClient.get<{ chapter: App.Chapter; progress: App.ChapterProgress | null }>(`/chapters/${chapterId}`),

  markChapterRead: (chapterId: string) =>
    apiClient.post(`/chapters/${chapterId}/read`),

  getChapterQuiz: (chapterId: string) =>
    apiClient.get<{ quiz: App.Quiz | null; best_attempt: App.QuizAttemptSummary | null }>(`/chapters/${chapterId}/quiz`),

  // NEW: Admin endpoint to upload a manuscript
  uploadBook: (formData: FormData) =>
    apiClient.post<{ book: App.Book }>('/admin/books', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    }),
}