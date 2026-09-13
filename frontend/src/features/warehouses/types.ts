export type WarehouseStatus = 'active' | 'inactive'

export interface Warehouse {
  id: number
  name: string
  location: string | null
  is_default: boolean
  status: WarehouseStatus
  created_at: string | null
}

export interface WarehouseInput {
  name: string
  location?: string | null | undefined
  status?: WarehouseStatus | undefined
  is_default?: boolean | undefined
}
