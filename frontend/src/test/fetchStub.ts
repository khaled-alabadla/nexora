import { vi } from 'vitest'

import { jsonResponse, noContentResponse } from './utils'

type Handler = (init: RequestInit) => Response | Promise<Response>

interface RouteSpec {
  method?: string
  /** substring match against the request URL */
  url: string
  handler: Handler
}

/**
 * Install a `fetch` stub that dispatches by method + URL substring. Unmatched
 * requests reject so tests fail loudly. `/sanctum/csrf-cookie` is handled by
 * default. Returns the underlying mock for assertions.
 */
export function stubFetch(routes: RouteSpec[]) {
  const mock = vi.fn(async (url: string, init: RequestInit = {}) => {
    const method = (init.method ?? 'GET').toUpperCase()

    if (url.includes('/sanctum/csrf-cookie')) return jsonResponse(null)

    for (const route of routes) {
      if ((route.method ?? 'GET').toUpperCase() === method && url.includes(route.url)) {
        return route.handler(init)
      }
    }

    throw new Error(`Unhandled request: ${method} ${url}`)
  })

  vi.stubGlobal('fetch', mock)
  return mock
}

export function ok(body: unknown) {
  return () => jsonResponse({ data: body, message: 'OK' })
}

export function created(body: unknown) {
  return () => jsonResponse({ data: body, message: 'Created' }, { status: 201 })
}

export function noContent() {
  return () => noContentResponse()
}

export function fail(status: number, message: string, errors?: Record<string, string[]>) {
  return () => jsonResponse({ message, ...(errors ? { errors } : {}) }, { ok: false, status })
}
