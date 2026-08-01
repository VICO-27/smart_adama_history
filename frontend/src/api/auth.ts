import apiClient from './client'

export interface RegisterPayload {
  name: string
  email: string
  password: string
}

export interface LoginPayload {
  email: string
  password: string
}

export interface AuthResponse {
  user: App.User
  token: string
}

export const authApi = {
  register: (data: RegisterPayload) =>
    apiClient.post<AuthResponse>('/auth/register', data),

  login: (data: LoginPayload) =>
    apiClient.post<AuthResponse>('/auth/login', data),

  logout: () =>
    apiClient.post('/auth/logout'),

  me: () =>
    apiClient.get<{ user: App.UserProfile }>('/auth/me'),

  forgotPassword: (email: string) =>
    apiClient.post('/auth/password/forgot', { email }),

  resetPassword: (data: { token: string; email: string; password: string }) =>
    apiClient.post('/auth/password/reset', data),
}
