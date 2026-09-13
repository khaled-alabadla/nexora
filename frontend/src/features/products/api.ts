import { api, type Page } from '@/lib/api'

import type { CategoryInput, Product, ProductCategory, ProductFilters, ProductInput } from './types'

function toQueryString(filters: ProductFilters): string {
  const params = new URLSearchParams()
  for (const [key, value] of Object.entries(filters)) {
    if (value !== undefined && value !== '') params.set(key, String(value))
  }
  const query = params.toString()
  return query ? `?${query}` : ''
}

export const listProducts = (filters: ProductFilters = {}): Promise<Page<Product>> =>
  api.getPage<Product>(`/products${toQueryString(filters)}`)

export const getProduct = (id: number): Promise<Product> => api.get<Product>(`/products/${id}`)

export const createProduct = (input: ProductInput): Promise<Product> =>
  api.post<Product>('/products', input)

export const updateProduct = (id: number, input: Partial<ProductInput>): Promise<Product> =>
  api.put<Product>(`/products/${id}`, input)

export const deleteProduct = (id: number): Promise<void> => api.delete<void>(`/products/${id}`)

export const listCategories = (): Promise<ProductCategory[]> =>
  api.get<ProductCategory[]>('/categories')

export const createCategory = (input: CategoryInput): Promise<ProductCategory> =>
  api.post<ProductCategory>('/categories', input)

export const updateCategory = (
  id: number,
  input: Partial<CategoryInput>,
): Promise<ProductCategory> => api.put<ProductCategory>(`/categories/${id}`, input)

export const deleteCategory = (id: number): Promise<void> => api.delete<void>(`/categories/${id}`)
