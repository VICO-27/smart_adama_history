import apiClient from './client'

export const progressApi = {
  getSummary: () =>
    apiClient.get<{ progress: App.ProgressSummary }>('/users/me/progress'),

  getBadges: () =>
    apiClient.get<{ badges: App.Badge[] }>('/users/me/badges'),

  getStreak: () =>
    apiClient.get<{ streak: App.Streak }>('/users/me/streak'),

  getDashboard: () =>
    apiClient.get<{ dashboard: App.Dashboard }>('/dashboard'),
}
