/**
 * useScrollReveal
 * Applies a fade/slide-up reveal when elements enter the viewport.
 * Respects prefers-reduced-motion automatically (Req 15.5).
 *
 * Usage:
 *   const el = ref<HTMLElement | null>(null)
 *   useScrollReveal(el)
 *
 * The element needs the `.reveal` CSS class (or inline style) to
 * start invisible; this composable adds `.revealed` when in view.
 */

import { onMounted, onUnmounted, type Ref } from 'vue'

export interface ScrollRevealOptions {
  threshold?: number   // 0–1, default 0.15
  rootMargin?: string  // default '0px'
  once?: boolean       // default true — stop observing after first reveal
}

export function useScrollReveal(
  target: Ref<HTMLElement | null> | HTMLElement | null,
  options: ScrollRevealOptions = {},
) {
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches

  let observer: IntersectionObserver | null = null

  onMounted(() => {
    const el = target instanceof HTMLElement ? target : target?.value
    if (!el) return

    // Reduced-motion: reveal immediately without animation
    if (prefersReduced) {
      el.classList.add('revealed')
      return
    }

    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('revealed')
            if (options.once !== false) observer?.unobserve(entry.target)
          }
        })
      },
      {
        threshold:  options.threshold  ?? 0.15,
        rootMargin: options.rootMargin ?? '0px',
      },
    )

    observer.observe(el)
  })

  onUnmounted(() => observer?.disconnect())
}

/**
 * Directive version — v-reveal — for declarative use in templates.
 *
 * Usage in main.ts:
 *   app.directive('reveal', scrollRevealDirective)
 *
 * Usage in template:
 *   <div v-reveal>...</div>
 */
export const scrollRevealDirective = {
  mounted(el: HTMLElement) {
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches

    el.style.opacity   = '0'
    el.style.transform = 'translateY(16px)'
    el.style.transition = 'opacity 0.4s var(--ease-out, ease-out), transform 0.4s var(--ease-out, ease-out)'

    if (prefersReduced) {
      el.style.opacity   = '1'
      el.style.transform = 'none'
      return
    }

    const obs = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          el.style.opacity   = '1'
          el.style.transform = 'none'
          obs.unobserve(el)
        }
      },
      { threshold: 0.1 },
    )
    obs.observe(el)
  },
}
