import { defineStore } from 'pinia'
import { ref } from 'vue'
import { chatApi } from '@/api/chat'
import { useAuthStore } from './auth'
import { useChatStream, type ChatMessageStreamDone } from '@/composables/useChatStream'

export const useChatStore = defineStore('chat', () => {
  const sessions        = ref<App.ChatSession[]>([])
  const currentSession  = ref<App.ChatSession | null>(null)
  const meta            = ref<App.PaginationMeta | null>(null)
  const error           = ref<string | null>(null)

  // Expose the composable's reactive state at store level
  // so components can bind to streaming / streamingContent directly.
  const { streaming, accumulatedText: streamingContent, streamError, stream } = useChatStream()

  // ── Session CRUD ───────────────────────────────────────────────────────────

  async function loadSessions(page = 1) {
    const { data } = await chatApi.listSessions(page)
    sessions.value = page === 1 ? data.sessions : [...sessions.value, ...data.sessions]
    meta.value     = data.meta
  }

  async function createSession(title?: string): Promise<App.ChatSession> {
    const { data } = await chatApi.createSession(title)
    sessions.value.unshift(data.session)
    currentSession.value = data.session
    return data.session
  }

  async function loadSession(sessionId: string): Promise<App.ChatSession> {
    const { data } = await chatApi.getSession(sessionId)
    currentSession.value = data.session
    return data.session
  }

  async function renameSession(sessionId: string, title: string) {
    const { data } = await chatApi.renameSession(sessionId, title)
    const idx = sessions.value.findIndex((s) => s.id === sessionId)
    if (idx !== -1) sessions.value[idx] = data.session
    if (currentSession.value?.id === sessionId) currentSession.value = data.session
  }

  async function deleteSession(sessionId: string) {
    await chatApi.deleteSession(sessionId)
    sessions.value = sessions.value.filter((s) => s.id !== sessionId)
    if (currentSession.value?.id === sessionId) currentSession.value = null
  }

  // ── Message streaming ──────────────────────────────────────────────────────

  /**
   * Send a message to the backend and stream the AI response via SSE.
   *
   * Flow:
   *  1. Optimistically push the user message into currentSession.messages
   *  2. Open SSE stream via fetch (token auth, no EventSource)
   *  3. Accumulate tokens in `streamingContent` (reactive)
   *  4. On `done` event: push the final assistant message + citations
   *
   * Components can bind `streaming` and `streamingContent` to show
   * a live typing effect without any additional local state.
   */
  async function sendMessage(
    sessionId: string,
    content: string,
    onToken?: (token: string) => void,
    onDone?:  (payload: ChatMessageStreamDone) => void,
  ) {
    const authStore = useAuthStore()
    if (!authStore.token) throw new Error('Not authenticated')

    error.value = null

    // ── 1. Optimistic user message ─────────────────────────────────────────
    if (currentSession.value?.id === sessionId) {
      currentSession.value.messages ??= []
      currentSession.value.messages.push({
        id:              crypto.randomUUID(),
        chat_session_id: sessionId,
        role:            'user',
        content,
        created_at:      new Date().toISOString(),
      })
    }

    // ── 2. SSE stream ──────────────────────────────────────────────────────
    const baseUrl = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000'
    const url     = `${baseUrl}/api/v1/chat/sessions/${sessionId}/messages`

    await stream(
      url,
      authStore.token,
      content,
      onToken,
      (donePayload) => {
        // ── 3. Push final assistant message ───────────────────────────────
        if (currentSession.value?.id === sessionId) {
          currentSession.value.messages ??= []
          currentSession.value.messages.push({
            id:              donePayload.message_id,
            chat_session_id: sessionId,
            role:            'assistant',
            content:         streamingContent.value,
            created_at:      new Date().toISOString(),
            sources:         donePayload.citations,
          })
        }
        // Update session title if it changed (auto-titled from first message)
        loadSessions(1).catch(() => {})
        onDone?.(donePayload)
      },
      (errPayload) => {
        error.value = errPayload.message
      },
    )
  }

  return {
    sessions,
    currentSession,
    meta,
    error,
    streaming,
    streamingContent,
    streamError,
    loadSessions,
    createSession,
    loadSession,
    renameSession,
    deleteSession,
    sendMessage,
  }
})
