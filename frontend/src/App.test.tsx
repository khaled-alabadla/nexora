import { afterEach, beforeEach, expect, it, vi } from 'vitest'

import { jsonResponse, renderWithProviders } from '@/test/utils'

import App from './App'

const fetchMock = vi.fn()

beforeEach(() => {
  vi.stubGlobal('fetch', fetchMock)
  fetchMock.mockResolvedValue(
    jsonResponse({ data: { status: 'ok', database: true, cache: true }, message: 'OK' }),
  )
})

afterEach(() => {
  vi.unstubAllGlobals()
  fetchMock.mockReset()
})

it('mounts and renders the health card', async () => {
  const { findByText } = renderWithProviders(<App />)

  expect(await findByText('Nexora API')).toBeInTheDocument()
})
