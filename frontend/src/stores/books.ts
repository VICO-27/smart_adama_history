import { defineStore } from 'pinia'
import { ref } from 'vue'
import { booksApi } from '@/api/books'
import apiClient from '@/api/client'

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
      const payload = (await booksApi.list()) as any
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
      if (booksApi.markChapterRead) {
        await booksApi.markChapterRead(chapterId)
      } else {
        await apiClient.post(`/chapters/${chapterId}/read`)
      }
      console.log(`✅ Chapter ${chapterId} marked as read on backend!`)
    } catch (error) {
      console.error('Failed to mark chapter read on backend:', error)
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