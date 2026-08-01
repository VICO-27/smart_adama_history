import apiClient from './client'

export const chatApi = {
  listSessions: (page = 1) =>
    apiClient.get<{ sessions: App.ChatSession[]; meta: App.PaginationMeta }>('/chat/sessions', { params: { page } }),

  createSession: (title?: string) =>
    apiClient.post<{ session: App.ChatSession }>('/chat/sessions', { title }),

  getSession: (sessionId: string) =>
    apiClient.get<{ session: App.ChatSession }>(`/chat/sessions/${sessionId}`),

  renameSession: (sessionId: string, title: string) =>
    apiClient.patch<{ session: App.ChatSession }>(`/chat/sessions/${sessionId}`, { title }),

  deleteSession: (sessionId: string) =>
    apiClient.delete(`/chat/sessions/${sessionId}`),

  /**
   * Returns the raw EventSource URL for SSE streaming.
   * The caller is responsible for creating the EventSource / fetch stream.
   * We use fetch + ReadableStream here to support Bearer token auth
   * (native EventSource does not support custom headers).
   */
  streamMessage: (sessionId: string, content: string, token: string) => {
    const url = `${apiClient.defaults.baseURL}/chat/sessions/${sessionId}/messages`
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'text/event-stream',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({ content }),
    })
  },
}
