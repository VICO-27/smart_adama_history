<script setup lang="ts">
defineProps<{ glass?: boolean; padding?: string }>()
</script>

<template>
  <div
    :class="[
      'rounded-2xl border border-[var(--sa-gray)] shadow-[var(--shadow-sm)]',
      glass ? 'glass' : 'bg-white',
      padding ?? 'p-6',
    ]"
  >
    <slot />
  </div>
</template>
