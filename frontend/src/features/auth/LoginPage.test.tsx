import { afterEach, expect, it, vi } from 'vitest'

import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'

import { fail, ok, stubFetch } from '@/test/fetchStub'
import { renderWithProviders } from '@/test/utils'

import { LoginPage } from './LoginPage'

afterEach(() => vi.unstubAllGlobals())

const guest = { method: 'GET' as const, url: '/auth/me', handler: fail(401, 'Unauthenticated.') }

const session = {
  user: { id: 1, name: 'A', email: 'a@example.com', email_verified: true },
  current_company: null,
  companies: [],
  permissions: [],
}

it('signs in and stores the session', async () => {
  const user = userEvent.setup({ delay: null })
  stubFetch([guest, { method: 'POST', url: '/auth/login', handler: ok(session) }])

  const { queryClient } = renderWithProviders(<LoginPage />, { route: '/login' })

  await user.type(screen.getByLabelText('Email'), 'a@example.com')
  await user.type(screen.getByLabelText('Password'), 'secret')
  await user.click(screen.getByRole('button', { name: 'Sign in' }))

  await vi.waitFor(() => expect(queryClient.getQueryData(['session'])).toEqual(session), {
    timeout: 8000,
  })
})

it('surfaces an invalid-credentials error', async () => {
  const user = userEvent.setup({ delay: null })
  stubFetch([
    guest,
    {
      method: 'POST',
      url: '/auth/login',
      handler: fail(422, 'Invalid', { email: ['These credentials do not match our records.'] }),
    },
  ])

  renderWithProviders(<LoginPage />, { route: '/login' })
  await user.type(screen.getByLabelText('Email'), 'a@example.com')
  await user.type(screen.getByLabelText('Password'), 'wrong')
  await user.click(screen.getByRole('button', { name: 'Sign in' }))

  expect(await screen.findByRole('status')).toHaveTextContent(
    'These credentials do not match our records.',
  )
})

it('shows the verified banner from the query string', async () => {
  stubFetch([guest])
  renderWithProviders(<LoginPage />, { route: '/login?verified=1' })
  expect(await screen.findByText(/Email verified/)).toBeInTheDocument()
})
