import { defineStore } from 'pinia'
import { ref } from 'vue'
import { booksApi } from '@/api/books'

export const useBooksStore = defineStore('books', () => {
  const books = ref<any[]>([])
  const currentChapter = ref<any>(null)
  const currentQuiz = ref<any>(null)
  const bestAttempt = ref<any>(null)
  const chapterProgress = ref<any>(null)
  const loading = ref(false)

  async function loadBooks() {
    loading.value = true
    try {
      // Cast as 'any' to bypass TS strict typing on the unwrapped Axios response
      const payload = (await booksApi.list()) as any
      console.log('Books API Payload:', payload) // Let's log it to be 100% sure!

      // Because the client unwraps it, the array is directly inside payload.books
      books.value = payload.books || payload.data?.books || payload.data || payload || []
    } catch (error) {
      console.error('Failed to load books:', error)
      books.value = []
    } finally {
      loading.value = false
    }
  }

  async function loadChapter(chapterId: string) {
    loading.value = true
    try {
      const payload = (await booksApi.getChapter(chapterId)) as any
      currentChapter.value = payload.chapter || payload.data?.chapter || payload
      chapterProgress.value = payload.progress || payload.data?.progress || null
    } catch (error) {
      console.error('Failed to load chapter:', error)
    } finally {
      loading.value = false
    }
  }

  async function markChapterRead(chapterId: string) {
    try {
      await booksApi.markChapterRead(chapterId)
    } catch (error) {
      console.error('Failed to mark chapter read:', error)
    }
  }

  async function loadChapterQuiz(chapterId: string) {
    try {
      const payload = (await booksApi.getChapterQuiz(chapterId)) as any
      currentQuiz.value = payload.quiz || payload.data?.quiz || null
      bestAttempt.value = payload.best_attempt || payload.data?.best_attempt || null
    } catch (error) {
      currentQuiz.value = null
      bestAttempt.value = null
    }
  }

  return {
    books,
    currentChapter,
    currentQuiz,
    bestAttempt,
    chapterProgress,
    loading,
    loadBooks,
    loadChapter,
    markChapterRead,
    loadChapterQuiz
  }
})