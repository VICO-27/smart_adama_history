<script setup lang="ts">
import { onMounted } from 'vue'
import { useBooksStore } from '@/stores/books'
import AppShell from '@/components/layout/AppShell.vue'
import SaCard   from '@/components/ui/SaCard.vue'

const books = useBooksStore()
onMounted(() => books.loadBooks())
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <h1 class="font-display text-2xl font-semibold text-[var(--sa-dark)]">Quizzes</h1>

      <div v-if="books.loading" class="space-y-3">
        <div v-for="i in 3" :key="i" class="h-16 rounded-2xl bg-[var(--sa-gray)] animate-pulse" />
      </div>

      <template v-else>
        <div
          v-for="book in books.books"
          :key="book.id"
          class="space-y-3"
        >
          <h2 class="font-display font-semibold text-[var(--sa-dark)]">{{ book.title }}</h2>
          <SaCard
            v-for="ch in (book.chapters?.data || book.chapters || [])"
            :key="ch.id"
            padding="p-5"
          >
            <div class="flex items-center justify-between">
              <div>
                <p class="font-medium text-[var(--sa-dark)]">{{ ch.title }}</p>
                <p class="text-xs text-[var(--sa-taupe)] mt-0.5 capitalize">Ingestion: {{ ch.ingestion_status }}</p>
              </div>
              <span
                :class="[
                  'text-xs px-2 py-1 rounded-full font-medium',
                  ch.ingestion_status === 'ready' ? 'bg-green-100 text-green-700' : 'bg-[var(--sa-gray)] text-[var(--sa-taupe)]',
                ]"
              >{{ ch.ingestion_status }}</span>
            </div>
          </SaCard>
        </div>
      </template>
    </div>
  </AppShell>
</template>