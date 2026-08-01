<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useBooksStore } from '@/stores/books'
import AppShell from '@/components/layout/AppShell.vue'
import SaCard   from '@/components/ui/SaCard.vue'
import SaButton from '@/components/ui/SaButton.vue'

const route = useRoute()
const books = useBooksStore()

const chapterId = computed(() => route.params.chapterId as string)

onMounted(async () => {
  await books.loadChapter(chapterId.value)
  await books.markChapterRead(chapterId.value)
  await books.loadChapterQuiz(chapterId.value)
})

const chapter  = computed(() => books.currentChapter)
const progress = computed(() => books.chapterProgress)
const quiz     = computed(() => books.currentQuiz)
const best     = computed(() => books.bestAttempt)
</script>

<template>
  <AppShell max-width="max-w-3xl">
    <!-- Loading -->
    <div v-if="books.loading && !chapter" class="space-y-4">
      <div class="h-8 w-48 rounded-xl bg-[var(--sa-gray)] animate-pulse" />
      <div class="h-4 w-full rounded-xl bg-[var(--sa-gray)] animate-pulse" />
      <div class="h-4 w-3/4 rounded-xl bg-[var(--sa-gray)] animate-pulse" />
    </div>

    <template v-else-if="chapter">
      <!-- Breadcrumb -->
      <div class="flex items-center gap-2 text-sm text-[var(--sa-taupe)] mb-6">
        <RouterLink to="/dashboard" class="hover:text-[var(--sa-dark)] transition-colors">Dashboard</RouterLink>
        <span>/</span>
        <span class="text-[var(--sa-dark)] font-medium">{{ chapter.title }}</span>
      </div>

      <!-- Chapter header -->
      <div class="flex items-start justify-between gap-4 mb-8">
        <div>
          <h1 class="font-display text-2xl font-semibold text-[var(--sa-dark)]">{{ chapter.title }}</h1>
          <p v-if="progress?.is_completed" class="mt-1 text-sm text-green-600 font-medium">✓ Completed</p>
        </div>
        <RouterLink v-if="quiz" :to="`/chapters/${chapterId}/quiz`">
          <SaButton size="sm">
            {{ best?.passed ? '🏆 Retake Quiz' : '📝 Take Quiz' }}
          </SaButton>
        </RouterLink>
      </div>

      <!-- Sections -->
      <div class="space-y-4">
        <SaCard
          v-for="section in chapter.sections"
          :key="section.id"
          padding="p-5"
        >
          <h2 class="font-display font-semibold text-[var(--sa-dark)] mb-2">{{ section.title }}</h2>
          <p v-if="section.raw_text" class="text-sm text-[var(--sa-dark)] leading-relaxed whitespace-pre-line">
            {{ section.raw_text }}
          </p>
          <p v-else class="text-sm text-[var(--sa-taupe)] italic">Content being processed…</p>
        </SaCard>
      </div>

      <!-- Quiz CTA -->
      <div v-if="quiz" class="mt-8">
        <SaCard glass padding="p-6">
          <div class="flex items-center justify-between gap-4">
            <div>
              <h3 class="font-display font-semibold text-[var(--sa-dark)]">Chapter Quiz</h3>
              <p class="text-sm text-[var(--sa-taupe)] mt-0.5">
                {{ best ? `Best score: ${best.score_pct}%` : `Pass at ${quiz.passing_score_pct}% to complete this chapter` }}
              </p>
            </div>
            <RouterLink :to="`/chapters/${chapterId}/quiz`">
              <SaButton>{{ best?.passed ? 'Retake' : 'Start Quiz' }}</SaButton>
            </RouterLink>
          </div>
        </SaCard>
      </div>
    </template>
  </AppShell>
</template>
