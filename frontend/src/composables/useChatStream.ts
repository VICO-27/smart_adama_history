/**
 * useChatStream
 *
 * Thin composable wrapping the native fetch + ReadableStream SSE parser.
 * Decoupled from the chat store so it can be called from any component
 * that needs direct streaming control.
 *
 * Backend SSE format (from ChatMessageController):
 *   event: delta\n
 *   data: {"token": "..."}\n\n
 *
 *   event: done\n
 *   data: {"message_id": "...", "grounded": true, "citations": [...]}\n\n
 *
 *   event: error\n
 *   data: {"error": {"code": "...", "message": "..."}}\n\n
 */

import { ref } from 'vue'

export interface StreamDonePayload {
  message_id: string
  grounded: boolean
  citations: App.ChatMessageSource[]
}

export interface StreamErrorPayload {
  code: string
  message: string
}

export function useChatStream() {
  const streaming        = ref(false)
  const accumulatedText  = ref('')
  const streamError      = ref<string | null>(null)

  /**
   * Stream a chat message from the backend.
   *
   * @param url       Full endpoint URL (e.g. http://localhost:8000/api/v1/chat/sessions/{id}/messages)
   * @param token     Sanctum bearer token
   * @param content   The user's message text
   * @param onToken   Callback fired for every streamed token fragment
   * @param onDone    Callback fired when the stream closes successfully
   * @param onError   Callback fired on stream error
   */
  async function stream(
    url: string,
    token: string,
    content: string,
    onToken?: (token: string) => void,
    onDone?:  (payload: StreamDonePayload) => void,
    onError?: (payload: StreamErrorPayload) => void,
  ): Promise<void> {
    streaming.value       = true
    accumulatedText.value = ''
    streamError.value     = null

    let response: Response
    try {
      response = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'text/event-stream',
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({ content }),
      })
    } catch (e) {
      streaming.value   = false
      streamError.value = 'Network error — could not reach the server.'
      onError?.({ code: 'NETWORK_ERROR', message: streamError.value })
      return
    }

    if (!response.ok) {
      streaming.value   = false
      streamError.value = response.status === 429
        ? 'Too many messages. Please wait a moment.'
        : response.status === 403
          ? 'Access denied to this chat session.'
          : `Request failed (${response.status}).`
      onError?.({ code: `HTTP_${response.status}`, message: streamError.value })
      return
    }

    if (!response.body) {
      streaming.value   = false
      streamError.value = 'No response body received.'
      onError?.({ code: 'NO_BODY', message: streamError.value })
      return
    }

    const reader  = response.body.getReader()
    const decoder = new TextDecoder()
    let   buffer  = ''
    let   currentEvent = ''

    try {
      while (true) {
        const { done, value } = await reader.read()
        if (done) break

        buffer += decoder.decode(value, { stream: true })
        const lines = buffer.split('\n')
        buffer = lines.pop() ?? '' // keep incomplete last line

        for (const raw of lines) {
          const line = raw.trimEnd()

          if (line === '') {
            // Blank line = event boundary; reset event name
            currentEvent = ''
            continue
          }

          if (line.startsWith('event: ')) {
            currentEvent = line.slice(7).trim()
            continue
          }

          if (line.startsWith('data: ')) {
            const jsonStr = line.slice(6)
            let payload: Record<string, unknown>
            try {
              payload = JSON.parse(jsonStr)
            } catch {
              continue // skip malformed line
            }

            if (currentEvent === 'delta' || payload.token !== undefined) {
              const tok = payload.token as string
              accumulatedText.value += tok
              onToken?.(tok)
            } else if (currentEvent === 'done' || payload.message_id !== undefined) {
              onDone?.({
                message_id: payload.message_id as string,
                grounded:   payload.grounded as boolean ?? false,
                citations:  (payload.citations as App.ChatMessageSource[]) ?? [],
              })
            } else if (currentEvent === 'error' || payload.error !== undefined) {
              const err = payload.error as StreamErrorPayload
              streamError.value = err?.message ?? 'AI service error.'
              onError?.(err ?? { code: 'STREAM_ERROR', message: streamError.value })
            }
          }
        }
      }
    } finally {
      streaming.value = false
    }
  }

  return {
    streaming,
    accumulatedText,
    streamError,
    stream,
  }
}
