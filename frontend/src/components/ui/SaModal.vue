<script setup lang="ts">
defineProps<{ title?: string; open: boolean }>()
const emit = defineEmits<{ close: [] }>()
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        :aria-modal="true"
        :aria-label="title"
        @keydown.escape="emit('close')"
      >
        <!-- Backdrop -->
        <div
          class="absolute inset-0 bg-[var(--sa-dark)]/30 backdrop-blur-sm"
          @click="emit('close')"
        />
        <!-- Panel -->
        <div class="relative w-full max-w-md glass rounded-2xl shadow-[var(--shadow-lg)] p-6">
          <div v-if="title" class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold font-display text-[var(--sa-dark)]">{{ title }}</h2>
            <button
              class="p-1 rounded-lg hover:bg-[var(--sa-gray)] transition-colors"
              aria-label="Close"
              @click="emit('close')"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
          <slot />
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s var(--ease-out); }
.modal-enter-from, .modal-leave-to       { opacity: 0; }
.modal-enter-active > div + div, .modal-leave-active > div + div {
  transition: transform 0.22s var(--ease-out), opacity 0.22s var(--ease-out);
}
.modal-enter-from > div + div { transform: scale(0.96) translateY(8px); opacity: 0; }
.modal-leave-to   > div + div { transform: scale(0.96) translateY(8px); opacity: 0; }
</style>
