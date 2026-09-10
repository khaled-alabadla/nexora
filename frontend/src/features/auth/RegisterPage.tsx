import { type FormEvent, useState } from 'react'

import { Link, Navigate } from 'react-router-dom'

import { Alert, Button, Field, Input } from '@/components/ui'
import { errorMessage, fieldErrors } from '@/lib/forms'

import { AuthShell } from './AuthShell'
import { useRegister, useSession } from './session'

export function RegisterPage() {
  const { data: session, isPending } = useSession()
  const register = useRegister()
  const [form, setForm] = useState({
    name: '',
    email: '',
    company_name: '',
    password: '',
    password_confirmation: '',
  })

  if (!isPending && session) return <Navigate to="/" replace />

  const errors = fieldErrors(register.error)

  function set<K extends keyof typeof form>(key: K, value: string) {
    setForm((prev) => ({ ...prev, [key]: value }))
  }

  function submit(event: FormEvent) {
    event.preventDefault()
    register.mutate(form)
  }

  return (
    <AuthShell title="Create your account" subtitle="You'll also create your first company.">
      <form onSubmit={submit} className="flex flex-col gap-4" noValidate>
        {register.isError && Object.keys(errors).length === 0 ? (
          <Alert>{errorMessage(register.error, 'Registration failed.')}</Alert>
        ) : null}

        <Field label="Your name" htmlFor="name" error={errors.name}>
          <Input
            id="name"
            required
            value={form.name}
            onChange={(e) => set('name', e.target.value)}
          />
        </Field>
        <Field label="Email" htmlFor="email" error={errors.email}>
          <Input
            id="email"
            type="email"
            autoComplete="email"
            required
            value={form.email}
            onChange={(e) => set('email', e.target.value)}
          />
        </Field>
        <Field label="Company name" htmlFor="company_name" error={errors.company_name}>
          <Input
            id="company_name"
            required
            value={form.company_name}
            onChange={(e) => set('company_name', e.target.value)}
          />
        </Field>
        <Field label="Password" htmlFor="password" error={errors.password}>
          <Input
            id="password"
            type="password"
            autoComplete="new-password"
            required
            value={form.password}
            onChange={(e) => set('password', e.target.value)}
          />
        </Field>
        <Field label="Confirm password" htmlFor="password_confirmation">
          <Input
            id="password_confirmation"
            type="password"
            autoComplete="new-password"
            required
            value={form.password_confirmation}
            onChange={(e) => set('password_confirmation', e.target.value)}
          />
        </Field>

        <Button type="submit" disabled={register.isPending}>
          {register.isPending ? 'Creating…' : 'Create account'}
        </Button>
      </form>

      <p className="mt-4 text-sm text-neutral-500 dark:text-neutral-400">
        Already have an account?{' '}
        <Link to="/login" className="hover:underline">
          Sign in
        </Link>
      </p>
    </AuthShell>
  )
}
