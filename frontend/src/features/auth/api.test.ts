import { afterEach, describe, expect, it, vi } from 'vitest'

import { fail, ok, stubFetch } from '@/test/fetchStub'

import { fetchSession, forgotPassword, login, logout, register, resetPassword } from './api'

afterEach(() => vi.unstubAllGlobals())

const session = {
  user: { id: 1, name: 'A', email: 'a@example.com', email_verified: false },
  current_company: null,
  companies: [],
  permissions: [],
}

describe('fetchSession', () => {
  it('returns the session on 200', async () => {
    stubFetch([{ url: '/auth/me', handler: ok(session) }])
    await expect(fetchSession()).resolves.toEqual(session)
  })

  it('returns null on 401', async () => {
    stubFetch([{ url: '/auth/me', handler: fail(401, 'Unauthenticated.') }])
    await expect(fetchSession()).resolves.toBeNull()
  })

  it('rethrows other errors', async () => {
    stubFetch([{ url: '/auth/me', handler: fail(500, 'Server error') }])
    await expect(fetchSession()).rejects.toThrow('Server error')
  })
})

it('register primes CSRF then posts', async () => {
  const mock = stubFetch([{ method: 'POST', url: '/auth/register', handler: ok(session) }])
  await register({
    name: 'A',
    email: 'a@example.com',
    password: 'x',
    password_confirmation: 'x',
    company_name: 'Co',
  })
  const urls = mock.mock.calls.map((c) => c[0])
  expect(urls[0]).toContain('/sanctum/csrf-cookie')
  expect(urls[1]).toContain('/auth/register')
})

it('login posts credentials', async () => {
  stubFetch([{ method: 'POST', url: '/auth/login', handler: ok(session) }])
  await expect(login({ email: 'a@example.com', password: 'x' })).resolves.toEqual(session)
})

it('logout resolves on 204', async () => {
  stubFetch([
    { method: 'POST', url: '/auth/logout', handler: () => new Response(null, { status: 204 }) },
  ])
  await expect(logout()).resolves.toBeUndefined()
})

it('forgotPassword and resetPassword resolve', async () => {
  stubFetch([
    { method: 'POST', url: '/auth/password/forgot', handler: ok(null) },
    { method: 'POST', url: '/auth/password/reset', handler: ok(null) },
  ])
  await expect(forgotPassword('a@example.com')).resolves.toBeUndefined()
  await expect(
    resetPassword({
      token: 't',
      email: 'a@example.com',
      password: 'x',
      password_confirmation: 'x',
    }),
  ).resolves.toBeUndefined()
})
