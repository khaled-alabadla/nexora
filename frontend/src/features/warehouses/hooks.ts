import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'

import * as warehousesApi from './api'
import type { WarehouseInput } from './types'

const warehousesKey = ['warehouses'] as const

export function useWarehouses(enabled = true) {
  return useQuery({ queryKey: warehousesKey, queryFn: warehousesApi.listWarehouses, enabled })
}

export function useCreateWarehouse() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: WarehouseInput) => warehousesApi.createWarehouse(input),
    onSuccess: async () => qc.invalidateQueries({ queryKey: warehousesKey }),
  })
}

export function useUpdateWarehouse() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: Partial<WarehouseInput> }) =>
      warehousesApi.updateWarehouse(id, input),
    onSuccess: async () => qc.invalidateQueries({ queryKey: warehousesKey }),
  })
}

export function useDeleteWarehouse() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => warehousesApi.deleteWarehouse(id),
    onSuccess: async () => qc.invalidateQueries({ queryKey: warehousesKey }),
  })
}
