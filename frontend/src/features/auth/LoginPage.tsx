import { type FormEvent, useState } from 'react'

import { Link, Navigate, useSearchParams } from 'react-router-dom'

import { Alert, Button, Field, Input } from '@/components/ui'
import { errorMessage, fieldErrors } from '@/lib/forms'

import { AuthShell } from './AuthShell'
import { useLogin, useSession } from './session'

export function LoginPage() {
  const { data: session, isPending } = useSession()
  const login = useLogin()
  const [params] = useSearchParams()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')

  if (!isPending && session) return <Navigate to="/" replace />

  const formError = login.isError
    ? (fieldErrors(login.error).email ?? errorMessage(login.error, 'Unable to sign in.'))
    : null

  function submit(event: FormEvent) {
    event.preventDefault()
    login.mutate({ email, password })
  }

  return (
    <AuthShell title="Sign in" subtitle="Access your company workspace.">
      {params.get('verified') === '1' ? (
        <Alert tone="success">Email verified — you can sign in now.</Alert>
      ) : null}
      {params.get('reset') === '1' ? (
        <Alert tone="success">Password updated. Sign in with your new password.</Alert>
      ) : null}

      <form onSubmit={submit} className="mt-3 flex flex-col gap-4" noValidate>
        {formError ? <Alert>{formError}</Alert> : null}

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

        <Field label="Password" htmlFor="password">
          <Input
            id="password"
            type="password"
            autoComplete="current-password"
            required
            value={password}
            onChange={(e) => setPassword(e.target.value)}
          />
        </Field>

        <Button type="submit" disabled={login.isPending}>
          {login.isPending ? 'Signing in…' : 'Sign in'}
        </Button>
      </form>

      <div className="mt-4 flex justify-between text-sm text-neutral-500 dark:text-neutral-400">
        <Link to="/forgot-password" className="hover:underline">
          Forgot password?
        </Link>
        <Link to="/register" className="hover:underline">
          Create an account
        </Link>
      </div>
    </AuthShell>
  )
}
