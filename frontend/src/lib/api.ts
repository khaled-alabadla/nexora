/**
 * Minimal typed API client for the Nexora backend.
 *
 * The first-party SPA authenticates with Sanctum cookie sessions (ADR-0004):
 *  - every request is sent with credentials;
 *  - `csrf()` primes the XSRF-TOKEN cookie before the first mutating request;
 *  - mutating requests echo that cookie back in the `X-XSRF-TOKEN` header.
 */

const API_BASE = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'
const API_PREFIX = '/api/v1'

const SAFE_METHODS = new Set(['GET', 'HEAD', 'OPTIONS'])

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

function readCookie(name: string): string | undefined {
  const match = new RegExp(`(?:^|; )${name}=([^;]*)`).exec(document.cookie)
  return match?.[1] ? decodeURIComponent(match[1]) : undefined
}

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  const method = (init.method ?? 'GET').toUpperCase()
  const headers = new Headers(init.headers)
  headers.set('Accept', 'application/json')

  if (init.body != null && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json')
  }

  if (!SAFE_METHODS.has(method)) {
    const token = readCookie('XSRF-TOKEN')
    if (token) headers.set('X-XSRF-TOKEN', token)
  }

  const response = await fetch(`${API_BASE}${API_PREFIX}${path}`, {
    ...init,
    method,
    credentials: 'include',
    headers,
  })

  const isJson = response.headers.get('content-type')?.includes('application/json') ?? false
  const payload: unknown = isJson ? await response.json() : null

  if (!response.ok) {
    const body = (payload ?? {}) as { message?: string; errors?: Record<string, string[]> }
    throw new ApiError(response.status, body.message ?? response.statusText, body.errors)
  }

  if (response.status === 204 || payload == null) {
    return undefined as T
  }

  return (payload as ApiEnvelope<T>).data
}

/** Prime the XSRF-TOKEN cookie. Call once before the first write request. */
export async function csrf(): Promise<void> {
  await fetch(`${API_BASE}/sanctum/csrf-cookie`, { credentials: 'include' })
}

function withBody(method: string, body: unknown): RequestInit {
  return {
    method,
    ...(body === undefined ? {} : { body: JSON.stringify(body) }),
  }
}

export const api = {
  get: <T>(path: string): Promise<T> => request<T>(path, { method: 'GET' }),
  post: <T>(path: string, body?: unknown): Promise<T> => request<T>(path, withBody('POST', body)),
  put: <T>(path: string, body?: unknown): Promise<T> => request<T>(path, withBody('PUT', body)),
  patch: <T>(path: string, body?: unknown): Promise<T> => request<T>(path, withBody('PATCH', body)),
  delete: <T>(path: string, body?: unknown): Promise<T> =>
    request<T>(path, withBody('DELETE', body)),
}
