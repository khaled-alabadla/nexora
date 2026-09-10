import { afterEach, expect, it, vi } from 'vitest'

import { fail, ok, stubFetch } from '@/test/fetchStub'
import { renderWithProviders } from '@/test/utils'

import App from './App'

afterEach(() => {
  vi.unstubAllGlobals()
})

const session = {
  user: { id: 1, name: 'Ada Lovelace', email: 'ada@example.com', email_verified: true },
  current_company: {
    id: 7,
    name: 'Analytical Engines',
    slug: 'analytical-engines',
    status: 'active',
    role: { slug: 'owner', name: 'Owner', level: 100 },
  },
  companies: [
    {
      id: 7,
      name: 'Analytical Engines',
      slug: 'analytical-engines',
      status: 'active',
      role: { slug: 'owner', name: 'Owner', level: 100 },
    },
  ],
  permissions: [
    'member.view',
    'member.invite',
    'member.role.update',
    'member.remove',
    'company.update',
  ],
}

it('shows the login page when unauthenticated', async () => {
  stubFetch([{ method: 'GET', url: '/auth/me', handler: fail(401, 'Unauthenticated.') }])

  const { findByRole } = renderWithProviders(<App />, { route: '/' })

  expect(await findByRole('heading', { name: 'Sign in' })).toBeInTheDocument()
})

it('renders the dashboard for an authenticated user', async () => {
  stubFetch([
    { method: 'GET', url: '/auth/me', handler: ok(session) },
    { method: 'GET', url: '/company/members', handler: ok([]) },
    { method: 'GET', url: '/company/invitations', handler: ok([]) },
    {
      method: 'GET',
      url: '/roles',
      handler: ok([{ slug: 'employee', name: 'Employee', level: 10 }]),
    },
    { method: 'GET', url: '/health', handler: ok({ status: 'ok', database: true, cache: true }) },
  ])

  const { findByRole } = renderWithProviders(<App />, { route: '/' })

  expect(await findByRole('heading', { name: 'Analytical Engines' })).toBeInTheDocument()
})

it('redirects unknown routes to the dashboard', async () => {
  stubFetch([{ method: 'GET', url: '/auth/me', handler: fail(401, 'Unauthenticated.') }])

  const { findByRole } = renderWithProviders(<App />, { route: '/nope' })

  // unknown → "/" → RequireAuth → login
  expect(await findByRole('heading', { name: 'Sign in' })).toBeInTheDocument()
})
