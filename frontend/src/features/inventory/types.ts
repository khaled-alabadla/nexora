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
