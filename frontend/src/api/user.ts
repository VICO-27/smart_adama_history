import apiClient from './client'

export const userApi = {
  getProfile: () =>
    apiClient.get<{ user: App.UserProfile }>('/users/me'),

  updateProfile: (data: Partial<{ name: string; locale: string; notify_badges: boolean }>) =>
    apiClient.patch<{ user: App.UserProfile }>('/users/me', data),

  uploadAvatar: (file: File) => {
    const form = new FormData()
    form.append('avatar', file)
    return apiClient.post<{ avatar_url: string }>('/users/me/avatar', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  deleteAccount: () =>
    apiClient.delete('/users/me'),
}
