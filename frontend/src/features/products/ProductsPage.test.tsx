import { afterEach, expect, it, vi } from 'vitest'

import { screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'

import { sessionKey } from '@/features/auth/session'
import { created, fail, noContent, ok, page, stubFetch } from '@/test/fetchStub'
import { createTestQueryClient, renderWithProviders } from '@/test/utils'

import { ProductsPage } from './ProductsPage'

afterEach(() => vi.unstubAllGlobals())

const role = { slug: 'owner', name: 'Owner', level: 100 }

function sessionWith(permissions: string[]) {
  return {
    user: { id: 1, name: 'Ada', email: 'ada@example.com', email_verified: true },
    current_company: { id: 1, name: 'Alpha', slug: 'alpha', status: 'active', role },
    companies: [{ id: 1, name: 'Alpha', slug: 'alpha', status: 'active', role }],
    permissions,
  }
}

const categoryOption = {
  id: 5,
  name: 'Electronics',
  parent_id: null,
  status: 'active',
  created_at: null,
}

const widget = {
  id: 1,
  category: categoryOption,
  sku: 'A1',
  name: 'Widget',
  description: null,
  barcode: null,
  unit: 'pcs',
  cost_price: '1.0000',
  selling_price: '19.9900',
  tax_rate: '0.0000',
  minimum_stock: '0.0000',
  status: 'active',
  created_at: null,
}

function renderPage(permissions: string[], routes: Parameters<typeof stubFetch>[0] = []) {
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, sessionWith(permissions))
  const mock = stubFetch([
    { method: 'GET', url: '/categories', handler: ok([categoryOption]) },
    { method: 'GET', url: '/products', handler: page([widget]) },
    ...routes,
  ])
  return { mock, ...renderWithProviders(<ProductsPage />, { queryClient: qc }) }
}

it('shows an access message without product.view', () => {
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, sessionWith([]))
  stubFetch([])
  renderWithProviders(<ProductsPage />, { queryClient: qc })
  expect(screen.getByText(/don't have access/)).toBeInTheDocument()
})

it('lists products and hides the create form for a view-only role', async () => {
  renderPage(['product.view'])
  expect(await screen.findByText('Widget')).toBeInTheDocument()
  expect(screen.getByText(/A1/)).toBeInTheDocument()
  expect(screen.queryByRole('button', { name: 'Add product' })).not.toBeInTheDocument()
  expect(screen.queryByRole('button', { name: 'Edit' })).not.toBeInTheDocument()
})

it('never calls GET /categories for a role without category.manage', async () => {
  // The backend 403s that endpoint for most roles (product.view alone does not
  // grant it) — the page must not fire a request it knows will fail.
  const { mock } = renderPage(['product.view'])
  await screen.findByText('Widget')

  expect(mock.mock.calls.some((c) => c[0].includes('/categories'))).toBe(false)
})

it('calls GET /categories when the role holds category.manage', async () => {
  const { mock } = renderPage(['product.view', 'category.manage'])
  await screen.findByText('Widget')

  await vi.waitFor(() => {
    expect(mock.mock.calls.some((c) => c[0].includes('/categories'))).toBe(true)
  })
})

it('creates a product', async () => {
  const user = userEvent.setup({ delay: null })
  renderPage(
    ['product.view', 'product.create'],
    [
      {
        method: 'POST',
        url: '/products',
        handler: created({ ...widget, id: 2, sku: 'B2', name: 'Gadget' }),
      },
    ],
  )

  await user.type(await screen.findByLabelText('SKU'), 'B2')
  await user.type(screen.getByLabelText('Name'), 'Gadget')
  await user.click(screen.getByRole('button', { name: 'Add product' }))

  await vi.waitFor(() => expect(screen.getByLabelText('SKU')).toHaveValue(''))
})

it('surfaces a validation error on create', async () => {
  const user = userEvent.setup({ delay: null })
  renderPage(
    ['product.view', 'product.create'],
    [
      {
        method: 'POST',
        url: '/products',
        handler: fail(422, 'The sku has already been taken.', {
          sku: ['The sku has already been taken.'],
        }),
      },
    ],
  )

  await user.type(await screen.findByLabelText('SKU'), 'DUP')
  await user.type(screen.getByLabelText('Name'), 'X')
  await user.click(screen.getByRole('button', { name: 'Add product' }))

  expect(await screen.findByRole('alert')).toHaveTextContent('The sku has already been taken.')
})

it('edits and deletes a product', async () => {
  const user = userEvent.setup({ delay: null })
  renderPage(
    ['product.view', 'product.update', 'product.delete'],
    [
      { method: 'PUT', url: '/products/1', handler: ok({ ...widget, name: 'Renamed' }) },
      { method: 'DELETE', url: '/products/1', handler: noContent() },
    ],
  )

  const row = (await screen.findByText('Widget')).closest('li') as HTMLElement
  await user.click(within(row).getByRole('button', { name: 'Edit' }))
  await user.click(within(row).getByRole('button', { name: 'Save' }))
  await screen.findByRole('button', { name: 'Edit' })

  await user.click(screen.getByRole('button', { name: 'Delete' }))
})

it('filters by search', async () => {
  const user = userEvent.setup({ delay: null })
  const mock = stubFetch([
    { method: 'GET', url: '/categories', handler: ok([categoryOption]) },
    { method: 'GET', url: '/products', handler: page([widget]) },
  ])
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, sessionWith(['product.view']))
  renderWithProviders(<ProductsPage />, { queryClient: qc })

  await screen.findByText('Widget')
  await user.type(screen.getByLabelText('Search'), 'wid')

  await vi.waitFor(() => {
    expect(mock.mock.calls.some((c) => String(c[0]).includes('search=wid'))).toBe(true)
  })
})

it('filters by status and category', async () => {
  const user = userEvent.setup({ delay: null })
  // category.manage so the category dropdown is actually populated (§ the
  // "never calls GET /categories" test above covers the product.view-only case).
  const { mock } = renderPage(['product.view', 'category.manage'])
  await screen.findByText('Widget')

  await user.selectOptions(screen.getByLabelText('Status'), 'inactive')
  await vi.waitFor(() => {
    expect(mock.mock.calls.some((c) => c[0].includes('status=inactive'))).toBe(true)
  })

  await screen.findByRole('option', { name: categoryOption.name })
  await user.selectOptions(screen.getByLabelText('Category'), String(categoryOption.id))
  await vi.waitFor(() => {
    expect(mock.mock.calls.some((c) => c[0].includes(`category_id=${categoryOption.id}`))).toBe(
      true,
    )
  })
})

it('paginates between pages', async () => {
  const user = userEvent.setup({ delay: null })
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, sessionWith(['product.view']))
  const mock = stubFetch([
    { method: 'GET', url: '/categories', handler: ok([categoryOption]) },
    {
      method: 'GET',
      url: '/products',
      handler: page([widget], { current_page: 1, last_page: 2, total: 2 }),
    },
  ])
  renderWithProviders(<ProductsPage />, { queryClient: qc })

  await screen.findByText('Widget')
  expect(screen.getByText('Page 1 of 2 (2 total)')).toBeInTheDocument()
  expect(screen.getByRole('button', { name: 'Previous' })).toBeDisabled()

  await user.click(screen.getByRole('button', { name: 'Next' }))

  await vi.waitFor(() => {
    expect(mock.mock.calls.some((c) => c[0].includes('page=2'))).toBe(true)
  })
})
