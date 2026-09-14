import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'

import * as inventoryApi from './api'
import type { AdjustmentInput, MovementFilters, StockFilters } from './types'

export function useStock(filters: StockFilters, enabled = true) {
  return useQuery({
    queryKey: ['inventory', 'stock', filters],
    queryFn: () => inventoryApi.listStock(filters),
    placeholderData: (previous) => previous,
    enabled,
  })
}

export function useMovements(filters: MovementFilters, enabled = true) {
  return useQuery({
    queryKey: ['inventory', 'movements', filters],
    queryFn: () => inventoryApi.listMovements(filters),
    placeholderData: (previous) => previous,
    enabled,
  })
}

export function useCreateAdjustment() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: AdjustmentInput) => inventoryApi.createAdjustment(input),
    onSuccess: async () => qc.invalidateQueries({ queryKey: ['inventory'] }),
  })
}
