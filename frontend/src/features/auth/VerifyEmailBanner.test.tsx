import { afterEach, expect, it, vi } from 'vitest'

import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'

import { ok, stubFetch } from '@/test/fetchStub'
import { createTestQueryClient, renderWithProviders } from '@/test/utils'

import { sessionKey } from './session'
import { VerifyEmailBanner } from './VerifyEmailBanner'

afterEach(() => vi.unstubAllGlobals())

function session(verified: boolean) {
  return {
    user: { id: 1, name: 'Ada', email: 'ada@example.com', email_verified: verified },
    current_company: null,
    companies: [],
    permissions: [],
  }
}

it('renders nothing once the email is verified', () => {
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, session(true))
  const { container } = renderWithProviders(<VerifyEmailBanner />, { queryClient: qc })
  expect(container).toBeEmptyDOMElement()
})

it('resends the verification link', async () => {
  const user = userEvent.setup({ delay: null })
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, session(false))
  stubFetch([{ method: 'POST', url: '/auth/email/verification-notification', handler: ok(null) }])

  renderWithProviders(<VerifyEmailBanner />, { queryClient: qc })
  await user.click(screen.getByRole('button', { name: 'Resend link' }))

  expect(await screen.findByRole('button', { name: /Sent/ })).toBeInTheDocument()
})
