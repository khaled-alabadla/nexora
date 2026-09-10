import { afterEach, expect, it, vi } from 'vitest'

import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'

import { fail, ok, stubFetch } from '@/test/fetchStub'
import { renderWithProviders } from '@/test/utils'

import App from '@/App'

import { ForgotPasswordPage } from './ForgotPasswordPage'
import { ResetPasswordPage } from './ResetPasswordPage'

afterEach(() => vi.unstubAllGlobals())

it('forgot-password shows a neutral confirmation regardless of the address', async () => {
  const user = userEvent.setup({ delay: null })
  stubFetch([{ method: 'POST', url: '/auth/password/forgot', handler: ok(null) }])

  renderWithProviders(<ForgotPasswordPage />, { route: '/forgot-password' })
  await user.type(screen.getByLabelText('Email'), 'nobody@example.com')
  await user.click(screen.getByRole('button', { name: 'Send reset link' }))

  expect(await screen.findByText(/reset link is on its way/)).toBeInTheDocument()
})

it('reset-password rejects a link with no token', () => {
  renderWithProviders(<ResetPasswordPage />, { route: '/reset-password' })
  expect(screen.getByText(/invalid or incomplete/)).toBeInTheDocument()
})

it('reset-password submits the new password and redirects to login', async () => {
  const user = userEvent.setup({ delay: null })
  stubFetch([
    { method: 'GET', url: '/auth/me', handler: fail(401, 'Unauthenticated.') },
    { method: 'POST', url: '/auth/password/reset', handler: ok(null) },
  ])

  renderWithProviders(<App />, { route: '/reset-password?token=abc&email=a%40example.com' })

  await user.type(screen.getByLabelText('New password'), 'Br4nd-new-pass!')
  await user.type(screen.getByLabelText('Confirm password'), 'Br4nd-new-pass!')
  await user.click(screen.getByRole('button', { name: 'Set new password' }))

  expect(await screen.findByText(/Password updated/)).toBeInTheDocument()
})

it('reset-password surfaces a broker error', async () => {
  const user = userEvent.setup({ delay: null })
  stubFetch([
    {
      method: 'POST',
      url: '/auth/password/reset',
      handler: fail(422, 'Invalid', { email: ['This password reset token is invalid.'] }),
    },
  ])

  renderWithProviders(<ResetPasswordPage />, {
    route: '/reset-password?token=abc&email=a%40example.com',
  })
  await user.type(screen.getByLabelText('New password'), 'Br4nd-new-pass!')
  await user.type(screen.getByLabelText('Confirm password'), 'Br4nd-new-pass!')
  await user.click(screen.getByRole('button', { name: 'Set new password' }))

  expect(await screen.findByRole('status')).toHaveTextContent(
    'This password reset token is invalid.',
  )
})
