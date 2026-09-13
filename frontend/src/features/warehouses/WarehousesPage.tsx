import { type FormEvent, useState } from 'react'

import { AppHeader } from '@/components/AppHeader'
import { Alert, Button, Field, Input } from '@/components/ui'
import { usePermissions } from '@/features/auth/session'
import { errorMessage, fieldErrors } from '@/lib/forms'

import { useCreateWarehouse, useDeleteWarehouse, useUpdateWarehouse, useWarehouses } from './hooks'
import type { Warehouse } from './types'

export function WarehousesPage() {
  const permissions = usePermissions()
  const canView = permissions.has('warehouse.view')
  const canCreate = permissions.has('warehouse.create')
  const warehouses = useWarehouses(canView)

  return (
    <main className="mx-auto flex min-h-dvh max-w-3xl flex-col gap-6 px-4 py-8 text-neutral-900 dark:text-neutral-100">
      <AppHeader />
      <h2 className="text-lg font-semibold">Warehouses</h2>

      {!canView ? (
        <p className="text-sm text-neutral-500 dark:text-neutral-400">
          You don't have access to warehouse management.
        </p>
      ) : (
        <>
          {canCreate ? (
            <CreateWarehouseForm hasWarehouses={(warehouses.data ?? []).length > 0} />
          ) : null}

          {warehouses.isError ? <Alert>{errorMessage(warehouses.error)}</Alert> : null}

          <ul className="divide-y divide-neutral-200 dark:divide-neutral-800">
            {(warehouses.data ?? []).map((warehouse) => (
              <WarehouseRow key={warehouse.id} warehouse={warehouse} />
            ))}
          </ul>

          {warehouses.data?.length === 0 ? (
            <p className="text-sm text-neutral-500 dark:text-neutral-400">
              No warehouses yet — the first one you add becomes the default.
            </p>
          ) : null}
        </>
      )}
    </main>
  )
}

function CreateWarehouseForm({ hasWarehouses }: { hasWarehouses: boolean }) {
  const create = useCreateWarehouse()
  const [name, setName] = useState('')
  const [location, setLocation] = useState('')
  const [isDefault, setIsDefault] = useState(false)
  const errors = fieldErrors(create.error)

  function submit(event: FormEvent) {
    event.preventDefault()
    create.mutate(
      { name, location: location || null, is_default: isDefault },
      {
        onSuccess: () => {
          setName('')
          setLocation('')
          setIsDefault(false)
        },
      },
    )
  }

  return (
    <form
      onSubmit={submit}
      className="flex flex-col gap-3 rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900"
    >
      <h3 className="text-sm font-semibold">Add a warehouse</h3>
      {create.isError && Object.keys(errors).length === 0 ? (
        <Alert>{errorMessage(create.error, 'Could not create warehouse.')}</Alert>
      ) : null}
      <div className="flex flex-wrap items-end gap-3">
        <Field label="Name" htmlFor="new-warehouse-name" error={errors.name}>
          <Input
            id="new-warehouse-name"
            required
            value={name}
            onChange={(e) => setName(e.target.value)}
          />
        </Field>
        <Field label="Location (optional)" htmlFor="new-warehouse-location">
          <Input
            id="new-warehouse-location"
            value={location}
            onChange={(e) => setLocation(e.target.value)}
          />
        </Field>
        {hasWarehouses ? (
          <label className="flex items-center gap-2 pb-2 text-sm">
            <input
              type="checkbox"
              checked={isDefault}
              onChange={(e) => setIsDefault(e.target.checked)}
            />
            Make default
          </label>
        ) : (
          <p className="pb-2 text-sm text-neutral-500 dark:text-neutral-400">
            This will be the default warehouse.
          </p>
        )}
      </div>
      <Button type="submit" disabled={create.isPending} className="self-start">
        {create.isPending ? 'Adding…' : 'Add warehouse'}
      </Button>
    </form>
  )
}

function WarehouseRow({ warehouse }: { warehouse: Warehouse }) {
  const permissions = usePermissions()
  const canUpdate = permissions.has('warehouse.update')
  const canDelete = permissions.has('warehouse.delete')
  const update = useUpdateWarehouse()
  const remove = useDeleteWarehouse()
  const [editing, setEditing] = useState(false)
  const [name, setName] = useState(warehouse.name)
  const [location, setLocation] = useState(warehouse.location ?? '')

  function save() {
    update.mutate(
      { id: warehouse.id, input: { name, location: location || null } },
      { onSuccess: () => setEditing(false) },
    )
  }

  return (
    <li className="flex flex-wrap items-center justify-between gap-2 py-2 text-sm">
      <div className="flex flex-col">
        {editing ? (
          <div className="flex gap-2">
            <Input value={name} onChange={(e) => setName(e.target.value)} className="max-w-xs" />
            <Input
              value={location}
              onChange={(e) => setLocation(e.target.value)}
              placeholder="Location"
              className="max-w-xs"
            />
          </div>
        ) : (
          <span className="font-medium">
            {warehouse.name}
            {warehouse.is_default ? (
              <span className="ml-2 rounded bg-neutral-900 px-1.5 py-0.5 text-xs text-white dark:bg-white dark:text-neutral-900">
                Default
              </span>
            ) : null}
          </span>
        )}
        <span className="text-neutral-500 dark:text-neutral-400">
          {warehouse.location ?? 'No location set'} · {warehouse.status}
        </span>
      </div>
      <div className="flex items-center gap-2">
        {editing ? (
          <>
            <Button variant="ghost" onClick={save} disabled={update.isPending}>
              Save
            </Button>
            <Button variant="ghost" onClick={() => setEditing(false)}>
              Cancel
            </Button>
          </>
        ) : (
          <>
            {canUpdate && !warehouse.is_default ? (
              <Button
                variant="ghost"
                onClick={() => update.mutate({ id: warehouse.id, input: { is_default: true } })}
                disabled={update.isPending}
              >
                Set default
              </Button>
            ) : null}
            {canUpdate ? (
              <Button variant="ghost" onClick={() => setEditing(true)}>
                Edit
              </Button>
            ) : null}
            {canDelete ? (
              <Button
                variant="danger"
                onClick={() => remove.mutate(warehouse.id)}
                disabled={remove.isPending}
              >
                Delete
              </Button>
            ) : null}
          </>
        )}
      </div>
      {update.isError ? <Alert>{errorMessage(update.error)}</Alert> : null}
      {remove.isError ? <Alert>{errorMessage(remove.error)}</Alert> : null}
    </li>
  )
}
