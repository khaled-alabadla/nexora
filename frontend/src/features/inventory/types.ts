export type MovementType =
  'purchase' | 'sale' | 'return' | 'adjustment' | 'transfer_in' | 'transfer_out' | 'damage'

export interface ProductSummary {
  id: number
  sku: string
  name: string
}

export interface WarehouseSummary {
  id: number
  name: string
}

export interface Stock {
  id: number
  product: ProductSummary
  warehouse: WarehouseSummary
  quantity: string
  updated_at: string | null
}

export interface InventoryMovement {
  id: number
  product: ProductSummary
  warehouse: WarehouseSummary
  type: MovementType
  quantity: string
  unit_cost: string | null
  reference_type: string | null
  reference_id: number | null
  note: string | null
  created_by: number | null
  created_at: string | null
}

export interface StockFilters {
  product_id?: number | undefined
  warehouse_id?: number | undefined
  sort?: string | undefined
  page?: number | undefined
  per_page?: number | undefined
}

export interface MovementFilters {
  product_id?: number | undefined
  warehouse_id?: number | undefined
  type?: MovementType | undefined
  sort?: string | undefined
  page?: number | undefined
  per_page?: number | undefined
}

export type AdjustmentType = 'adjustment' | 'damage'

export interface AdjustmentLineInput {
  product_id: number
  quantity_delta: number
  unit_cost?: number | undefined
  reason?: string | undefined
}

export interface AdjustmentInput {
  warehouse_id: number
  type: AdjustmentType
  force?: boolean | undefined
  lines: AdjustmentLineInput[]
}
