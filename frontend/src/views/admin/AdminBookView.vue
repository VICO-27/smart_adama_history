<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useBooksStore } from '@/stores/books'
import { booksApi } from '@/api/books'
import AppShell from '@/components/layout/AppShell.vue'
import SaCard   from '@/components/ui/SaCard.vue'
import SaButton from '@/components/ui/SaButton.vue'

const books = useBooksStore()

// Upload State
const showUploadForm = ref(false)
const isUploading = ref(false)
const uploadError = ref('')
const title = ref('')
const fileInput = ref<HTMLInputElement | null>(null)

onMounted(() => books.loadBooks())

const handleUpload = async () => {
  if (!title.value || !fileInput.value?.files?.length) {
    uploadError.value = 'Please provide both a title and select a PDF file.'
    return
  }

  isUploading.value = true
  uploadError.value = ''

  const formData = new FormData()
  formData.append('title', title.value)
  formData.append('file', fileInput.value.files[0])

  try {
    await booksApi.uploadBook(formData)
    
    // Reset the form on success
    title.value = ''
    if (fileInput.value) fileInput.value.value = ''
    showUploadForm.value = false
    
    // Refresh the list to show the new book
    await books.loadBooks()
  } catch (err: any) {
    uploadError.value = err.response?.data?.message || 'Failed to upload book. Check the backend logs.'
  } finally {
    isUploading.value = false
  }
}
</script>

<template>
  <AppShell>
    <div class="space-y-6 max-w-4xl mx-auto">
      
      <!-- Header Area -->
      <div class="flex items-center justify-between">
        <h1 class="font-display text-2xl font-semibold text-[var(--sa-dark)]">Books</h1>
        <SaButton @click="showUploadForm = !showUploadForm" variant="primary" class="bg-black text-white px-4 py-2 rounded-full text-sm font-medium">
          {{ showUploadForm ? 'Cancel' : 'Upload New Book' }}
        </SaButton>
      </div>

      <!-- Upload Form -->
      <transition name="fade">
        <SaCard v-if="showUploadForm" padding="p-6" class="border border-[var(--sa-gray)] shadow-sm bg-gray-50/50">
          <h2 class="text-lg font-semibold text-[var(--sa-dark)] mb-4">Upload Manuscript</h2>
          
          <form @submit.prevent="handleUpload" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-[var(--sa-dark)] mb-1">Book Title</label>
              <input 
                v-model="title" 
                type="text" 
                placeholder="e.g., Smart Adama Guide" 
                class="w-full border border-[var(--sa-gray)] rounded-lg px-4 py-2 focus:outline-none focus:border-[var(--sa-dark)] text-sm"
                :disabled="isUploading"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-[var(--sa-dark)] mb-1">PDF File</label>
              <input 
                ref="fileInput"
                type="file" 
                accept=".pdf"
                class="w-full text-sm text-[var(--sa-taupe)] file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[var(--sa-gray)] file:text-[var(--sa-dark)] hover:file:bg-gray-200"
                :disabled="isUploading"
              />
            </div>

            <div v-if="uploadError" class="text-red-500 text-sm font-medium">
              {{ uploadError }}
            </div>

            <div class="flex justify-end pt-2">
              <button 
                type="submit" 
                :disabled="isUploading"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-full text-sm font-medium transition-colors disabled:opacity-50 flex items-center gap-2"
              >
                <span v-if="isUploading" class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
                {{ isUploading ? 'Uploading & Processing...' : 'Submit Book' }}
              </button>
            </div>
          </form>
        </SaCard>
      </transition>

      <!-- Loading State -->
      <div v-if="books.loading" class="space-y-3">
        <div v-for="i in 3" :key="i" class="h-16 rounded-2xl bg-[var(--sa-gray)] animate-pulse" />
      </div>

      <!-- Empty State -->
      <div v-else-if="!books.books.length" class="text-center py-20 text-[var(--sa-taupe)] bg-white rounded-2xl border border-[var(--sa-gray)] border-dashed">
        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        No books yet. Click "Upload New Book" to add your manuscript.
      </div>

      <!-- Book List -->
      <div v-else class="space-y-3">
        <SaCard
          v-for="book in books.books"
          :key="book.id"
          padding="p-5"
          class="hover:shadow-md transition-shadow"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium text-[var(--sa-dark)] text-lg">{{ book.title }}</p>
              <p class="text-sm text-[var(--sa-taupe)] mt-1 capitalize">
                {{ book.status || 'Processing' }} · {{ (book.chapters?.data || book.chapters || []).length }} chapters
              </p>
            </div>
            <span
              :class="[
                'text-xs px-3 py-1 rounded-full font-semibold uppercase tracking-wider',
                book.status === 'published' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700',
              ]"
            >{{ book.status || 'Draft' }}</span>
          </div>
        </SaCard>
      </div>

    </div>
  </AppShell>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>