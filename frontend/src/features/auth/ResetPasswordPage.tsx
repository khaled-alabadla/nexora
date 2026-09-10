import { type FormEvent, useState } from 'react'

import { Link, useNavigate, useSearchParams } from 'react-router-dom'

import { Alert, Button, Field, Input } from '@/components/ui'
import { errorMessage, fieldErrors } from '@/lib/forms'

import { AuthShell } from './AuthShell'
import { useResetPassword } from './session'

export function ResetPasswordPage() {
  const [params] = useSearchParams()
  const navigate = useNavigate()
  const reset = useResetPassword()

  const token = params.get('token') ?? ''
  const email = params.get('email') ?? ''
  const [password, setPassword] = useState('')
  const [confirmation, setConfirmation] = useState('')

  const errors = fieldErrors(reset.error)

  function submit(event: FormEvent) {
    event.preventDefault()
    reset.mutate(
      { token, email, password, password_confirmation: confirmation },
      { onSuccess: () => void navigate('/login?reset=1', { replace: true }) },
    )
  }

  if (!token || !email) {
    return (
      <AuthShell title="Reset your password">
        <Alert>This reset link is invalid or incomplete.</Alert>
        <p className="mt-4 text-sm">
          <Link to="/forgot-password" className="hover:underline">
            Request a new link
          </Link>
        </p>
      </AuthShell>
    )
  }

  return (
    <AuthShell title="Choose a new password" subtitle={`For ${email}`}>
      <form onSubmit={submit} className="flex flex-col gap-4" noValidate>
        {reset.isError && Object.keys(errors).length === 0 ? (
          <Alert>{errorMessage(reset.error, 'Could not reset password.')}</Alert>
        ) : null}
        {errors.email ? <Alert>{errors.email}</Alert> : null}

        <Field label="New password" htmlFor="password" error={errors.password}>
          <Input
            id="password"
            type="password"
            autoComplete="new-password"
            required
            value={password}
            onChange={(e) => setPassword(e.target.value)}
          />
        </Field>
        <Field label="Confirm password" htmlFor="password_confirmation">
          <Input
            id="password_confirmation"
            type="password"
            autoComplete="new-password"
            required
            value={confirmation}
            onChange={(e) => setConfirmation(e.target.value)}
          />
        </Field>

        <Button type="submit" disabled={reset.isPending}>
          {reset.isPending ? 'Saving…' : 'Set new password'}
        </Button>
      </form>
    </AuthShell>
  )
}
