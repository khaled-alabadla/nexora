import { api, csrf } from '@/lib/api'

import type { LoginInput, RegisterInput, ResetPasswordInput, Session } from './types'

/** GET the current session, or `null` when the caller is not authenticated. */
export async function fetchSession(): Promise<Session | null> {
  try {
    return await api.get<Session>('/auth/me')
  } catch (error) {
    if (isStatus(error, 401)) return null
    throw error
  }
}

export async function register(input: RegisterInput): Promise<Session> {
  await csrf()
  return api.post<Session>('/auth/register', input)
}

export async function login(input: LoginInput): Promise<Session> {
  await csrf()
  return api.post<Session>('/auth/login', input)
}

export async function logout(): Promise<void> {
  await api.post<void>('/auth/logout')
}

export async function forgotPassword(email: string): Promise<void> {
  await csrf()
  await api.post<void>('/auth/password/forgot', { email })
}

export async function resetPassword(input: ResetPasswordInput): Promise<void> {
  await csrf()
  await api.post<void>('/auth/password/reset', input)
}

export async function resendVerification(): Promise<void> {
  await api.post<void>('/auth/email/verification-notification')
}

function isStatus(error: unknown, status: number): boolean {
  return typeof error === 'object' && error !== null && 'status' in error && error.status === status
}
