import apiClient from './client'

export const booksApi = {
  list: () => apiClient.get('/books'),
  getChapter: (id: string | number) => apiClient.get(`/chapters/${id}`),
  getChapterQuiz: (id: string | number) => apiClient.get(`/chapters/${id}/quiz`),
  markChapterRead: (id: string | number) => apiClient.post(`/chapters/${id}/read`),

  // Admin Book Ingestion API
  getBookIngestionStatus: () => apiClient.get('/admin/book-ingestion'),
  updateChapterContent: (chapterId: string | number, data: { content: string; publish?: boolean }) => 
    apiClient.put(`/admin/chapters/${chapterId}`, data),
  validateChapter: (chapterId: string | number, content: string) => 
    apiClient.post(`/admin/chapters/${chapterId}/validate`, { content }),
  previewChapter: (chapterId: string | number, content: string) => 
    apiClient.post(`/admin/chapters/${chapterId}/preview`, { content }),
  previewStructured: (chapterId: string | number) => 
    apiClient.post(`/admin/chapters/${chapterId}/preview-structured`),
  ingestChapter: (chapterId: string | number) => 
    apiClient.post(`/admin/chapters/${chapterId}/ingest`),
  ingestStructured: (chapterId: string | number) => 
    apiClient.post(`/admin/chapters/${chapterId}/ingest-structured`),
  retryFailed: (chapterId: string | number) => 
    apiClient.post(`/admin/chapters/${chapterId}/retry`),
  getChapterStatus: (chapterId: string | number) => 
    apiClient.get(`/admin/chapters/${chapterId}/status`),
  verifyBook: (bookId: string | number) => 
    apiClient.post(`/admin/books/${bookId}/verify`),

  // Admin Section API
  createSection: (chapterId: string | number, data: { section_number: string; title: string; raw_text: string }) => 
    apiClient.post(`/admin/chapters/${chapterId}/sections`, data),
  updateSection: (sectionId: string | number, data: { section_number?: string; title?: string; order?: number; raw_text?: string }) => 
    apiClient.patch(`/admin/sections/${sectionId}`, data),
  deleteSection: (sectionId: string | number) => 
    apiClient.delete(`/admin/sections/${sectionId}`),
  reorderSection: (sectionId: string | number, newOrder: number) => 
    apiClient.patch(`/admin/sections/${sectionId}/reorder`, { new_order: newOrder }),
  getSections: (chapterId: string | number) => 
    apiClient.get(`/admin/chapters/${chapterId}/sections`),
}