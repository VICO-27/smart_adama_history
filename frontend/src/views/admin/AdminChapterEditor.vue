<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { booksApi } from '@/api/books'
import AppShell from '@/components/layout/AppShell.vue'
import SaCard from '@/components/ui/SaCard.vue'
import SaButton from '@/components/ui/SaButton.vue'

const route = useRoute()
const router = useRouter()
const chapterId = route.params.id as string

const loading = ref(true)
const saving = ref(false)
const chapter = ref<any>(null)
const sections = ref<any[]>([])
const errorMessage = ref('')
const successMessage = ref('')
const isProcessing = ref(false)
const showSectionModal = ref(false)
const sectionForm = ref({ section_number: '', title: '', raw_text: '' })
const editingSection = ref<any>(null)

const loadChapter = async () => {
  try {
    loading.value = true
    errorMessage.value = ''
    const [chapterRes, sectionsRes] = await Promise.all([
      booksApi.getChapterStatus(chapterId),
      booksApi.getSections(chapterId),
    ])
    chapter.value = chapterRes.data.chapter
    sections.value = sectionsRes.data.sections || []
  } catch (err: any) {
    errorMessage.value = err.response?.data?.error?.message || 'Failed to load chapter'
  } finally {
    loading.value = false
  }
}

const openSectionModal = (section?: any) => {
  if (section) {
    editingSection.value = section
    sectionForm.value = { section_number: section.section_number || '', title: section.title, raw_text: section.raw_text || '' }
  } else {
    editingSection.value = null
    sectionForm.value = { section_number: '', title: '', raw_text: '' }
  }
  showSectionModal.value = true
}

const closeSectionModal = () => {
  showSectionModal.value = false
  editingSection.value = null
  sectionForm.value = { section_number: '', title: '', raw_text: '' }
}

const saveSection = async () => {
  try {
    saving.value = true
    errorMessage.value = ''
    if (editingSection.value) {
      await booksApi.updateSection(editingSection.value.id, {
        section_number: sectionForm.value.section_number,
        title: sectionForm.value.title,
        raw_text: sectionForm.value.raw_text,
      })
    } else {
      await booksApi.createSection(chapterId, {
        section_number: sectionForm.value.section_number,
        title: sectionForm.value.title,
        raw_text: sectionForm.value.raw_text,
      })
    }
    successMessage.value = 'Section saved successfully'
    closeSectionModal()
    await loadChapter()
  } catch (err: any) {
    errorMessage.value = err.response?.data?.error?.message || (editingSection.value ? 'Failed to update section' : 'Failed to create section')
  } finally {
    saving.value = false
  }
}

const deleteSection = async (sectionId: string) => {
  if (!confirm('Are you sure you want to delete this section?')) return
  try {
    saving.value = true
    errorMessage.value = ''
    await booksApi.deleteSection(sectionId)
    successMessage.value = 'Section deleted successfully'
    await loadChapter()
  } catch (err: any) {
    errorMessage.value = err.response?.data?.error?.message || 'Failed to delete section'
  } finally {
    saving.value = false
  }
}

const reorderSection = async (sectionId: string, newOrder: number) => {
  try {
    saving.value = true
    errorMessage.value = ''
    await booksApi.reorderSection(sectionId, newOrder)
    successMessage.value = 'Section reordered successfully'
    await loadChapter()
  } catch (err: any) {
    errorMessage.value = err.response?.data?.error?.message || 'Failed to reorder section'
  } finally {
    saving.value = false
  }
}

const validateChapter = async () => {
  try {
    isProcessing.value = true
    await booksApi.validateChapter(chapterId, '')
    successMessage.value = 'Chapter validated successfully'
  } catch (err: any) {
    errorMessage.value = err.response?.data?.error?.message || 'Validation failed'
  } finally {
    isProcessing.value = false
  }
}

const ingestChapter = async () => {
  if (!confirm('Are you sure you want to ingest this chapter? This will create chunks and queue embeddings.')) return
  try {
    isProcessing.value = true
    errorMessage.value = ''
    await booksApi.ingestStructured(chapterId)
    successMessage.value = 'Chapter queued for ingestion'
    await loadChapter()
  } catch (err: any) {
    errorMessage.value = err.response?.data?.error?.message || 'Ingestion failed'
  } finally {
    isProcessing.value = false
  }
}

const goBack = () => router.push('/admin/books')

onMounted(() => loadChapter())
</script>

<template>
  <AppShell>
    <div class="max-w-5xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <button @click="goBack" class="text-[var(--sa-taupe)] hover:text-[var(--sa-dark)] flex items-center gap-2 mb-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            Back to Books
          </button>
          <h1 class="font-display text-2xl font-semibold text-[var(--sa-dark)]">{{ chapter?.title || 'Loading...' }}</h1>
          <p class="text-sm text-[var(--sa-taupe)] mt-1">Chapter {{ chapter?.order }}</p>
        </div>
        <span :class="['px-3 py-1 rounded-full text-xs font-semibold capitalize', chapter?.ingestion_status === 'ready' ? 'bg-green-100 text-green-700' : 'bg-[var(--sa-gray)] text-[var(--sa-taupe)]']">{{ chapter?.ingestion_status }}</span>
      </div>

      <div v-if="successMessage" class="bg-green-50 text-green-800 px-4 py-3 rounded-xl text-sm font-medium">{{ successMessage }}</div>
      <div v-if="errorMessage" class="bg-red-50 text-red-800 px-4 py-3 rounded-xl text-sm font-medium">{{ errorMessage }}</div>

      <div class="flex items-center gap-3">
        <SaButton @click="validateChapter" :disabled="isProcessing" variant="secondary">Validate</SaButton>
        <SaButton @click="ingestChapter" :disabled="isProcessing || !sections.length" variant="primary">
          <svg v-if="isProcessing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
          Ingest Chapter
        </SaButton>
        <SaButton @click="openSectionModal()" variant="primary">+ Add Section</SaButton>
      </div>

      <div class="space-y-4">
        <h2 class="font-display font-semibold text-[var(--sa-dark)]">Sections ({{ sections.length }})</h2>
        <div v-if="loading" class="space-y-3">
          <div v-for="i in 3" :key="i" class="h-48 rounded-2xl bg-[var(--sa-gray)] animate-pulse" />
        </div>
        <div v-else-if="!sections.length" class="text-center py-12 bg-[var(--sa-gray)]/50 rounded-2xl border-2 border-dashed border-[var(--sa-gray)]">
          <p class="text-[var(--sa-taupe)]">No sections yet. Click "Add Section" to begin.</p>
        </div>
        <div v-else class="space-y-3">
          <SaCard v-for="section in sections" :key="section.id" padding="p-5" class="hover:shadow-md transition-shadow">
            <div class="flex items-start gap-4">
              <div class="flex-shrink-0 w-16">
                <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[var(--sa-gray)] text-[var(--sa-dark)] font-semibold text-sm">{{ section.section_number }}</span>
              </div>
              <div class="flex-1">
                <h3 class="font-medium text-[var(--sa-dark)] mb-1">{{ section.title }}</h3>
                <p class="text-xs text-[var(--sa-taupe)] mb-2">{{ section.raw_text?.length || 0 }} characters</p>
                <p class="text-sm text-[var(--sa-taupe)] line-clamp-2">{{ section.raw_text?.substring(0, 200) }}{{ section.raw_text?.length > 200 ? '...' : '' }}</p>
              </div>
              <div class="flex items-center gap-2">
                <button @click="openSectionModal(section)" class="p-2 hover:bg-[var(--sa-gray)] rounded-lg"><svg class="w-4 h-4 text-[var(--sa-dark)]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                <button @click="deleteSection(section.id)" class="p-2 hover:bg-red-50 rounded-lg"><svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                <button @click="reorderSection(section.id, section.order - 1)" :disabled="section.order === 1" class="p-2 hover:bg-[var(--sa-gray)] rounded-lg disabled:opacity-30"><svg class="w-4 h-4 text-[var(--sa-dark)]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg></button>
                <button @click="reorderSection(section.id, section.order + 1)" :disabled="section.order === sections.length" class="p-2 hover:bg-[var(--sa-gray)] rounded-lg disabled:opacity-30"><svg class="w-4 h-4 text-[var(--sa-dark)]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg></button>
              </div>
            </div>
          </SaCard>
        </div>
      </div>
    </div>

    <div v-if="showSectionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
      <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-[var(--sa-gray)] flex justify-between items-center">
          <h3 class="text-lg font-semibold text-[var(--sa-dark)]">{{ editingSection ? 'Edit Section' : 'Add New Section' }}</h3>
          <button @click="closeSectionModal" class="text-[var(--sa-taupe)] hover:text-[var(--sa-dark)]">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        <form @submit.prevent="saveSection" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-[var(--sa-dark)] mb-1">Section Number</label>
            <input v-model="sectionForm.section_number" type="text" placeholder="e.g., 2.1" required class="w-full border border-[var(--sa-gray)] rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--sa-dark)]" />
          </div>
          <div>
            <label class="block text-sm font-medium text-[var(--sa-dark)] mb-1">Section Title</label>
            <input v-model="sectionForm.title" type="text" placeholder="e.g., Introduction" required class="w-full border border-[var(--sa-gray)] rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--sa-dark)]" />
          </div>
          <div>
            <label class="block text-sm font-medium text-[var(--sa-dark)] mb-1">Section Content</label>
            <textarea v-model="sectionForm.raw_text" placeholder="Paste your section content here..." required rows="8" class="w-full border border-[var(--sa-gray)] rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--sa-dark)] font-mono text-sm"></textarea>
            <p class="text-xs text-[var(--sa-taupe)] mt-1">{{ sectionForm.raw_text?.length || 0 }} characters</p>
          </div>
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-[var(--sa-gray)]">
            <SaButton @click="closeSectionModal" variant="secondary" type="button">Cancel</SaButton>
            <SaButton :loading="saving" type="submit" variant="primary">{{ editingSection ? 'Update' : 'Add' }} Section</SaButton>
          </div>
        </form>
      </div>
    </div>
  </AppShell>
</template>