import { afterEach, expect, it, vi } from 'vitest'

import { screen } from '@testing-library/react'

import { sessionKey } from '@/features/auth/session'
import { fail, ok, stubFetch } from '@/test/fetchStub'
import { createTestQueryClient, renderWithProviders } from '@/test/utils'

import App from '@/App'

afterEach(() => vi.unstubAllGlobals())

const session = {
  user: { id: 1, name: 'Ada', email: 'ada@example.com', email_verified: true },
  current_company: null,
  companies: [],
  permissions: [],
}

it('redirects an unauthenticated visitor to login', async () => {
  stubFetch([{ method: 'GET', url: '/auth/me', handler: fail(401, 'Unauthenticated.') }])

  renderWithProviders(<App />, { route: '/invitations/tok123/accept' })

  expect(await screen.findByRole('heading', { name: 'Sign in' })).toBeInTheDocument()
})

it('accepts the invitation for a signed-in user', async () => {
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, session)

  stubFetch([
    { method: 'GET', url: '/auth/me', handler: ok(session) },
    { method: 'POST', url: '/invitations/tok123/accept', handler: ok({ id: 4, name: 'Gamma' }) },
  ])

  renderWithProviders(<App />, { route: '/invitations/tok123/accept', queryClient: qc })

  expect(await screen.findByText("You've joined the company.")).toBeInTheDocument()
})

it('reports an expired or invalid invitation', async () => {
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, session)

  stubFetch([
    { method: 'GET', url: '/auth/me', handler: ok(session) },
    {
      method: 'POST',
      url: '/invitations/bad/accept',
      handler: fail(422, 'This invitation has expired.', {
        token: ['This invitation has expired.'],
      }),
    },
  ])

  renderWithProviders(<App />, { route: '/invitations/bad/accept', queryClient: qc })

  expect(await screen.findByRole('status')).toHaveTextContent('This invitation has expired.')
})
