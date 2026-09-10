import { afterEach, expect, it, vi } from 'vitest'

import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'

import { sessionKey } from '@/features/auth/session'
import { ok, stubFetch } from '@/test/fetchStub'
import { createTestQueryClient, renderWithProviders } from '@/test/utils'

import { CompanySwitcher } from './CompanySwitcher'

afterEach(() => vi.unstubAllGlobals())

const role = (slug: string, name: string, level: number) => ({ slug, name, level })

const session = {
  user: { id: 1, name: 'Ada', email: 'ada@example.com', email_verified: true },
  current_company: {
    id: 1,
    name: 'Alpha',
    slug: 'alpha',
    status: 'active',
    role: role('owner', 'Owner', 100),
  },
  companies: [
    { id: 1, name: 'Alpha', slug: 'alpha', status: 'active', role: role('owner', 'Owner', 100) },
    { id: 2, name: 'Beta', slug: 'beta', status: 'active', role: role('employee', 'Employee', 10) },
  ],
  permissions: [],
}

it('lists the caller companies and switches the active one', async () => {
  const user = userEvent.setup({ delay: null })
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, session)

  const mock = stubFetch([
    { method: 'PUT', url: '/companies/2/active', handler: ok(session.companies[1]) },
    {
      method: 'GET',
      url: '/auth/me',
      handler: ok({ ...session, current_company: session.companies[1] }),
    },
  ])

  renderWithProviders(<CompanySwitcher />, { queryClient: qc })

  const select = screen.getByLabelText('Active company')
  expect(select).toHaveValue('1')

  await user.selectOptions(select, '2')

  await vi.waitFor(
    () => {
      expect(mock.mock.calls.some((c) => c[0].includes('/companies/2/active'))).toBe(true)
    },
    { timeout: 8000 },
  )
})

it('renders nothing when the user has no companies', () => {
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, { ...session, companies: [], current_company: null })

  const { container } = renderWithProviders(<CompanySwitcher />, { queryClient: qc })
  expect(container).toBeEmptyDOMElement()
})
