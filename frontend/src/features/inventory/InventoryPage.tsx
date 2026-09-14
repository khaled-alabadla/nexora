import { type FormEvent, useState } from 'react'

import { AppHeader } from '@/components/AppHeader'
import { Alert, Button, Field, Input } from '@/components/ui'
import { usePermissions } from '@/features/auth/session'
import { useProducts } from '@/features/products/hooks'
import { useWarehouses } from '@/features/warehouses/hooks'
import { errorMessage, fieldErrors } from '@/lib/forms'

import { useCreateAdjustment, useMovements, useStock } from './hooks'
import type {
  AdjustmentType,
  InventoryMovement,
  MovementFilters,
  MovementType,
  Stock,
  StockFilters,
} from './types'

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
  const canAdjust = permissions.has('inventory.adjust')

  const [stockFilters, setStockFilters] = useState<StockFilters>({ per_page: 20 })
  const [movementFilters, setMovementFilters] = useState<MovementFilters>({ per_page: 20 })
  const stock = useStock(stockFilters, canView)
  const movements = useMovements(movementFilters, canView)

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
          {canAdjust ? <AdjustmentForm /> : null}

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

function AdjustmentForm() {
  const products = useProducts({ per_page: 100 })
  const warehouses = useWarehouses()
  const create = useCreateAdjustment()

  const [warehouseId, setWarehouseId] = useState('')
  const [productId, setProductId] = useState('')
  const [type, setType] = useState<AdjustmentType>('adjustment')
  const [quantityDelta, setQuantityDelta] = useState('')
  const [unitCost, setUnitCost] = useState('')
  const [reason, setReason] = useState('')
  const [force, setForce] = useState(false)
  const errors = fieldErrors(create.error)

  function submit(event: FormEvent) {
    event.preventDefault()
    create.mutate(
      {
        warehouse_id: Number(warehouseId),
        type,
        force: force || undefined,
        lines: [
          {
            product_id: Number(productId),
            quantity_delta: Number(quantityDelta),
            unit_cost: unitCost ? Number(unitCost) : undefined,
            reason: reason || undefined,
          },
        ],
      },
      {
        onSuccess: () => {
          setQuantityDelta('')
          setUnitCost('')
          setReason('')
        },
      },
    )
  }

  return (
    <form
      onSubmit={submit}
      className="flex flex-col gap-3 rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900"
    >
      <h3 className="text-sm font-semibold">Adjust stock</h3>
      {create.isError && Object.keys(errors).length === 0 ? (
        <Alert>{errorMessage(create.error, 'Could not record the adjustment.')}</Alert>
      ) : null}
      <div className="flex flex-wrap gap-3">
        <Field label="Warehouse" htmlFor="adj-warehouse">
          <select
            id="adj-warehouse"
            required
            className="rounded-md border border-neutral-300 px-2 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
            value={warehouseId}
            onChange={(e) => setWarehouseId(e.target.value)}
          >
            <option value="">Select…</option>
            {(warehouses.data ?? []).map((w) => (
              <option key={w.id} value={w.id}>
                {w.name}
              </option>
            ))}
          </select>
        </Field>
        <Field label="Product" htmlFor="adj-product" error={errors['lines.0.product_id']}>
          <select
            id="adj-product"
            required
            className="rounded-md border border-neutral-300 px-2 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
            value={productId}
            onChange={(e) => setProductId(e.target.value)}
          >
            <option value="">Select…</option>
            {(products.data?.data ?? []).map((p) => (
              <option key={p.id} value={p.id}>
                {p.sku} — {p.name}
              </option>
            ))}
          </select>
        </Field>
        <Field label="Adjustment type" htmlFor="adj-type">
          <select
            id="adj-type"
            className="rounded-md border border-neutral-300 px-2 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
            value={type}
            onChange={(e) => setType(e.target.value as AdjustmentType)}
          >
            <option value="adjustment">Adjustment</option>
            <option value="damage">Damage</option>
          </select>
        </Field>
        <Field
          label="Quantity change"
          htmlFor="adj-qty"
          error={errors['lines.0.quantity_delta'] ?? errors.quantity}
        >
          <Input
            id="adj-qty"
            type="number"
            step="0.0001"
            required
            value={quantityDelta}
            onChange={(e) => setQuantityDelta(e.target.value)}
            className="w-28"
          />
        </Field>
        <Field label="Unit cost" htmlFor="adj-cost" error={errors['lines.0.unit_cost']}>
          <Input
            id="adj-cost"
            type="number"
            step="0.0001"
            min="0"
            value={unitCost}
            onChange={(e) => setUnitCost(e.target.value)}
            className="w-28"
          />
        </Field>
        <Field label="Reason" htmlFor="adj-reason">
          <Input
            id="adj-reason"
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            className="w-48"
          />
        </Field>
      </div>
      {type === 'damage' ? (
        <p className="text-sm text-neutral-500 dark:text-neutral-400">
          Damage always decreases stock — enter a negative quantity.
        </p>
      ) : null}
      <label className="flex items-center gap-2 text-sm">
        <input type="checkbox" checked={force} onChange={(e) => setForce(e.target.checked)} />
        Allow this to take stock negative (force)
      </label>
      <Button type="submit" disabled={create.isPending} className="self-start">
        {create.isPending ? 'Recording…' : 'Record adjustment'}
      </Button>
    </form>
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
