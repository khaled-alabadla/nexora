import { useQuery } from '@tanstack/react-query'

import * as inventoryApi from './api'
import type { MovementFilters, StockFilters } from './types'

export function useStock(filters: StockFilters) {
  return useQuery({
    queryKey: ['inventory', 'stock', filters],
    queryFn: () => inventoryApi.listStock(filters),
    placeholderData: (previous) => previous,
  })
}

export function useMovements(filters: MovementFilters) {
  return useQuery({
    queryKey: ['inventory', 'movements', filters],
    queryFn: () => inventoryApi.listMovements(filters),
    placeholderData: (previous) => previous,
  })
}
