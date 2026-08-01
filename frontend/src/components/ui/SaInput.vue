<script setup lang="ts">
defineProps<{
  label?: string
  error?: string
  type?: string
  placeholder?: string
  disabled?: boolean
  autocomplete?: string
}>()

const model = defineModel<string>()
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" class="text-sm font-medium text-[var(--sa-dark)]">{{ label }}</label>
    <input
      v-model="model"
      :type="type ?? 'text'"
      :placeholder="placeholder"
      :disabled="disabled"
      :autocomplete="autocomplete"
      :class="[
        'w-full px-4 py-2.5 rounded-xl text-sm bg-white border transition-all duration-150',
        'placeholder:text-[var(--sa-taupe)] text-[var(--sa-dark)]',
        'focus:outline-none focus:ring-2 focus:ring-[var(--sa-dark)] focus:border-transparent',
        error
          ? 'border-red-400 bg-red-50'
          : 'border-[var(--sa-gray)] hover:border-[var(--sa-taupe)]',
        disabled && 'opacity-50 cursor-not-allowed bg-[var(--sa-bg)]',
      ]"
    />
    <p v-if="error" class="text-xs text-red-600" role="alert">{{ error }}</p>
  </div>
</template>
