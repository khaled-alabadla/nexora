import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'

import * as productsApi from './api'
import type { CategoryInput, ProductFilters, ProductInput } from './types'

const productsKey = (filters: ProductFilters) => ['products', filters] as const
const categoriesKey = ['categories'] as const

export function useProducts(filters: ProductFilters) {
  return useQuery({
    queryKey: productsKey(filters),
    queryFn: () => productsApi.listProducts(filters),
    placeholderData: (previous) => previous,
  })
}

export function useCategories(enabled = true) {
  return useQuery({ queryKey: categoriesKey, queryFn: productsApi.listCategories, enabled })
}

export function useCreateProduct() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: ProductInput) => productsApi.createProduct(input),
    onSuccess: async () => qc.invalidateQueries({ queryKey: ['products'] }),
  })
}

export function useUpdateProduct() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: Partial<ProductInput> }) =>
      productsApi.updateProduct(id, input),
    onSuccess: async () => qc.invalidateQueries({ queryKey: ['products'] }),
  })
}

export function useDeleteProduct() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => productsApi.deleteProduct(id),
    onSuccess: async () => qc.invalidateQueries({ queryKey: ['products'] }),
  })
}

export function useCreateCategory() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: CategoryInput) => productsApi.createCategory(input),
    onSuccess: async () => qc.invalidateQueries({ queryKey: categoriesKey }),
  })
}

export function useUpdateCategory() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: Partial<CategoryInput> }) =>
      productsApi.updateCategory(id, input),
    onSuccess: async () => qc.invalidateQueries({ queryKey: categoriesKey }),
  })
}

export function useDeleteCategory() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => productsApi.deleteCategory(id),
    onSuccess: async () => qc.invalidateQueries({ queryKey: categoriesKey }),
  })
}
