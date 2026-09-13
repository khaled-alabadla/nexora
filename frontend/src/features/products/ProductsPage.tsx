import { type FormEvent, useState } from 'react'

import { AppHeader } from '@/components/AppHeader'
import { Alert, Button, Field, Input } from '@/components/ui'
import { usePermissions } from '@/features/auth/session'
import { errorMessage, fieldErrors } from '@/lib/forms'

import {
  useCategories,
  useCreateProduct,
  useDeleteProduct,
  useProducts,
  useUpdateProduct,
} from './hooks'
import type { Product, ProductFilters } from './types'

export function ProductsPage() {
  const permissions = usePermissions()
  const canView = permissions.has('product.view')
  const canCreate = permissions.has('product.create')
  // GET /categories requires category.manage — most roles hold only product.view
  // (see RolesAndPermissionsSeeder), so only fetch it when that's actually granted.
  const canManageCategories = permissions.has('category.manage')

  const [filters, setFilters] = useState<ProductFilters>({ sort: 'name', per_page: 20 })
  const products = useProducts(filters)
  const categories = useCategories(canManageCategories)

  return (
    <main className="mx-auto flex min-h-dvh max-w-4xl flex-col gap-6 px-4 py-8 text-neutral-900 dark:text-neutral-100">
      <AppHeader />
      <h2 className="text-lg font-semibold">Products</h2>

      {!canView ? (
        <p className="text-sm text-neutral-500 dark:text-neutral-400">
          You don't have access to product management.
        </p>
      ) : (
        <>
          {canCreate ? <CreateProductForm categoryOptions={categories.data ?? []} /> : null}

          <Filters
            filters={filters}
            onChange={setFilters}
            categoryOptions={categories.data ?? []}
          />

          {products.isError ? <Alert>{errorMessage(products.error)}</Alert> : null}

          <ul className="divide-y divide-neutral-200 dark:divide-neutral-800">
            {(products.data?.data ?? []).map((product) => (
              <ProductRow
                key={product.id}
                product={product}
                categoryOptions={categories.data ?? []}
              />
            ))}
          </ul>

          {products.data?.data.length === 0 ? (
            <p className="text-sm text-neutral-500 dark:text-neutral-400">No products found.</p>
          ) : null}

          <Pagination filters={filters} onChange={setFilters} meta={products.data?.meta} />
        </>
      )}
    </main>
  )
}

function Filters({
  filters,
  onChange,
  categoryOptions,
}: {
  filters: ProductFilters
  onChange: (f: ProductFilters) => void
  categoryOptions: { id: number; name: string }[]
}) {
  return (
    <div className="flex flex-wrap items-end gap-3">
      <Field label="Search" htmlFor="product-search">
        <Input
          id="product-search"
          placeholder="SKU, name or barcode"
          value={filters.search ?? ''}
          onChange={(e) => onChange({ ...filters, search: e.target.value, page: 1 })}
        />
      </Field>
      <Field label="Status" htmlFor="product-status">
        <select
          id="product-status"
          className="rounded-md border border-neutral-300 px-2 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
          value={filters.status ?? ''}
          onChange={(e) =>
            onChange({
              ...filters,
              status: (e.target.value || undefined) as ProductFilters['status'],
              page: 1,
            })
          }
        >
          <option value="">All</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </Field>
      <Field label="Category" htmlFor="product-category">
        <select
          id="product-category"
          className="rounded-md border border-neutral-300 px-2 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
          value={filters.category_id ?? ''}
          onChange={(e) =>
            onChange({
              ...filters,
              category_id: e.target.value ? Number(e.target.value) : undefined,
              page: 1,
            })
          }
        >
          <option value="">All</option>
          {categoryOptions.map((c) => (
            <option key={c.id} value={c.id}>
              {c.name}
            </option>
          ))}
        </select>
      </Field>
    </div>
  )
}

function Pagination({
  filters,
  onChange,
  meta,
}: {
  filters: ProductFilters
  onChange: (f: ProductFilters) => void
  meta: { current_page: number; last_page: number; total: number } | undefined
}) {
  if (!meta || meta.last_page <= 1) return null

  return (
    <div className="flex items-center justify-between text-sm text-neutral-500 dark:text-neutral-400">
      <span>
        Page {meta.current_page} of {meta.last_page} ({meta.total} total)
      </span>
      <div className="flex gap-2">
        <Button
          variant="ghost"
          disabled={meta.current_page <= 1}
          onClick={() => onChange({ ...filters, page: meta.current_page - 1 })}
        >
          Previous
        </Button>
        <Button
          variant="ghost"
          disabled={meta.current_page >= meta.last_page}
          onClick={() => onChange({ ...filters, page: meta.current_page + 1 })}
        >
          Next
        </Button>
      </div>
    </div>
  )
}

function CreateProductForm({
  categoryOptions,
}: {
  categoryOptions: { id: number; name: string }[]
}) {
  const create = useCreateProduct()
  const [form, setForm] = useState({
    sku: '',
    name: '',
    category_id: '',
    cost_price: '',
    selling_price: '',
  })
  const errors = fieldErrors(create.error)

  function set<K extends keyof typeof form>(key: K, value: string) {
    setForm((prev) => ({ ...prev, [key]: value }))
  }

  function submit(event: FormEvent) {
    event.preventDefault()
    create.mutate(
      {
        sku: form.sku,
        name: form.name,
        category_id: form.category_id ? Number(form.category_id) : null,
        cost_price: form.cost_price ? Number(form.cost_price) : undefined,
        selling_price: form.selling_price ? Number(form.selling_price) : undefined,
      },
      {
        onSuccess: () =>
          setForm({ sku: '', name: '', category_id: '', cost_price: '', selling_price: '' }),
      },
    )
  }

  return (
    <form
      onSubmit={submit}
      className="flex flex-col gap-3 rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900"
    >
      <h3 className="text-sm font-semibold">Add a product</h3>
      {create.isError && Object.keys(errors).length === 0 ? (
        <Alert>{errorMessage(create.error, 'Could not create product.')}</Alert>
      ) : null}
      <div className="flex flex-wrap gap-3">
        <Field label="SKU" htmlFor="new-sku" error={errors.sku}>
          <Input
            id="new-sku"
            required
            value={form.sku}
            onChange={(e) => set('sku', e.target.value)}
          />
        </Field>
        <Field label="Name" htmlFor="new-name" error={errors.name}>
          <Input
            id="new-name"
            required
            value={form.name}
            onChange={(e) => set('name', e.target.value)}
          />
        </Field>
        <Field label="Category" htmlFor="new-category">
          <select
            id="new-category"
            className="rounded-md border border-neutral-300 px-2 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
            value={form.category_id}
            onChange={(e) => set('category_id', e.target.value)}
          >
            <option value="">None</option>
            {categoryOptions.map((c) => (
              <option key={c.id} value={c.id}>
                {c.name}
              </option>
            ))}
          </select>
        </Field>
        <Field label="Cost price" htmlFor="new-cost" error={errors.cost_price}>
          <Input
            id="new-cost"
            type="number"
            step="0.01"
            min="0"
            value={form.cost_price}
            onChange={(e) => set('cost_price', e.target.value)}
          />
        </Field>
        <Field label="Selling price" htmlFor="new-price" error={errors.selling_price}>
          <Input
            id="new-price"
            type="number"
            step="0.01"
            min="0"
            value={form.selling_price}
            onChange={(e) => set('selling_price', e.target.value)}
          />
        </Field>
      </div>
      <Button type="submit" disabled={create.isPending} className="self-start">
        {create.isPending ? 'Adding…' : 'Add product'}
      </Button>
    </form>
  )
}

function ProductRow({
  product,
  categoryOptions,
}: {
  product: Product
  categoryOptions: { id: number; name: string }[]
}) {
  const permissions = usePermissions()
  const canUpdate = permissions.has('product.update')
  const canDelete = permissions.has('product.delete')
  const update = useUpdateProduct()
  const remove = useDeleteProduct()
  const [editing, setEditing] = useState(false)
  const [name, setName] = useState(product.name)
  const [sellingPrice, setSellingPrice] = useState(product.selling_price)
  const [status, setStatus] = useState(product.status)
  const [categoryId, setCategoryId] = useState(product.category?.id.toString() ?? '')

  function save() {
    update.mutate(
      {
        id: product.id,
        input: {
          name,
          selling_price: Number(sellingPrice),
          status,
          category_id: categoryId ? Number(categoryId) : null,
        },
      },
      { onSuccess: () => setEditing(false) },
    )
  }

  return (
    <li className="flex flex-wrap items-center justify-between gap-2 py-2 text-sm">
      <div className="flex flex-col">
        {editing ? (
          <Input value={name} onChange={(e) => setName(e.target.value)} className="max-w-xs" />
        ) : (
          <span className="font-medium">{product.name}</span>
        )}
        <span className="text-neutral-500 dark:text-neutral-400">
          {product.sku} {product.category ? `· ${product.category.name}` : ''}
        </span>
      </div>
      <div className="flex items-center gap-2">
        {editing ? (
          <>
            <Input
              type="number"
              step="0.01"
              value={sellingPrice}
              onChange={(e) => setSellingPrice(e.target.value)}
              className="w-24"
              aria-label="Selling price"
            />
            <select
              aria-label="Status"
              className="rounded-md border border-neutral-300 px-2 py-1 dark:border-neutral-700 dark:bg-neutral-900"
              value={status}
              onChange={(e) => setStatus(e.target.value as Product['status'])}
            >
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
            <select
              aria-label="Category"
              className="rounded-md border border-neutral-300 px-2 py-1 dark:border-neutral-700 dark:bg-neutral-900"
              value={categoryId}
              onChange={(e) => setCategoryId(e.target.value)}
            >
              <option value="">No category</option>
              {categoryOptions.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name}
                </option>
              ))}
            </select>
            <Button variant="ghost" onClick={save} disabled={update.isPending}>
              Save
            </Button>
            <Button variant="ghost" onClick={() => setEditing(false)}>
              Cancel
            </Button>
          </>
        ) : (
          <>
            <span className="rounded bg-neutral-100 px-2 py-0.5 text-xs dark:bg-neutral-800">
              {product.selling_price} · {product.status}
            </span>
            {canUpdate ? (
              <Button variant="ghost" onClick={() => setEditing(true)}>
                Edit
              </Button>
            ) : null}
            {canDelete ? (
              <Button
                variant="danger"
                onClick={() => remove.mutate(product.id)}
                disabled={remove.isPending}
              >
                Delete
              </Button>
            ) : null}
          </>
        )}
      </div>
    </li>
  )
}
