import { afterEach, expect, it, vi } from 'vitest'

import { screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'

import { sessionKey } from '@/features/auth/session'
import { fail, noContent, ok, stubFetch } from '@/test/fetchStub'
import { createTestQueryClient, renderWithProviders } from '@/test/utils'

import { WarehousesPage } from './WarehousesPage'

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

const main = {
  id: 1,
  name: 'Main',
  location: 'Downtown',
  is_default: true,
  status: 'active',
  created_at: null,
}
const secondary = {
  id: 2,
  name: 'Secondary',
  location: null,
  is_default: false,
  status: 'active',
  created_at: null,
}

function renderPage(
  permissions: string[],
  warehouses = [main, secondary],
  routes: Parameters<typeof stubFetch>[0] = [],
) {
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, sessionWith(permissions))
  const mock = stubFetch([
    { method: 'GET', url: '/warehouses', handler: ok(warehouses) },
    ...routes,
  ])
  return { mock, ...renderWithProviders(<WarehousesPage />, { queryClient: qc }) }
}

it('shows an access message without warehouse.view', () => {
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, sessionWith([]))
  stubFetch([])
  renderWithProviders(<WarehousesPage />, { queryClient: qc })
  expect(screen.getByText(/don't have access/)).toBeInTheDocument()
})

it('lists warehouses and marks the default', async () => {
  renderPage(['warehouse.view'])
  expect(await screen.findByText('Main')).toBeInTheDocument()
  expect(screen.getByText('Default')).toBeInTheDocument()
  expect(screen.getByText('Secondary')).toBeInTheDocument()
  expect(screen.queryByRole('button', { name: 'Add warehouse' })).not.toBeInTheDocument()
})

it('hints that the first warehouse becomes the default when the company has none', async () => {
  renderPage(['warehouse.view', 'warehouse.create'], [])
  expect(await screen.findByText(/becomes the default/)).toBeInTheDocument()
  expect(screen.getByText(/This will be the default warehouse\./)).toBeInTheDocument()
  expect(screen.queryByLabelText('Make default')).not.toBeInTheDocument()
})

it('creates a warehouse with an explicit default checkbox once one exists', async () => {
  const user = userEvent.setup({ delay: null })
  const { mock } = renderPage(['warehouse.view', 'warehouse.create'])
  await screen.findByText('Main')

  await user.type(screen.getByLabelText('Name'), 'Third')
  await user.click(screen.getByLabelText('Make default'))
  await user.click(screen.getByRole('button', { name: 'Add warehouse' }))

  await vi.waitFor(() => {
    const call = mock.mock.calls.find(
      (c) => c[0].includes('/warehouses') && c[1]?.method === 'POST',
    )
    expect(call).toBeDefined()
    expect(JSON.parse(call?.[1]?.body as string)).toMatchObject({ name: 'Third', is_default: true })
  })
})

it('surfaces a validation error on create', async () => {
  const user = userEvent.setup({ delay: null })
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, sessionWith(['warehouse.view', 'warehouse.create']))
  stubFetch([
    { method: 'GET', url: '/warehouses', handler: ok([main]) },
    {
      method: 'POST',
      url: '/warehouses',
      handler: fail(422, 'The name has already been taken.', {
        name: ['The name has already been taken.'],
      }),
    },
  ])
  renderWithProviders(<WarehousesPage />, { queryClient: qc })

  await user.type(await screen.findByLabelText('Name'), 'Main')
  await user.click(screen.getByRole('button', { name: 'Add warehouse' }))

  expect(await screen.findByRole('alert')).toHaveTextContent('The name has already been taken.')
})

it('sets a non-default warehouse as default', async () => {
  const user = userEvent.setup({ delay: null })
  const { mock } = renderPage(
    ['warehouse.view', 'warehouse.update'],
    [main, secondary],
    [{ method: 'PUT', url: '/warehouses/2', handler: ok({ ...secondary, is_default: true }) }],
  )
  await screen.findByText('Secondary')

  const row = screen.getByText('Secondary').closest('li') as HTMLElement
  await user.click(within(row).getByRole('button', { name: 'Set default' }))

  await vi.waitFor(() => {
    const call = mock.mock.calls.find(
      (c) => c[0].includes('/warehouses/2') && c[1]?.method === 'PUT',
    )
    expect(call).toBeDefined()
    expect(JSON.parse(call?.[1]?.body as string)).toEqual({ is_default: true })
  })
})

it('does not offer "Set default" on the current default', async () => {
  renderPage(['warehouse.view', 'warehouse.update'])
  const row = (await screen.findByText('Main')).closest('li') as HTMLElement
  expect(within(row).queryByRole('button', { name: 'Set default' })).not.toBeInTheDocument()
})

it('surfaces a rename failure from the server', async () => {
  // The "must keep a default warehouse" invariant (backend WarehouseService,
  // covered by its own Pest suite) has no UI trigger — this page never sends
  // is_default: false. What the Edit/Save path *can* fail on is a duplicate
  // name, which exercises the same error-surfacing code.
  const user = userEvent.setup({ delay: null })
  const qc = createTestQueryClient()
  qc.setQueryData(sessionKey, sessionWith(['warehouse.view', 'warehouse.update']))
  stubFetch([
    { method: 'GET', url: '/warehouses', handler: ok([main, secondary]) },
    { method: 'PUT', url: '/warehouses/1', handler: fail(422, 'The name has already been taken.') },
  ])
  renderWithProviders(<WarehousesPage />, { queryClient: qc })

  const row = (await screen.findByText('Main')).closest('li') as HTMLElement
  await user.click(within(row).getByRole('button', { name: 'Edit' }))
  await user.click(within(row).getByRole('button', { name: 'Save' }))

  // WarehouseRow's rename form has no per-field error slot — update failures
  // surface through the blanket <Alert> (role="status"), unlike the create
  // form's <Field error=…> (role="alert").
  expect(await screen.findByRole('status')).toHaveTextContent('The name has already been taken.')
})

it('edits and deletes a warehouse', async () => {
  const user = userEvent.setup({ delay: null })
  const { mock } = renderPage(
    ['warehouse.view', 'warehouse.update', 'warehouse.delete'],
    [main, secondary],
    [
      { method: 'PUT', url: '/warehouses/2', handler: ok(secondary) },
      { method: 'DELETE', url: '/warehouses/2', handler: noContent() },
    ],
  )
  const row = (await screen.findByText('Secondary')).closest('li') as HTMLElement

  await user.click(within(row).getByRole('button', { name: 'Edit' }))
  await user.click(within(row).getByRole('button', { name: 'Save' }))
  await within(row).findByRole('button', { name: 'Edit' })

  await user.click(within(row).getByRole('button', { name: 'Delete' }))

  await vi.waitFor(() => {
    expect(
      mock.mock.calls.some((c) => c[0].includes('/warehouses/2') && c[1]?.method === 'DELETE'),
    ).toBe(true)
  })
})
