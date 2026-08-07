import { ref, type Ref } from 'vue'

/**
 * SSE Event types
 */
type SSEEvent = 'delta' | 'done' | 'error'

/**
 * SSE Event payloads
 */
interface DeltaEvent {
  token: string
}

interface DoneEvent {
  message_id: string
  grounded: boolean
  citations: Citation[]
}

interface ErrorEvent {
  error: {
    code: string
    message: string
  }
}

interface Citation {
  chunk_id: string
  chapter_title: string
  section_title: string
  excerpt: string
  similarity: number
}

/**
 * Combined payload types
 */
type SSEPayload = DeltaEvent | DoneEvent | ErrorEvent

/**
 * Use Chat Stream composable
 * 
 * Handles SSE streaming from the backend using fetch + ReadableStream
 * instead of native EventSource (which doesn't support Bearer tokens).
 * 
 * Backend sends:
 *   event: delta
 *   data: {"token":"..."}
 *   
 *   event: done
 *   data: {"message_id":"...","grounded":true,"citations":[...]}
 *   
 *   event: error
 *   data: {"error":{"code":"...","message":"..."}}
 */
export function useChatStream() {
  const streaming = ref(false)
  const accumulatedText = ref('')
  const streamError = ref<string | null>(null)
  const donePayload = ref<DoneEvent | null>(null)
  const abortController = ref<AbortController | null>(null)

  /**
   * Stream a message to the backend via SSE
   * 
   * @param url - Backend endpoint URL
   * @param token - Bearer token for authentication
   * @param content - User message content
   * @param onToken - Callback for each token delta
   * @param onDone - Callback when streaming completes
   * @param onError - Callback when an error occurs
   * @returns Promise that resolves when streaming completes or fails
   */
  async function stream(
    url: string,
    token: string,
    content: string,
    onToken?: (token: string) => void,
    onDone?: (payload: DoneEvent) => void,
    onError?: (error: ErrorEvent) => void
  ): Promise<void> {
    // Cancel any existing stream
    if (abortController.value) {
      abortController.value.abort()
    }

    abortController.value = new AbortController()

    streaming.value = true
    accumulatedText.value = ''
    streamError.value = null
    donePayload.value = null

    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'text/event-stream',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({ content }),
        signal: abortController.value.signal,
      })

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}))
        throw new Error(errorData.message || `HTTP ${response.status}`)
      }

      if (!response.body) {
        throw new Error('Response body is null')
      }

      // Parse SSE stream using ReadableStream
      const reader = response.body.getReader()
      const decoder = new TextDecoder('utf-8')
      let buffer = ''

      while (true) {
        const { done, value } = await reader.read()

        if (done) {
          break
        }

        buffer += decoder.decode(value, { stream: true })

        // Process complete lines
        const lines = buffer.split('\n')
        buffer = lines.pop() || '' // Keep incomplete line in buffer

        for (const line of lines) {
          const parsed = parseSSELine(line)
          if (parsed) {
            await handleSSEEvent(parsed, onToken, onDone, onError)
          }
        }
      }

      // Process any remaining buffer
      if (buffer.trim()) {
        const parsed = parseSSELine(buffer)
        if (parsed) {
          await handleSSEEvent(parsed, onToken, onDone, onError)
        }
      }
    } catch (error: any) {
      if (error.name === 'AbortError') {
        // Stream was cancelled
        return
      }
      
      streamError.value = error.message || 'Unknown error'
      onError?.({ error: { code: 'STREAM_ERROR', message: streamError.value } })
    } finally {
      streaming.value = false
    }
  }

  /**
   * Cancel the current stream
   */
  function cancel() {
    if (abortController.value) {
      abortController.value.abort()
    }
  }

  /**
   * Parse a single SSE line
   * Returns null if line doesn't start with 'data: ' or 'event: '
   */
  function parseSSELine(line: string): { type: SSEEvent; data: SSEPayload } | null {
    line = line.trim()

    // Skip empty lines and comments
    if (!line || line.startsWith(':')) {
      return null
    }

    // Parse event type
    if (line.startsWith('event: ')) {
      const eventType = line.substring(7).trim() as SSEEvent
      // Store event type for later use
      return { type: eventType, data: { token: '' } as SSEPayload }
    }

    // Parse data
    if (line.startsWith('data: ')) {
      const dataStr = line.substring(6).trim()
      try {
        const data = JSON.parse(dataStr)
        // Determine the event type based on data structure
        if ('token' in data) {
          return { type: 'delta', data: data as DeltaEvent }
        } else if ('error' in data) {
          return { type: 'error', data: data as ErrorEvent }
        } else if ('message_id' in data) {
          return { type: 'done', data: data as DoneEvent }
        }
        return null
      } catch {
        return null
      }
    }

    return null
  }

  /**
   * Handle a parsed SSE event
   */
  async function handleSSEEvent(
    parsed: { type: SSEEvent; data: SSEPayload },
    onToken?: (token: string) => void,
    onDone?: (payload: DoneEvent) => void,
    onError?: (error: ErrorEvent) => void
  ): Promise<void> {
    const { type, data } = parsed

    switch (type) {
      case 'delta':
        if ('token' in data && data.token) {
          accumulatedText.value += data.token
          onToken?.(data.token)
        }
        break

      case 'done':
        if ('message_id' in data) {
          donePayload.value = data as DoneEvent
          onDone?.(data as DoneEvent)
        }
        break

      case 'error':
        if ('error' in data && data.error) {
          streamError.value = data.error.message
          onError?.(data as ErrorEvent)
        }
        break

      default:
        console.warn('Unknown SSE event type:', type)
    }
  }

  return {
    streaming,
    accumulatedText,
    streamError,
    donePayload,
    stream,
    cancel,
  }
}

// Type exports for global types
export type { SSEEvent, DeltaEvent, DoneEvent, ErrorEvent, Citation }
