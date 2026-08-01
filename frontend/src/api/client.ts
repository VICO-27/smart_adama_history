import axios, { type AxiosInstance, type AxiosError } from 'axios'

/**
 * Central Axios instance for all Smart Adama API calls.
 *
 * Error envelope from the backend:
 *   { error: { code: string, message: string, details?: Record<string, string[]> } }
 *
 * This client normalises that envelope so stores and components can read errors
 * without parsing the raw Axios error themselves.
 */

const apiClient: AxiosInstance = axios.create({
  baseURL: `${import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000'}/api/v1`,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  withCredentials: false,
})

// ── Request: attach Bearer token ──────────────────────────────────────────────
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('sa_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// ── Response: normalise errors ────────────────────────────────────────────────
apiClient.interceptors.response.use(
  (res) => res,
  (error: AxiosError<ApiErrorEnvelope>) => {
    const status = error.response?.status

    // 401 — clear token and redirect to login (unless already there)
    if (status === 401) {
      localStorage.removeItem('sa_token')
      if (window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
    }

    // Attach normalised error fields directly onto the error object
    // so callers can do: catch(e) { e.userMessage; e.fieldErrors }
    if (error.response?.data?.error) {
      const envelope = error.response.data.error
      ;(error as ApiError).userMessage = envelope.message
      ;(error as ApiError).fieldErrors  = envelope.details ?? {}
    }

    return Promise.reject(error)
  },
)

export default apiClient

// ── Shared types ──────────────────────────────────────────────────────────────

export interface ApiErrorEnvelope {
  error: {
    code: string
    message: string
    details?: Record<string, string[]>
  }
}

/** Extended AxiosError with normalised fields attached by the interceptor */
export interface ApiError extends AxiosError<ApiErrorEnvelope> {
  userMessage: string
  fieldErrors: Record<string, string[]>
}

/**
 * Type-guard — use in catch blocks to get typed error fields.
 * @example
 *   } catch (e) {
 *     if (isApiError(e)) toast(e.userMessage)
 *   }
 */
export function isApiError(e: unknown): e is ApiError {
  return axios.isAxiosError(e) && !!(e as ApiError).userMessage
}

/**
 * Extract the first validation error message for a specific field.
 * Useful in <script setup> to populate inline field errors.
 */
export function fieldError(e: unknown, field: string): string | undefined {
  if (isApiError(e)) return e.fieldErrors?.[field]?.[0]
  return undefined
}
