import { useMutation, useQuery, useQueryClient, type UseQueryResult } from '@tanstack/react-query'

import * as authApi from './api'
import type { LoginInput, RegisterInput, ResetPasswordInput, Session } from './types'

export const sessionKey = ['session'] as const

export function useSession(): UseQueryResult<Session | null> {
  return useQuery({
    queryKey: sessionKey,
    queryFn: authApi.fetchSession,
    staleTime: 30_000,
    retry: false,
  })
}

export function usePermissions(): Set<string> {
  const { data } = useSession()
  return new Set(data?.permissions ?? [])
}

export function useLogin() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: LoginInput) => authApi.login(input),
    onSuccess: (session) => qc.setQueryData(sessionKey, session),
  })
}

export function useRegister() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: RegisterInput) => authApi.register(input),
    onSuccess: (session) => qc.setQueryData(sessionKey, session),
  })
}

export function useLogout() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: () => authApi.logout(),
    onSuccess: async () => {
      qc.setQueryData(sessionKey, null)
      await qc.invalidateQueries()
    },
  })
}

export function useForgotPassword() {
  return useMutation({ mutationFn: (email: string) => authApi.forgotPassword(email) })
}

export function useResetPassword() {
  return useMutation({ mutationFn: (input: ResetPasswordInput) => authApi.resetPassword(input) })
}

export function useResendVerification() {
  return useMutation({ mutationFn: () => authApi.resendVerification() })
}
