import { afterEach, expect, it, vi } from 'vitest'

import { screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'

import { sessionKey } from '@/features/auth/session'
import { created, noContent, ok, stubFetch } from '@/test/fetchStub'
import { createTestQueryClient, renderWithProviders } from '@/test/utils'

import { MembersPanel } from './MembersPanel'

afterEach(() => vi.unstubAllGlobals())

const role = (slug: string, name: string, level = 10) => ({ slug, name, level })

function sessionWith(permissions: string[]) {
  return {
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
    ],
    permissions,
  }
}

const members = [
  {
    user_id: 1,
    name: 'Ada',
    email: 'ada@example.com',
    role: role('owner', 'Owner', 100),
    joined_at: null,
  },
  {
    user_id: 2,
    name: 'Bob',
    email: 'bob@example.com',
    role: role('employee', 'Employee'),
    joined_at: null,
  },
]

const roles = [
  role('administrator', 'Administrator', 80),
  role('employee', 'Employee', 10),
  role('owner', 'Owner', 100),
]

function renderPanel(permissions: string[], routes: Parameters<typeof stubFetch>[0] = []) {
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, sessionWith(permissions))
  stubFetch([
    { method: 'GET', url: '/company/members', handler: ok(members) },
    { method: 'GET', url: '/company/invitations', handler: ok([]) },
    { method: 'GET', url: '/roles', handler: ok(roles) },
    ...routes,
  ])
  return renderWithProviders(<MembersPanel />, { queryClient: qc })
}

it('hides management for a role without member.view', () => {
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, sessionWith([]))
  stubFetch([])
  renderWithProviders(<MembersPanel />, { queryClient: qc })
  expect(screen.getByText(/don't have access/)).toBeInTheDocument()
})

it('lists members for a permitted role', async () => {
  renderPanel(['member.view'])
  expect(await screen.findByText('bob@example.com')).toBeInTheDocument()
  expect(screen.queryByRole('button', { name: 'Send invite' })).not.toBeInTheDocument()
})

it('invites a new member', async () => {
  const user = userEvent.setup({ delay: null })
  renderPanel(
    ['member.view', 'member.invite'],
    [
      {
        method: 'POST',
        url: '/company/invitations',
        handler: created({
          id: 9,
          email: 'new@example.com',
          role: role('employee', 'Employee'),
          status: 'pending',
          expires_at: '',
          accepted_at: null,
          created_at: null,
        }),
      },
    ],
  )

  await user.type(await screen.findByLabelText('Invite by email'), 'new@example.com')
  await user.click(screen.getByRole('button', { name: 'Send invite' }))

  expect(await screen.findByText('Invitation sent.')).toBeInTheDocument()
})

it('changes a member role and removes a member', async () => {
  const user = userEvent.setup({ delay: null })
  renderPanel(
    ['member.view', 'member.role.update', 'member.remove'],
    [
      {
        method: 'PATCH',
        url: '/company/members/2',
        handler: ok({ ...members[1], role: role('administrator', 'Administrator', 80) }),
      },
      { method: 'DELETE', url: '/company/members/2', handler: noContent() },
    ],
  )

  const bobRow = (await screen.findByText('Bob')).closest('li') as HTMLElement
  await user.selectOptions(within(bobRow).getByLabelText('Role for Bob'), 'administrator')
  await user.click(within(bobRow).getByRole('button', { name: 'Remove' }))
})
