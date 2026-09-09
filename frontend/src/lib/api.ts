/**
 * Minimal typed API client for the Nexora backend.
 *
 * The first-party SPA authenticates with Sanctum cookie sessions (ADR-0004),
 * so every request is sent with credentials. Before the first mutating request
 * the caller must prime the CSRF cookie via `csrf()`.
 */

const API_BASE = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'
const API_PREFIX = '/api/v1'

export class ApiError extends Error {
  readonly status: number
  readonly errors: Record<string, string[]> | undefined

  constructor(status: number, message: string, errors?: Record<string, string[]>) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }
}

interface ApiEnvelope<T> {
  data: T
  message: string
  meta?: unknown
}

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  const headers = new Headers(init.headers)
  headers.set('Accept', 'application/json')
  if (init.body != null && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json')
  }

  const response = await fetch(`${API_BASE}${API_PREFIX}${path}`, {
    ...init,
    credentials: 'include',
    headers,
  })

  const isJson = response.headers.get('content-type')?.includes('application/json') ?? false
  const payload: unknown = isJson ? await response.json() : null

  if (!response.ok) {
    const body = (payload ?? {}) as { message?: string; errors?: Record<string, string[]> }
    throw new ApiError(response.status, body.message ?? response.statusText, body.errors)
  }

  return (payload as ApiEnvelope<T>).data
}

/** Prime the XSRF-TOKEN cookie. Call once before the first write request. */
export async function csrf(): Promise<void> {
  await fetch(`${API_BASE}/sanctum/csrf-cookie`, { credentials: 'include' })
}

export const api = {
  get: <T>(path: string): Promise<T> => request<T>(path, { method: 'GET' }),
  post: <T>(path: string, body?: unknown): Promise<T> =>
    request<T>(path, {
      method: 'POST',
      ...(body === undefined ? {} : { body: JSON.stringify(body) }),
    }),
}
