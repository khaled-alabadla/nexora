import { type FormEvent, useState } from 'react'

import { Alert, Button, Field, Input } from '@/components/ui'
import { errorMessage, fieldErrors } from '@/lib/forms'

import { useCreateCompany } from './hooks'

export function CreateCompanyCard() {
  const create = useCreateCompany()
  const [name, setName] = useState('')
  const errors = fieldErrors(create.error)

  function submit(event: FormEvent) {
    event.preventDefault()
    create.mutate(name, { onSuccess: () => setName('') })
  }

  return (
    <form
      onSubmit={submit}
      className="flex flex-col gap-3 rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900"
    >
      <h3 className="text-sm font-semibold">Create a company</h3>
      {create.isError && Object.keys(errors).length === 0 ? (
        <Alert>{errorMessage(create.error, 'Could not create company.')}</Alert>
      ) : null}
      <Field label="Company name" htmlFor="new-company" error={errors.name}>
        <Input id="new-company" required value={name} onChange={(e) => setName(e.target.value)} />
      </Field>
      <Button type="submit" disabled={create.isPending}>
        {create.isPending ? 'Creating…' : 'Create company'}
      </Button>
    </form>
  )
}
