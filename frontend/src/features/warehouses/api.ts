import { api } from '@/lib/api'

import type { Warehouse, WarehouseInput } from './types'

export const listWarehouses = (): Promise<Warehouse[]> => api.get<Warehouse[]>('/warehouses')

export const createWarehouse = (input: WarehouseInput): Promise<Warehouse> =>
  api.post<Warehouse>('/warehouses', input)

export const updateWarehouse = (id: number, input: Partial<WarehouseInput>): Promise<Warehouse> =>
  api.put<Warehouse>(`/warehouses/${id}`, input)

export const deleteWarehouse = (id: number): Promise<void> => api.delete<void>(`/warehouses/${id}`)
