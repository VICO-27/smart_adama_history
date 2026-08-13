<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { booksApi } from '@/api/books'
import AppShell from '@/components/layout/AppShell.vue'
import SaCard from '@/components/ui/SaCard.vue'
import SaButton from '@/components/ui/SaButton.vue'
import { useRouter } from 'vue-router'

// Canonical chapters (hardcoded - must match backend)
const CANONICAL_CHAPTERS = {
  1: 'Introduction',
  2: 'Smart Governance',
  3: 'Digital Adama',
  4: 'Smart Security',
  5: 'Smart Urban Design and Land Use Management',
  6: 'Smart Environment and Organic Production',
  7: 'Smart Mobility',
  8: 'Smart Social Services',
  9: 'Smart Tourism and Culture',
  10: 'Smart Public Relation, Research and Knowledge Management',
  11: 'Smart People',
}

// State
const loading = ref(true)
const bookId = ref<string | null>(null)
const chapters = ref<any[]>([])
const verification = ref<any>(null)
const selectedChapter = ref<number | null>(null)
const chapterContent = ref('')
const chapterStatus = ref<any>(null)
const validationResult = ref<any>(null)
const previewResult = ref<any>(null)
const errorMessage = ref('')
const successMessage = ref('')
const isProcessing = ref(false)

// UI State
const activeTab = ref<'edit' | 'preview' | 'status'>('edit')

// Router
const router = useRouter()

// Selected chapter ID for editor navigation
const selectedChapterId = ref<string | null>(null)

// Computed
const selectedChapterTitle = computed(() => {
  if (!selectedChapter.value) return ''
  return CANONICAL_CHAPTERS[selectedChapter.value as keyof typeof CANONICAL_CHAPTERS] || ''
})

const contentStats = computed(() => {
  const text = chapterContent.value || ''
  const chars = text.length
  const words = text.trim().split(/\s+/).filter(Boolean).length
  const lines = text.split('\n').length
  return { chars, words, lines }
})

// Load book ingestion status
const loadStatus = async () => {
  try {
    loading.value = true
    const response = await booksApi.getBookIngestionStatus()
    bookId.value = response.data.book?.id || null
    verification.value = response.data.verification || null
    chapters.value = response.data.chapters || []
  } catch (err: any) {
    errorMessage.value = err.response?.data?.error?.message || 'Failed to load book status'
  } finally {
    loading.value = false
  }
}

// Select a chapter to edit
const selectChapter = (chapterId: string) => {
  // Navigate to the chapter editor
  router.push(`/admin/chapters/${chapterId}`)
}

// Save draft
const saveDraft = async () => {
  if (!selectedChapter.value) return
  
  const chapter = chapters.value.find(c => c.number === selectedChapter.value)
  if (!chapter?.id) {
    errorMessage.value = 'Chapter not found'
    return
  }
  
  errorMessage.value = ''
  successMessage.value = ''
  isProcessing.value = true
  
  try {
    await booksApi.updateChapterContent(chapter.id, { content: chapterContent.value })
    successMessage.value = 'Draft saved successfully'
    await loadStatus() // Reload to get updated status
  } catch (err: any) {
    errorMessage.value = err.response?.data?.error?.message || 'Failed to save draft'
  } finally {
    isProcessing.value = false
  }
}

// Validate chapter content
const validateContent = async () => {
  if (!selectedChapter.value || !chapterContent.value.trim()) {
    errorMessage.value = 'Please enter chapter content first'
    return
  }
  
  const chapter = chapters.value.find(c => c.number === selectedChapter.value)
  if (!chapter?.id) {
    errorMessage.value = 'Chapter not found'
    return
  }
  
  errorMessage.value = ''
  validationResult.value = null
  isProcessing.value = true
  
  try {
    const response = await booksApi.validateChapter(chapter.id, chapterContent.value)
    validationResult.value = response.data
    
    if (response.data.valid) {
      successMessage.value = 'Content validation passed!'
    } else {
      errorMessage.value = 'Content validation failed. See errors below.'
    }
  } catch (err: any) {
    errorMessage.value = err.response?.data?.error?.message || 'Validation request failed'
  } finally {
    isProcessing.value = false
  }
}

// Preview ingestion
const previewIngestion = async () => {
  if (!selectedChapter.value || !chapterContent.value.trim()) {
    errorMessage.value = 'Please enter chapter content first'
    return
  }
  
  const chapter = chapters.value.find(c => c.number === selectedChapter.value)
  if (!chapter?.id) {
    errorMessage.value = 'Chapter not found'
    return
  }
  
  errorMessage.value = ''
  previewResult.value = null
  isProcessing.value = true
  activeTab.value = 'preview'
  
  try {
    const response = await booksApi.previewChapter(chapter.id, chapterContent.value)
    previewResult.value = response.data.preview
  } catch (err: any) {
    errorMessage.value = err.response?.data?.error?.message || 'Preview request failed'
  } finally {
    isProcessing.value = false
  }
}

// Ingest chapter
const ingestChapter = async () => {
  if (!selectedChapter.value) return
  
  const chapter = chapters.value.find(c => c.number === selectedChapter.value)
  if (!chapter?.id) {
    errorMessage.value = 'Chapter not found'
    return
  }
  
  if (!confirm(`Ingest Chapter ${selectedChapter.value}: ${selectedChapterTitle.value}?\n\nThis will start the embedding process which may take several minutes.`)) {
    return
  }
  
  errorMessage.value = ''
  successMessage.value = ''
  isProcessing.value = true
  
  try {
    // First save content
    await booksApi.updateChapterContent(chapter.id, { content: chapterContent.value })
    // Then trigger ingestion
    await booksApi.ingestChapter(chapter.id)
    successMessage.value = 'Chapter queued for ingestion. This may take several minutes.'
    activeTab.value = 'status'
    await loadStatus() // Reload to get updated status
  } catch (err: any) {
    errorMessage.value = err.response?.data?.error?.message || 'Failed to start ingestion'
  } finally {
    isProcessing.value = false
  }
}

// Verify entire book
const verifyBook = async () => {
  if (!bookId.value) {
    errorMessage.value = 'No book found'
    return
  }
  
  isProcessing.value = true
  try {
    const response = await booksApi.verifyBook(bookId.value)
    verification.value = response.data.verification
    successMessage.value = 'Book verification complete'
  } catch (err: any) {
    errorMessage.value = err.response?.data?.error?.message || 'Verification failed'
  } finally {
    isProcessing.value = false
  }
}

onMounted(() => {
  loadStatus()
})
</script>

<template>
  <AppShell>
    <div class="max-w-7xl mx-auto space-y-6">
      
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="font-display text-2xl font-semibold text-[var(--sa-dark)]">Book Ingestion</h1>
          <p class="text-sm text-[var(--sa-taupe)] mt-1">Manually enter and ingest Smart Adama Book chapters (1-11)</p>
        </div>
        <SaButton 
          @click="verifyBook" 
          :disabled="!bookId || isProcessing"
          variant="primary" 
          class="bg-indigo-600 text-white px-4 py-2 rounded-full text-sm font-medium"
        >
          Verify Book
        </SaButton>
      </div>

      <!-- Global Status -->
      <SaCard v-if="verification" padding="p-6" class="border-2 border-[var(--sa-gray)]">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-[var(--sa-dark)]">Book Status</h2>
          <span 
            :class="[
              'text-xs px-3 py-1 rounded-full font-semibold uppercase',
              verification.is_complete ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'
            ]"
          >
            {{ verification.status }}
          </span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
          <div>
            <p class="text-[var(--sa-taupe)]">Chapters</p>
            <p class="text-lg font-semibold text-[var(--sa-dark)]">{{ verification.populated_chapters }} / {{ verification.canonical_chapters }}</p>
          </div>
          <div>
            <p class="text-[var(--sa-taupe)]">Total Chunks</p>
            <p class="text-lg font-semibold text-[var(--sa-dark)]">{{ verification.total_chunks }}</p>
          </div>
          <div>
            <p class="text-[var(--sa-taupe)]">Ready</p>
            <p class="text-lg font-semibold text-green-600">{{ verification.ready_chunks }}</p>
          </div>
          <div>
            <p class="text-[var(--sa-taupe)]">Failed</p>
            <p class="text-lg font-semibold text-red-600">{{ verification.failed_chunks }}</p>
          </div>
        </div>
      </SaCard>

      <!-- Alerts -->
      <div v-if="errorMessage" class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700">
        {{ errorMessage }}
      </div>
      <div v-if="successMessage" class="bg-green-50 border border-green-200 rounded-lg p-4 text-sm text-green-700">
        {{ successMessage }}
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Chapter List -->
        <div class="lg:col-span-1">
          <SaCard padding="p-4" class="sticky top-4">
            <h2 class="text-lg font-semibold text-[var(--sa-dark)] mb-3">Chapters</h2>
            <div class="space-y-2 max-h-[600px] overflow-y-auto">
              <button
                v-for="chapter in chapters"
                :key="chapter.id"
                @click="selectChapter(chapter.id)"
                :class="[
                  'w-full text-left px-3 py-2 rounded-lg text-sm transition-colors cursor-pointer',
                  selectedChapter === chapter.number
                    ? 'bg-indigo-50 border-2 border-indigo-500 text-indigo-700'
                    : 'bg-gray-50 border border-[var(--sa-gray)] text-[var(--sa-dark)] hover:bg-gray-100'
                ]"
              >
                <div class="font-medium">{{ chapter.number }}. {{ chapter.title }}</div>
                <div class="text-xs text-[var(--sa-taupe)] mt-1">
                  <span :class="chapter.has_content ? 'text-green-600' : 'text-gray-400'">
                    {{ chapter.has_content ? '● Has content' : '○ No content' }}
                  </span>
                  · {{ chapter.chunk_count }} chunks · {{ chapter.status }}
                </div>
              </button>
            </div>
          </SaCard>
        </div>

        <!-- Editor Panel -->
        <div class="lg:col-span-2">
          <SaCard v-if="!selectedChapter" padding="p-12" class="text-center">
            <svg class="mx-auto h-16 w-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <p class="text-[var(--sa-taupe)]">Select a chapter from the left to begin</p>
          </SaCard>

          <SaCard v-else padding="p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-[var(--sa-dark)]">
                Chapter {{ selectedChapter }}: {{ selectedChapterTitle }}
              </h2>
              <div class="flex gap-2">
                <button
                  @click="activeTab = 'edit'"
                  :class="[
                    'px-3 py-1 text-sm rounded-lg transition-colors',
                    activeTab === 'edit' ? 'bg-indigo-100 text-indigo-700 font-medium' : 'text-[var(--sa-taupe)] hover:bg-gray-100'
                  ]"
                >
                  Edit
                </button>
                <button
                  @click="activeTab = 'preview'"
                  :class="[
                    'px-3 py-1 text-sm rounded-lg transition-colors',
                    activeTab === 'preview' ? 'bg-indigo-100 text-indigo-700 font-medium' : 'text-[var(--sa-taupe)] hover:bg-gray-100'
                  ]"
                >
                  Preview
                </button>
                <button
                  @click="activeTab = 'status'"
                  :class="[
                    'px-3 py-1 text-sm rounded-lg transition-colors',
                    activeTab === 'status' ? 'bg-indigo-100 text-indigo-700 font-medium' : 'text-[var(--sa-taupe)] hover:bg-gray-100'
                  ]"
                >
                  Status
                </button>
              </div>
            </div>

            <!-- Edit Tab -->
            <div v-if="activeTab === 'edit'" class="space-y-4">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <label class="block text-sm font-medium text-[var(--sa-dark)]">Chapter Content</label>
                  <div class="text-xs text-[var(--sa-taupe)]">
                    {{ contentStats.chars }} chars · {{ contentStats.words }} words · {{ contentStats.lines }} lines
                  </div>
                </div>
                <textarea
                  v-model="chapterContent"
                  rows="20"
                  placeholder="Paste chapter content here..."
                  class="w-full border border-[var(--sa-gray)] rounded-lg px-4 py-3 focus:outline-none focus:border-indigo-500 text-sm font-mono"
                  :disabled="isProcessing"
                ></textarea>
              </div>

              <!-- Validation Result -->
              <div v-if="validationResult" class="mt-4 p-4 rounded-lg" :class="validationResult.valid ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
                <div class="font-semibold text-sm mb-2" :class="validationResult.valid ? 'text-green-700' : 'text-red-700'">
                  {{ validationResult.valid ? '✓ Validation Passed' : '✗ Validation Failed' }}
                </div>
                <ul v-if="!validationResult.valid && validationResult.errors?.length" class="text-sm text-red-600 space-y-1 ml-4">
                  <li v-for="(error, idx) in validationResult.errors" :key="idx">• {{ error }}</li>
                </ul>
              </div>

              <!-- Action Buttons -->
              <div class="flex gap-3 pt-4">
                <SaButton
                  @click="saveDraft"
                  :disabled="!chapterContent.trim() || isProcessing"
                  class="bg-gray-100 text-[var(--sa-dark)] px-4 py-2 rounded-lg text-sm font-medium"
                >
                  Save Draft
                </SaButton>
                <SaButton
                  @click="validateContent"
                  :disabled="!chapterContent.trim() || isProcessing"
                  class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium"
                >
                  Validate
                </SaButton>
                <SaButton
                  @click="previewIngestion"
                  :disabled="!chapterContent.trim() || isProcessing"
                  class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium"
                >
                  Preview
                </SaButton>
                <SaButton
                  @click="ingestChapter"
                  :disabled="!chapterContent.trim() || isProcessing"
                  class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium"
                >
                  {{ isProcessing ? 'Processing...' : 'Ingest' }}
                </SaButton>
              </div>
            </div>

            <!-- Preview Tab -->
            <div v-else-if="activeTab === 'preview'" class="space-y-4">
              <div v-if="!previewResult" class="text-center py-12 text-[var(--sa-taupe)]">
                <p>Click "Preview" in the Edit tab to see ingestion preview</p>
              </div>
              <div v-else>
                <div class="grid grid-cols-3 gap-4 mb-6">
                  <div class="bg-blue-50 rounded-lg p-4">
                    <p class="text-sm text-blue-600 font-medium">Sections</p>
                    <p class="text-2xl font-bold text-blue-700">{{ previewResult.sections?.length || 0 }}</p>
                  </div>
                  <div class="bg-purple-50 rounded-lg p-4">
                    <p class="text-sm text-purple-600 font-medium">Est. Chunks</p>
                    <p class="text-2xl font-bold text-purple-700">{{ previewResult.estimated_chunks || 0 }}</p>
                  </div>
                  <div class="bg-amber-50 rounded-lg p-4">
                    <p class="text-sm text-amber-600 font-medium">Est. Batches</p>
                    <p class="text-2xl font-bold text-amber-700">{{ previewResult.estimated_batches || 0 }}</p>
                  </div>
                </div>

                <div class="space-y-3">
                  <h3 class="font-semibold text-[var(--sa-dark)]">Detected Sections:</h3>
                  <div
                    v-for="(section, idx) in previewResult.sections"
                    :key="idx"
                    class="border border-[var(--sa-gray)] rounded-lg p-3"
                  >
                    <p class="font-medium text-sm text-[var(--sa-dark)]">{{ section.title }}</p>
                    <p class="text-xs text-[var(--sa-taupe)] mt-1">{{ section.raw_text?.length || 0 }} characters</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Status Tab -->
            <div v-else-if="activeTab === 'status'" class="space-y-4">
              <div v-if="!chapterStatus" class="text-center py-12 text-[var(--sa-taupe)]">
                <p>No ingestion status available yet</p>
              </div>
              <div v-else>
                <div class="grid grid-cols-2 gap-4">
                  <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-[var(--sa-taupe)]">Status</p>
                    <p class="text-lg font-semibold text-[var(--sa-dark)] capitalize">{{ chapterStatus.ingestion_status }}</p>
                  </div>
                  <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-[var(--sa-taupe)]">Sections</p>
                    <p class="text-lg font-semibold text-[var(--sa-dark)]">{{ chapterStatus.section_count }}</p>
                  </div>
                  <div class="bg-green-50 rounded-lg p-4">
                    <p class="text-sm text-green-600">Ready Chunks</p>
                    <p class="text-lg font-semibold text-green-700">{{ chapterStatus.ready_chunks }}</p>
                  </div>
                  <div class="bg-amber-50 rounded-lg p-4">
                    <p class="text-sm text-amber-600">Pending Chunks</p>
                    <p class="text-lg font-semibold text-amber-700">{{ chapterStatus.pending_chunks }}</p>
                  </div>
                  <div class="bg-red-50 rounded-lg p-4">
                    <p class="text-sm text-red-600">Failed Chunks</p>
                    <p class="text-lg font-semibold text-red-700">{{ chapterStatus.failed_chunks }}</p>
                  </div>
                  <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-[var(--sa-taupe)]">Total Chunks</p>
                    <p class="text-lg font-semibold text-[var(--sa-dark)]">{{ chapterStatus.chunk_count }}</p>
                  </div>
                </div>
              </div>
            </div>
          </SaCard>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<style scoped>
textarea {
  font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Fira Code', 'Consolas', monospace;
}
</style>
