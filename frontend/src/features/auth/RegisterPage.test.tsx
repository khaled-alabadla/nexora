import { afterEach, expect, it, vi } from 'vitest'

import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'

import { fail, ok, stubFetch } from '@/test/fetchStub'
import { renderWithProviders } from '@/test/utils'

import { RegisterPage } from './RegisterPage'

afterEach(() => vi.unstubAllGlobals())

const guest = { method: 'GET' as const, url: '/auth/me', handler: fail(401, 'Unauthenticated.') }

const session = {
  user: { id: 1, name: 'Ada', email: 'ada@example.com', email_verified: false },
  current_company: {
    id: 3,
    name: 'Engines',
    slug: 'engines',
    status: 'active',
    role: { slug: 'owner', name: 'Owner', level: 100 },
  },
  companies: [],
  permissions: [],
}

async function fillForm(user: ReturnType<typeof userEvent.setup>) {
  await user.type(screen.getByLabelText('Your name'), 'Ada')
  await user.type(screen.getByLabelText('Email'), 'ada@example.com')
  await user.type(screen.getByLabelText('Company name'), 'Engines')
  await user.type(screen.getByLabelText('Password'), 'Str0ng-pass!')
  await user.type(screen.getByLabelText('Confirm password'), 'Str0ng-pass!')
}

it('registers and stores the session', async () => {
  const user = userEvent.setup({ delay: null })
  stubFetch([guest, { method: 'POST', url: '/auth/register', handler: ok(session) }])

  const { queryClient } = renderWithProviders(<RegisterPage />, { route: '/register' })
  await fillForm(user)
  await user.click(screen.getByRole('button', { name: 'Create account' }))

  await vi.waitFor(() => expect(queryClient.getQueryData(['session'])).toEqual(session), {
    timeout: 8000,
  })
})

it('shows per-field validation errors', async () => {
  const user = userEvent.setup({ delay: null })
  stubFetch([
    guest,
    {
      method: 'POST',
      url: '/auth/register',
      handler: fail(422, 'Invalid', { email: ['The email has already been taken.'] }),
    },
  ])

  renderWithProviders(<RegisterPage />, { route: '/register' })
  await fillForm(user)
  await user.click(screen.getByRole('button', { name: 'Create account' }))

  expect(await screen.findByRole('alert')).toHaveTextContent('The email has already been taken.')
})
