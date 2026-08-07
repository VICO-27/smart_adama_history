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

  // ── Feedback (👍/👎) ──────────────────────────────────────────────────────

  /**
   * Send feedback for a message (like or dislike).
   */
  sendFeedback: (messageId: string, feedback: 'like' | 'dislike', token: string) => {
    const url = `${apiClient.defaults.baseURL}/api/v1/messages/${messageId}/feedback`
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({ feedback }),
    })
  },

  /**
   * Delete feedback for a message.
   */
  deleteFeedback: (messageId: string, token: string) => {
    const url = `${apiClient.defaults.baseURL}/api/v1/messages/${messageId}/feedback`
    return fetch(url, {
      method: 'DELETE',
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  },

  /**
   * Get feedback status for a message.
   */
  getFeedback: (messageId: string, token: string) => {
    const url = `${apiClient.defaults.baseURL}/api/v1/messages/${messageId}/feedback`
    return fetch(url, {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  },
}
