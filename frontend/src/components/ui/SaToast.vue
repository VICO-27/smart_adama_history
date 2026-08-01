<script setup lang="ts">
import { ref, watch } from 'vue'

const props = defineProps<{
  message: string
  type?: 'success' | 'error' | 'info'
  show: boolean
  duration?: number
}>()

const emit = defineEmits<{ close: [] }>()

watch(() => props.show, (val) => {
  if (val) {
    setTimeout(() => emit('close'), props.duration ?? 4000)
  }
})
</script>

<template>
  <Teleport to="body">
    <Transition name="toast">
      <div
        v-if="show"
        :class="[
          'fixed bottom-6 left-1/2 -translate-x-1/2 z-[60]',
          'px-5 py-3 rounded-2xl shadow-[var(--shadow-lg)] glass',
          'flex items-center gap-3 text-sm font-medium min-w-[280px] max-w-[420px]',
          type === 'error'   ? 'border-red-300 text-red-700' :
          type === 'success' ? 'border-green-300 text-green-700' :
                               'border-[var(--sa-gray)] text-[var(--sa-dark)]',
        ]"
        role="status"
        aria-live="polite"
      >
        <span class="text-lg" aria-hidden="true">
          {{ type === 'error' ? '✕' : type === 'success' ? '✓' : 'ℹ' }}
        </span>
        {{ message }}
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: opacity 0.2s, transform 0.2s var(--ease-out); }
.toast-enter-from, .toast-leave-to       { opacity: 0; transform: translateX(-50%) translateY(12px); }
</style>
