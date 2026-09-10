import { type FormEvent, useState } from 'react'

import { Link } from 'react-router-dom'

import { Alert, Button, Field, Input } from '@/components/ui'

import { AuthShell } from './AuthShell'
import { useForgotPassword } from './session'

export function ForgotPasswordPage() {
  const forgot = useForgotPassword()
  const [email, setEmail] = useState('')

  function submit(event: FormEvent) {
    event.preventDefault()
    forgot.mutate(email)
  }

  return (
    <AuthShell title="Reset your password" subtitle="We'll email you a reset link.">
      {forgot.isSuccess ? (
        <Alert tone="success">If that email is registered, a reset link is on its way.</Alert>
      ) : (
        <form onSubmit={submit} className="flex flex-col gap-4" noValidate>
          <Field label="Email" htmlFor="email">
            <Input
              id="email"
              type="email"
              autoComplete="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
            />
          </Field>
          <Button type="submit" disabled={forgot.isPending}>
            {forgot.isPending ? 'Sending…' : 'Send reset link'}
          </Button>
        </form>
      )}

      <p className="mt-4 text-sm text-neutral-500 dark:text-neutral-400">
        <Link to="/login" className="hover:underline">
          Back to sign in
        </Link>
      </p>
    </AuthShell>
  )
}
