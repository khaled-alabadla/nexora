import { api, type Page } from '@/lib/api'

import type { InventoryMovement, MovementFilters, Stock, StockFilters } from './types'

function toQueryString(filters: StockFilters | MovementFilters): string {
  const params = new URLSearchParams()
  for (const [key, value] of Object.entries(filters)) {
    if (value !== undefined && value !== '') params.set(key, String(value as string | number))
  }
  const query = params.toString()
  return query ? `?${query}` : ''
}

export const listStock = (filters: StockFilters = {}): Promise<Page<Stock>> =>
  api.getPage<Stock>(`/inventory/stock${toQueryString(filters)}`)

export const listMovements = (filters: MovementFilters = {}): Promise<Page<InventoryMovement>> =>
  api.getPage<InventoryMovement>(`/inventory/movements${toQueryString(filters)}`)
