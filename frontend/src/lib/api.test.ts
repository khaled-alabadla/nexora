import { afterEach, beforeEach, expect, it, vi } from 'vitest'

import { jsonResponse as json } from '@/test/utils'

import { api, ApiError } from './api'

const fetchMock = vi.fn()

beforeEach(() => {
  vi.stubGlobal('fetch', fetchMock)
  document.cookie = 'XSRF-TOKEN=test-token'
})

afterEach(() => {
  vi.unstubAllGlobals()
  fetchMock.mockReset()
  document.cookie = 'XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT'
})

it('unwraps the { data } envelope', async () => {
  fetchMock.mockResolvedValue(json({ data: { ok: true }, message: 'OK' }))

  await expect(api.get('/health')).resolves.toEqual({ ok: true })

  const [url, init] = fetchMock.mock.calls[0] as [string, RequestInit]
  expect(url).toBe('http://localhost:8000/api/v1/health')
  expect(init.credentials).toBe('include')
})

it('sends X-XSRF-TOKEN on mutating requests only', async () => {
  fetchMock.mockResolvedValue(json({ data: null, message: 'OK' }))

  await api.post('/things', { a: 1 })

  const [, init] = fetchMock.mock.calls[0] as [string, RequestInit]
  const headers = new Headers(init.headers)
  expect(headers.get('X-XSRF-TOKEN')).toBe('test-token')
  expect(headers.get('Content-Type')).toBe('application/json')
})

it('throws ApiError carrying status and validation errors', async () => {
  fetchMock.mockResolvedValue(
    json(
      { message: 'Validation failed', errors: { email: ['required'] } },
      { ok: false, status: 422 },
    ),
  )

  await expect(api.get('/x')).rejects.toMatchObject({
    name: 'ApiError',
    status: 422,
    message: 'Validation failed',
  })
  await expect(api.get('/x')).rejects.toBeInstanceOf(ApiError)
})
