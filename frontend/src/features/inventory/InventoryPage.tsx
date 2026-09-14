import { useState } from 'react'

import { AppHeader } from '@/components/AppHeader'
import { Alert, Button, Field } from '@/components/ui'
import { usePermissions } from '@/features/auth/session'
import { errorMessage } from '@/lib/forms'

import { useMovements, useStock } from './hooks'
import type { InventoryMovement, MovementFilters, MovementType, Stock, StockFilters } from './types'

const MOVEMENT_TYPES: MovementType[] = [
  'purchase',
  'sale',
  'return',
  'adjustment',
  'transfer_in',
  'transfer_out',
  'damage',
]

export function InventoryPage() {
  const permissions = usePermissions()
  const canView = permissions.has('inventory.view')

  const [stockFilters, setStockFilters] = useState<StockFilters>({ per_page: 20 })
  const [movementFilters, setMovementFilters] = useState<MovementFilters>({ per_page: 20 })
  const stock = useStock(stockFilters)
  const movements = useMovements(movementFilters)

  return (
    <main className="mx-auto flex min-h-dvh max-w-4xl flex-col gap-8 px-4 py-8 text-neutral-900 dark:text-neutral-100">
      <AppHeader />
      <h2 className="text-lg font-semibold">Inventory</h2>

      {!canView ? (
        <p className="text-sm text-neutral-500 dark:text-neutral-400">
          You don't have access to inventory.
        </p>
      ) : (
        <>
          <section className="flex flex-col gap-3">
            <h3 className="text-sm font-semibold">Stock</h3>

            {stock.isError ? <Alert>{errorMessage(stock.error)}</Alert> : null}

            <ul className="divide-y divide-neutral-200 dark:divide-neutral-800">
              {(stock.data?.data ?? []).map((row) => (
                <StockRow key={row.id} stock={row} />
              ))}
            </ul>

            {stock.data?.data.length === 0 ? (
              <p className="text-sm text-neutral-500 dark:text-neutral-400">
                No stock recorded yet.
              </p>
            ) : null}

            <Pagination
              meta={stock.data?.meta}
              onPage={(page) => setStockFilters({ ...stockFilters, page })}
            />
          </section>

          <section className="flex flex-col gap-3">
            <h3 className="text-sm font-semibold">Movements</h3>

            <Field label="Type" htmlFor="movement-type">
              <select
                id="movement-type"
                className="rounded-md border border-neutral-300 px-2 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                value={movementFilters.type ?? ''}
                onChange={(e) =>
                  setMovementFilters({
                    ...movementFilters,
                    type: (e.target.value || undefined) as MovementFilters['type'],
                    page: 1,
                  })
                }
              >
                <option value="">All</option>
                {MOVEMENT_TYPES.map((type) => (
                  <option key={type} value={type}>
                    {type}
                  </option>
                ))}
              </select>
            </Field>

            {movements.isError ? <Alert>{errorMessage(movements.error)}</Alert> : null}

            <ul className="divide-y divide-neutral-200 dark:divide-neutral-800">
              {(movements.data?.data ?? []).map((movement) => (
                <MovementRow key={movement.id} movement={movement} />
              ))}
            </ul>

            {movements.data?.data.length === 0 ? (
              <p className="text-sm text-neutral-500 dark:text-neutral-400">
                No movements recorded yet.
              </p>
            ) : null}

            <Pagination
              meta={movements.data?.meta}
              onPage={(page) => setMovementFilters({ ...movementFilters, page })}
            />
          </section>
        </>
      )}
    </main>
  )
}

function StockRow({ stock }: { stock: Stock }) {
  return (
    <li className="flex flex-wrap items-center justify-between gap-2 py-2 text-sm">
      <div className="flex flex-col">
        <span className="font-medium">{stock.product.name}</span>
        <span className="text-neutral-500 dark:text-neutral-400">
          {stock.product.sku} · {stock.warehouse.name}
        </span>
      </div>
      <span className="rounded bg-neutral-100 px-2 py-0.5 text-xs font-medium dark:bg-neutral-800">
        {stock.quantity}
      </span>
    </li>
  )
}

function MovementRow({ movement }: { movement: InventoryMovement }) {
  return (
    <li className="flex flex-wrap items-center justify-between gap-2 py-2 text-sm">
      <div className="flex flex-col">
        <span className="font-medium">
          {movement.product.name}{' '}
          <span className="text-neutral-500 dark:text-neutral-400">
            @ {movement.warehouse.name}
          </span>
        </span>
        <span className="text-neutral-500 dark:text-neutral-400">
          {movement.type} ·{' '}
          {movement.created_at ? new Date(movement.created_at).toLocaleString() : ''}
        </span>
      </div>
      <span className="rounded bg-neutral-100 px-2 py-0.5 text-xs font-medium dark:bg-neutral-800">
        {movement.quantity}
      </span>
    </li>
  )
}

function Pagination({
  meta,
  onPage,
}: {
  meta: { current_page: number; last_page: number; total: number } | undefined
  onPage: (page: number) => void
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
          onClick={() => onPage(meta.current_page - 1)}
        >
          Previous
        </Button>
        <Button
          variant="ghost"
          disabled={meta.current_page >= meta.last_page}
          onClick={() => onPage(meta.current_page + 1)}
        >
          Next
        </Button>
      </div>
    </div>
  )
}
