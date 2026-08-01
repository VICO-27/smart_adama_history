import { defineStore } from 'pinia'
import { ref } from 'vue'
import { progressApi } from '@/api/progress'

export const useProgressStore = defineStore('progress', () => {
  const dashboard = ref<App.Dashboard | null>(null)
  const summary = ref<App.ProgressSummary | null>(null)
  const badges = ref<App.Badge[]>([])
  const streak = ref<App.Streak | null>(null)
  const loading = ref(false)

  // Newly earned badges during this session (for toast/animation trigger)
  const newlyEarnedBadges = ref<App.Badge[]>([])

  async function loadDashboard() {
    loading.value = true
    try {
      const { data } = await progressApi.getDashboard()
      dashboard.value = data.dashboard
    } finally {
      loading.value = false
    }
  }

  async function loadSummary() {
    const { data } = await progressApi.getSummary()
    summary.value = data.progress
  }

  async function loadBadges() {
    const { data } = await progressApi.getBadges()
    const previous = new Set(badges.value.filter((b) => b.earned).map((b) => b.id))
    badges.value = data.badges

    // Detect newly earned since last load (for Req 11.2 celebratory animation)
    newlyEarnedBadges.value = data.badges.filter(
      (b) => b.earned && b.awarded_at && !previous.has(b.id),
    )
  }

  async function loadStreak() {
    const { data } = await progressApi.getStreak()
    streak.value = data.streak
  }

  async function loadAll() {
    await Promise.all([loadDashboard(), loadBadges(), loadStreak()])
  }

  function clearNewlyEarned() {
    newlyEarnedBadges.value = []
  }

  return {
    dashboard,
    summary,
    badges,
    streak,
    loading,
    newlyEarnedBadges,
    loadDashboard,
    loadSummary,
    loadBadges,
    loadStreak,
    loadAll,
    clearNewlyEarned,
  }
})
