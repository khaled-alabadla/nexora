import { afterEach, beforeEach, expect, it, vi } from 'vitest'

import { renderWithProviders } from '@/test/utils'

import { HealthCard } from './HealthCard'

const fetchMock = vi.fn()

beforeEach(() => {
  vi.stubGlobal('fetch', fetchMock)
})

afterEach(() => {
  vi.unstubAllGlobals()
  fetchMock.mockReset()
})

function jsonResponse(body: unknown, ok = true, status = 200): Response {
  return {
    ok,
    status,
    headers: new Headers({ 'content-type': 'application/json' }),
    json: () => Promise.resolve(body),
  } as Response
}

it('renders the datastore indicators when the backend is healthy', async () => {
  fetchMock.mockResolvedValue(
    jsonResponse({ data: { status: 'ok', database: true, cache: true }, message: 'OK' }),
  )

  const { findByText } = renderWithProviders(<HealthCard />)

  expect(await findByText('Status: ok')).toBeInTheDocument()
  expect(await findByText('Database')).toBeInTheDocument()
  expect(await findByText('Cache')).toBeInTheDocument()
})

it('shows an error state when the backend is unreachable', async () => {
  fetchMock.mockResolvedValue(jsonResponse({ message: 'Server error' }, false, 500))

  const { findByRole } = renderWithProviders(<HealthCard />)

  // useHealth retries once with backoff, so allow a little extra time.
  expect(await findByRole('alert', {}, { timeout: 3000 })).toHaveTextContent(
    'Cannot reach the backend.',
  )
})
