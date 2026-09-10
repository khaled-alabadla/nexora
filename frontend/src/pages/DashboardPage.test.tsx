import { afterEach, expect, it, vi } from 'vitest'

import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'

import { sessionKey } from '@/features/auth/session'
import { noContent, ok, stubFetch } from '@/test/fetchStub'
import { createTestQueryClient, renderWithProviders } from '@/test/utils'

import { DashboardPage } from './DashboardPage'

afterEach(() => vi.unstubAllGlobals())

const role = { slug: 'owner', name: 'Owner', level: 100 }

const withCompany = {
  user: { id: 1, name: 'Ada', email: 'ada@example.com', email_verified: true },
  current_company: { id: 1, name: 'Alpha Inc', slug: 'alpha', status: 'active', role },
  companies: [{ id: 1, name: 'Alpha Inc', slug: 'alpha', status: 'active', role }],
  permissions: ['member.view'],
}

const noCompany = {
  user: { id: 1, name: 'Ada', email: 'ada@example.com', email_verified: true },
  current_company: null,
  companies: [],
  permissions: [],
}

it('shows the active company and members panel', async () => {
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, withCompany)
  stubFetch([
    { method: 'GET', url: '/company/members', handler: ok([]) },
    { method: 'GET', url: '/company/invitations', handler: ok([]) },
    { method: 'GET', url: '/roles', handler: ok([]) },
    { method: 'GET', url: '/health', handler: ok({ status: 'ok', database: true, cache: true }) },
  ])

  renderWithProviders(<DashboardPage />, { queryClient: qc })
  expect(await screen.findByRole('heading', { name: 'Alpha Inc' })).toBeInTheDocument()
  expect(screen.getByText('Members')).toBeInTheDocument()
})

it('prompts to create a company when the user has none', () => {
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, noCompany)
  stubFetch([
    { method: 'GET', url: '/health', handler: ok({ status: 'ok', database: true, cache: true }) },
  ])

  renderWithProviders(<DashboardPage />, { queryClient: qc })
  expect(screen.getByRole('button', { name: 'Create company' })).toBeInTheDocument()
})

it('signs the user out', async () => {
  const user = userEvent.setup({ delay: null })
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, withCompany)
  const mock = stubFetch([
    { method: 'GET', url: '/company/members', handler: ok([]) },
    { method: 'GET', url: '/company/invitations', handler: ok([]) },
    { method: 'GET', url: '/roles', handler: ok([]) },
    { method: 'GET', url: '/health', handler: ok({ status: 'ok', database: true, cache: true }) },
    { method: 'POST', url: '/auth/logout', handler: noContent() },
  ])

  renderWithProviders(<DashboardPage />, { queryClient: qc })
  await user.click(screen.getByRole('button', { name: 'Sign out' }))

  await vi.waitFor(
    () => {
      expect(mock.mock.calls.some((c) => c[0].includes('/auth/logout'))).toBe(true)
      expect(qc.getQueryData(sessionKey)).toBeNull()
    },
    { timeout: 8000 },
  )
})
