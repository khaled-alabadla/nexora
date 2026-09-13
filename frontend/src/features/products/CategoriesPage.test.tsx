import { afterEach, expect, it, vi } from 'vitest'

import { screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'

import { sessionKey } from '@/features/auth/session'
import { created, noContent, ok, stubFetch } from '@/test/fetchStub'
import { createTestQueryClient, renderWithProviders } from '@/test/utils'

import { CategoriesPage } from './CategoriesPage'

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

const categories = [
  { id: 1, name: 'Electronics', parent_id: null, status: 'active', created_at: null },
  { id: 2, name: 'Phones', parent_id: 1, status: 'active', created_at: null },
]

it('shows an access message without category.manage', () => {
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, sessionWith([]))
  stubFetch([])

  renderWithProviders(<CategoriesPage />, { queryClient: qc })

  expect(screen.getByText(/don't have access/)).toBeInTheDocument()
})

it('lists categories and shows parent names', async () => {
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, sessionWith(['category.manage']))
  stubFetch([{ method: 'GET', url: '/categories', handler: ok(categories) }])

  renderWithProviders(<CategoriesPage />, { queryClient: qc })

  const list = await screen.findByRole('list')
  expect(await within(list).findByText('Phones')).toBeInTheDocument()
  expect(within(list).getByText('under Electronics')).toBeInTheDocument()
})

it('creates a category', async () => {
  const user = userEvent.setup({ delay: null })
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, sessionWith(['category.manage']))
  const mock = stubFetch([
    { method: 'GET', url: '/categories', handler: ok(categories) },
    {
      method: 'POST',
      url: '/categories',
      handler: created({
        id: 3,
        name: 'Laptops',
        parent_id: null,
        status: 'active',
        created_at: null,
      }),
    },
  ])

  renderWithProviders(<CategoriesPage />, { queryClient: qc })
  await screen.findByRole('list')

  await user.type(screen.getByLabelText('New category'), 'Laptops')
  await user.click(screen.getByRole('button', { name: 'Add category' }))

  await vi.waitFor(() => expect(screen.getByLabelText('New category')).toHaveValue(''))
  expect(mock.mock.calls.some((c) => c[0].includes('/categories') && c[1]?.method === 'POST')).toBe(
    true,
  )
})

it('renames a category', async () => {
  const user = userEvent.setup({ delay: null })
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, sessionWith(['category.manage']))
  const mock = stubFetch([
    { method: 'GET', url: '/categories', handler: ok([categories[0]]) },
    {
      method: 'PUT',
      url: '/categories/1',
      handler: ok({ id: 1, name: 'Renamed', parent_id: null, status: 'active', created_at: null }),
    },
  ])

  renderWithProviders(<CategoriesPage />, { queryClient: qc })

  const list = await screen.findByRole('list')
  const row = (await within(list).findByText('Electronics')).closest('li') as HTMLElement
  await user.click(within(row).getByRole('button', { name: 'Rename' }))
  const input = row.querySelector('input')!
  await user.clear(input)
  await user.type(input, 'Renamed')
  await user.click(within(row).getByRole('button', { name: 'Save' }))

  await vi.waitFor(() =>
    expect(
      mock.mock.calls.some((c) => c[0].includes('/categories/1') && c[1]?.method === 'PUT'),
    ).toBe(true),
  )
})

it('deletes a category', async () => {
  const user = userEvent.setup({ delay: null })
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, sessionWith(['category.manage']))
  const mock = stubFetch([
    { method: 'GET', url: '/categories', handler: ok([categories[0]]) },
    { method: 'DELETE', url: '/categories/1', handler: noContent() },
  ])

  renderWithProviders(<CategoriesPage />, { queryClient: qc })

  const list = await screen.findByRole('list')
  await user.click(await within(list).findByRole('button', { name: 'Delete' }))

  await vi.waitFor(() =>
    expect(
      mock.mock.calls.some((c) => c[0].includes('/categories/1') && c[1]?.method === 'DELETE'),
    ).toBe(true),
  )
})
