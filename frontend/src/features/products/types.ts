export type ProductStatus = 'active' | 'inactive'

export interface ProductCategory {
  id: number
  name: string
  parent_id: number | null
  status: ProductStatus
  created_at: string | null
}

export interface Product {
  id: number
  category: ProductCategory | null
  sku: string
  name: string
  description: string | null
  barcode: string | null
  unit: string
  cost_price: string
  selling_price: string
  tax_rate: string
  minimum_stock: string
  status: ProductStatus
  created_at: string | null
}

export interface ProductInput {
  category_id?: number | null | undefined
  sku: string
  name: string
  description?: string | null | undefined
  barcode?: string | null | undefined
  unit?: string | undefined
  cost_price?: number | undefined
  selling_price?: number | undefined
  tax_rate?: number | undefined
  minimum_stock?: number | undefined
  status?: ProductStatus | undefined
}

export interface CategoryInput {
  name: string
  parent_id?: number | null | undefined
  status?: ProductStatus | undefined
}

export interface ProductFilters {
  status?: ProductStatus | undefined
  category_id?: number | undefined
  search?: string | undefined
  sort?: string | undefined
  page?: number | undefined
  per_page?: number | undefined
}
