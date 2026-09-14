import { afterEach, expect, it, vi } from 'vitest'

import { screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'

import { sessionKey } from '@/features/auth/session'
import { page, stubFetch } from '@/test/fetchStub'
import { createTestQueryClient, renderWithProviders } from '@/test/utils'

import { InventoryPage } from './InventoryPage'

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

const stockRow = {
  id: 1,
  product: { id: 1, sku: 'A1', name: 'Widget' },
  warehouse: { id: 1, name: 'Main' },
  quantity: '42.0000',
  updated_at: null,
}

const movement = {
  id: 1,
  product: { id: 2, sku: 'B2', name: 'Gadget' },
  warehouse: { id: 1, name: 'Main' },
  type: 'purchase',
  quantity: '10.0000',
  unit_cost: '5.0000',
  reference_type: null,
  reference_id: null,
  note: null,
  created_by: 1,
  created_at: null,
}

function renderPage(permissions: string[], routes: Parameters<typeof stubFetch>[0] = []) {
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, sessionWith(permissions))
  const mock = stubFetch([
    { method: 'GET', url: '/inventory/stock', handler: page([stockRow]) },
    { method: 'GET', url: '/inventory/movements', handler: page([movement]) },
    ...routes,
  ])
  return { mock, ...renderWithProviders(<InventoryPage />, { queryClient: qc }) }
}

it('shows an access message without inventory.view, and never requests stock or movements', async () => {
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, sessionWith([]))
  const mock = stubFetch([])
  renderWithProviders(<InventoryPage />, { queryClient: qc })
  expect(screen.getByText(/don't have access/)).toBeInTheDocument()

  // Give any wrongly-enabled query a tick to fire before asserting it didn't.
  await new Promise((resolve) => setTimeout(resolve, 0))
  expect(mock).not.toHaveBeenCalled()
})

it('lists stock and movements', async () => {
  renderPage(['inventory.view'])

  expect(await screen.findByText('42.0000')).toBeInTheDocument()
  expect(screen.getByText('Widget')).toBeInTheDocument()

  const movementRow = screen.getByText('Gadget').closest('li') as HTMLElement
  expect(within(movementRow).getByText('10.0000')).toBeInTheDocument()
  expect(within(movementRow).getByText(/purchase/)).toBeInTheDocument()
})

it('shows empty-state copy when there is no stock or movement history yet', async () => {
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, sessionWith(['inventory.view']))
  stubFetch([
    { method: 'GET', url: '/inventory/stock', handler: page([]) },
    { method: 'GET', url: '/inventory/movements', handler: page([]) },
  ])
  renderWithProviders(<InventoryPage />, { queryClient: qc })

  expect(await screen.findByText('No stock recorded yet.')).toBeInTheDocument()
  expect(screen.getByText('No movements recorded yet.')).toBeInTheDocument()
})

it('filters movements by type', async () => {
  const user = userEvent.setup({ delay: null })
  const { mock } = renderPage(['inventory.view'])
  await screen.findByText('Widget')

  await user.selectOptions(screen.getByLabelText('Type'), 'sale')

  await vi.waitFor(() => {
    expect(mock.mock.calls.some((c) => String(c[0]).includes('type=sale'))).toBe(true)
  })
})

it('paginates the stock list independently from the movements list', async () => {
  const user = userEvent.setup({ delay: null })
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, sessionWith(['inventory.view']))
  const mock = stubFetch([
    {
      method: 'GET',
      url: '/inventory/stock',
      handler: page([stockRow], { current_page: 1, last_page: 2, total: 2 }),
    },
    { method: 'GET', url: '/inventory/movements', handler: page([movement]) },
  ])
  renderWithProviders(<InventoryPage />, { queryClient: qc })

  await screen.findByText('42.0000')
  expect(screen.getByText('Page 1 of 2 (2 total)')).toBeInTheDocument()

  const movementsCallsBefore = mock.mock.calls.filter((c) =>
    String(c[0]).includes('/inventory/movements'),
  ).length

  const [stockNext] = screen.getAllByRole('button', { name: 'Next' })
  await user.click(stockNext!)

  await vi.waitFor(() => {
    expect(
      mock.mock.calls.some(
        (c) => String(c[0]).includes('/inventory/stock') && String(c[0]).includes('page=2'),
      ),
    ).toBe(true)
  })

  const movementsCallsAfter = mock.mock.calls.filter((c) =>
    String(c[0]).includes('/inventory/movements'),
  ).length
  expect(movementsCallsAfter).toBe(movementsCallsBefore)
})

it('surfaces a fetch error for stock', async () => {
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, sessionWith(['inventory.view']))
  stubFetch([
    {
      method: 'GET',
      url: '/inventory/stock',
      handler: () => {
        throw new Error('network down')
      },
    },
    { method: 'GET', url: '/inventory/movements', handler: page([]) },
  ])
  renderWithProviders(<InventoryPage />, { queryClient: qc })

  expect(await screen.findByRole('status')).toBeInTheDocument()
})
