<script setup lang="ts">
defineProps<{
  variant?: 'primary' | 'secondary' | 'ghost' | 'danger'
  size?: 'sm' | 'md' | 'lg'
  loading?: boolean
  disabled?: boolean
  type?: 'button' | 'submit' | 'reset'
}>()
</script>

<template>
  <button
    :type="type ?? 'button'"
    :disabled="disabled || loading"
    :class="[
      'inline-flex items-center justify-center gap-2 font-medium rounded-xl transition-all duration-150',
      'focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[var(--sa-dark)]',
      'disabled:opacity-50 disabled:cursor-not-allowed',
      // Size
      size === 'sm'  ? 'px-3 py-1.5 text-sm'  :
      size === 'lg'  ? 'px-7 py-3.5 text-base' :
                       'px-5 py-2.5 text-sm',
      // Variant
      variant === 'secondary' ? 'bg-[var(--sa-gray)] text-[var(--sa-dark)] hover:bg-[var(--sa-taupe)]' :
      variant === 'ghost'     ? 'bg-transparent text-[var(--sa-dark)] hover:bg-[var(--sa-gray)]' :
      variant === 'danger'    ? 'bg-red-600 text-white hover:bg-red-700 active:scale-[0.98]' :
                                'bg-[var(--sa-dark)] text-[var(--sa-bg)] hover:opacity-90 active:scale-[0.98]',
    ]"
  >
    <!-- Loading spinner -->
    <svg v-if="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
    </svg>
    <slot />
  </button>
</template>
