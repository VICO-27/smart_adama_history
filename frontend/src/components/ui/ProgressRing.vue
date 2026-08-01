<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  value: number   // 0–100
  size?: number   // px, default 80
  stroke?: number // default 6
  label?: string
}>()

const size  = computed(() => props.size ?? 80)
const sw    = computed(() => props.stroke ?? 6)
const r     = computed(() => (size.value - sw.value) / 2)
const circ  = computed(() => 2 * Math.PI * r.value)
const dash  = computed(() => (props.value / 100) * circ.value)
</script>

<template>
  <div class="inline-flex flex-col items-center gap-1">
    <svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`" role="img" :aria-label="`${value}% complete`">
      <!-- Track -->
      <circle
        :cx="size / 2" :cy="size / 2" :r="r"
        fill="none" :stroke-width="sw"
        stroke="var(--sa-gray)"
      />
      <!-- Progress -->
      <circle
        :cx="size / 2" :cy="size / 2" :r="r"
        fill="none" :stroke-width="sw"
        stroke="var(--sa-dark)"
        stroke-linecap="round"
        :stroke-dasharray="`${dash} ${circ}`"
        transform="rotate(-90 40 40)"
        style="transition: stroke-dasharray 0.5s var(--ease-out)"
      />
      <!-- Label text -->
      <text
        :x="size / 2" :y="size / 2"
        text-anchor="middle" dominant-baseline="central"
        class="text-[10px] font-semibold fill-[var(--sa-dark)]"
        :font-size="size * 0.18"
      >{{ Math.round(value) }}%</text>
    </svg>
    <span v-if="label" class="text-xs text-[var(--sa-taupe)]">{{ label }}</span>
  </div>
</template>
