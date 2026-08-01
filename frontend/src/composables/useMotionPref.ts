/**
 * useMotionPref
 * Reactive wrapper around prefers-reduced-motion.
 * Returns `reducedMotion: Ref<boolean>` that updates if the user
 * changes their OS setting during a session.
 *
 * Usage:
 *   const { reducedMotion } = useMotionPref()
 *   // In template: :class="{ 'animate-bounce': !reducedMotion }"
 */

import { ref, onMounted, onUnmounted } from 'vue'

export function useMotionPref() {
  const mq            = window.matchMedia('(prefers-reduced-motion: reduce)')
  const reducedMotion = ref(mq.matches)

  function onChange(e: MediaQueryListEvent) {
    reducedMotion.value = e.matches
  }

  onMounted(() => mq.addEventListener('change', onChange))
  onUnmounted(() => mq.removeEventListener('change', onChange))

  return { reducedMotion }
}
