import { afterEach, beforeEach, expect, it, vi } from 'vitest'

import { jsonResponse, renderWithProviders } from '@/test/utils'

import { HealthCard } from './HealthCard'

const fetchMock = vi.fn()

beforeEach(() => {
  vi.stubGlobal('fetch', fetchMock)
})

afterEach(() => {
  vi.unstubAllGlobals()
  fetchMock.mockReset()
})

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
  fetchMock.mockResolvedValue(jsonResponse({ message: 'Server error' }, { ok: false, status: 500 }))

  const { findByRole } = renderWithProviders(<HealthCard />)

  // useHealth retries once with backoff, so allow a little extra time.
  expect(await findByRole('alert', {}, { timeout: 3000 })).toHaveTextContent(
    'Cannot reach the backend.',
  )
})
