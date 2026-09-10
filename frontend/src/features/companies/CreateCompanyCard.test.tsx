import { afterEach, expect, it, vi } from 'vitest'

import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'

import { sessionKey } from '@/features/auth/session'
import { created, fail, ok, stubFetch } from '@/test/fetchStub'
import { createTestQueryClient, renderWithProviders } from '@/test/utils'

import { CreateCompanyCard } from './CreateCompanyCard'

afterEach(() => vi.unstubAllGlobals())

const emptySession = {
  user: { id: 1, name: 'Ada', email: 'ada@example.com', email_verified: true },
  current_company: null,
  companies: [],
  permissions: [],
}

it('creates a company and refreshes the session', async () => {
  const user = userEvent.setup({ delay: null })
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, emptySession)

  const mock = stubFetch([
    {
      method: 'POST',
      url: '/companies',
      handler: created({
        id: 5,
        name: 'New Co',
        slug: 'new-co',
        status: 'active',
        role: { slug: 'owner', name: 'Owner', level: 100 },
      }),
    },
    { method: 'GET', url: '/auth/me', handler: ok(emptySession) },
  ])

  renderWithProviders(<CreateCompanyCard />, { queryClient: qc })
  await user.type(screen.getByLabelText('Company name'), 'New Co')
  await user.click(screen.getByRole('button', { name: 'Create company' }))

  await vi.waitFor(
    () =>
      expect(
        mock.mock.calls.some((c) => c[0].includes('/companies') && c[1]?.method === 'POST'),
      ).toBe(true),
    { timeout: 8000 },
  )
})

it('shows a validation error', async () => {
  const user = userEvent.setup({ delay: null })
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, emptySession)
  stubFetch([
    {
      method: 'POST',
      url: '/companies',
      handler: fail(422, 'Invalid', { name: ['The name field is required.'] }),
    },
  ])

  renderWithProviders(<CreateCompanyCard />, { queryClient: qc })
  await user.type(screen.getByLabelText('Company name'), 'x')
  await user.click(screen.getByRole('button', { name: 'Create company' }))

  expect(await screen.findByRole('alert')).toHaveTextContent('The name field is required.')
})
