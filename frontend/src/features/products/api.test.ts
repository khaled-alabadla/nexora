import { afterEach, expect, it, vi } from 'vitest'

import { noContent, ok, page, stubFetch } from '@/test/fetchStub'

import {
  createCategory,
  createProduct,
  deleteCategory,
  deleteProduct,
  getProduct,
  listCategories,
  listProducts,
  updateCategory,
  updateProduct,
} from './api'

afterEach(() => vi.unstubAllGlobals())

const product = { id: 1, category: null, sku: 'A1', name: 'Widget', status: 'active' }
const category = { id: 2, name: 'Electronics', parent_id: null, status: 'active' }

it('lists products with query-string filters', async () => {
  const mock = stubFetch([{ method: 'GET', url: '/products', handler: page([product]) }])

  const result = await listProducts({ status: 'active', search: 'wid', page: 2 })

  expect(result.data).toEqual([product])
  expect(result.meta.total).toBe(1)
  const url = String(mock.mock.calls[0]?.[0])
  expect(url).toContain('status=active')
  expect(url).toContain('search=wid')
  expect(url).toContain('page=2')
})

it('omits undefined/empty filters from the query string', async () => {
  const mock = stubFetch([{ method: 'GET', url: '/products', handler: page([]) }])

  await listProducts({ search: '' })

  expect(String(mock.mock.calls[0]?.[0])).toBe('http://localhost:8000/api/v1/products')
})

it('reads, creates, updates and deletes a product', async () => {
  stubFetch([
    { method: 'GET', url: '/products/1', handler: ok(product) },
    { method: 'POST', url: '/products', handler: ok(product) },
    { method: 'PUT', url: '/products/1', handler: ok(product) },
    { method: 'DELETE', url: '/products/1', handler: noContent() },
  ])

  await expect(getProduct(1)).resolves.toEqual(product)
  await expect(createProduct({ sku: 'A1', name: 'Widget' })).resolves.toEqual(product)
  await expect(updateProduct(1, { name: 'New' })).resolves.toEqual(product)
  await expect(deleteProduct(1)).resolves.toBeUndefined()
})

it('lists, creates, updates and deletes a category', async () => {
  stubFetch([
    { method: 'GET', url: '/categories', handler: ok([category]) },
    { method: 'POST', url: '/categories', handler: ok(category) },
    { method: 'PUT', url: '/categories/2', handler: ok(category) },
    { method: 'DELETE', url: '/categories/2', handler: noContent() },
  ])

  await expect(listCategories()).resolves.toEqual([category])
  await expect(createCategory({ name: 'Electronics' })).resolves.toEqual(category)
  await expect(updateCategory(2, { name: 'Renamed' })).resolves.toEqual(category)
  await expect(deleteCategory(2)).resolves.toBeUndefined()
})
