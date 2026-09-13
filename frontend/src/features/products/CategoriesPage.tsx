import { type FormEvent, useState } from 'react'

import { AppHeader } from '@/components/AppHeader'
import { Alert, Button, Field, Input } from '@/components/ui'
import { usePermissions } from '@/features/auth/session'
import { errorMessage, fieldErrors } from '@/lib/forms'

import { useCategories, useCreateCategory, useDeleteCategory, useUpdateCategory } from './hooks'
import type { ProductCategory } from './types'

export function CategoriesPage() {
  const permissions = usePermissions()
  const canManage = permissions.has('category.manage')
  const categories = useCategories(canManage)

  return (
    <main className="mx-auto flex min-h-dvh max-w-3xl flex-col gap-6 px-4 py-8 text-neutral-900 dark:text-neutral-100">
      <AppHeader />
      <h2 className="text-lg font-semibold">Categories</h2>

      {!canManage ? (
        <p className="text-sm text-neutral-500 dark:text-neutral-400">
          You don't have access to category management.
        </p>
      ) : (
        <>
          <CreateCategoryForm categories={categories.data ?? []} />
          <ul className="divide-y divide-neutral-200 dark:divide-neutral-800">
            {(categories.data ?? []).map((category) => (
              <CategoryRow
                key={category.id}
                category={category}
                categories={categories.data ?? []}
              />
            ))}
          </ul>
        </>
      )}
    </main>
  )
}

function CreateCategoryForm({ categories }: { categories: ProductCategory[] }) {
  const create = useCreateCategory()
  const [name, setName] = useState('')
  const [parentId, setParentId] = useState('')
  const errors = fieldErrors(create.error)

  function submit(event: FormEvent) {
    event.preventDefault()
    create.mutate(
      { name, parent_id: parentId ? Number(parentId) : null },
      { onSuccess: () => setName('') },
    )
  }

  return (
    <form onSubmit={submit} className="flex flex-wrap items-end gap-3">
      {create.isError && Object.keys(errors).length === 0 ? (
        <Alert>{errorMessage(create.error, 'Could not create category.')}</Alert>
      ) : null}
      <Field label="New category" htmlFor="category-name" error={errors.name}>
        <Input id="category-name" required value={name} onChange={(e) => setName(e.target.value)} />
      </Field>
      <Field label="Parent (optional)" htmlFor="category-parent">
        <select
          id="category-parent"
          className="rounded-md border border-neutral-300 px-2 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
          value={parentId}
          onChange={(e) => setParentId(e.target.value)}
        >
          <option value="">None</option>
          {categories.map((c) => (
            <option key={c.id} value={c.id}>
              {c.name}
            </option>
          ))}
        </select>
      </Field>
      <Button type="submit" disabled={create.isPending}>
        {create.isPending ? 'Adding…' : 'Add category'}
      </Button>
    </form>
  )
}

function CategoryRow({
  category,
  categories,
}: {
  category: ProductCategory
  categories: ProductCategory[]
}) {
  const update = useUpdateCategory()
  const remove = useDeleteCategory()
  const [editing, setEditing] = useState(false)
  const [name, setName] = useState(category.name)

  function save() {
    update.mutate({ id: category.id, input: { name } }, { onSuccess: () => setEditing(false) })
  }

  const parentName = categories.find((c) => c.id === category.parent_id)?.name

  return (
    <li className="flex flex-wrap items-center justify-between gap-2 py-2 text-sm">
      {editing ? (
        <Input value={name} onChange={(e) => setName(e.target.value)} className="max-w-xs" />
      ) : (
        <span>
          {category.name}
          {parentName ? (
            <span className="ml-2 text-neutral-500 dark:text-neutral-400">under {parentName}</span>
          ) : null}
        </span>
      )}
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
            <Button variant="ghost" onClick={() => setEditing(true)}>
              Rename
            </Button>
            <Button
              variant="danger"
              onClick={() => remove.mutate(category.id)}
              disabled={remove.isPending}
            >
              Delete
            </Button>
          </>
        )}
      </div>
      {remove.isError ? <Alert>{errorMessage(remove.error)}</Alert> : null}
    </li>
  )
}
