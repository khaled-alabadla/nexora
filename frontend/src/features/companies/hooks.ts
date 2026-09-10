import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'

import { sessionKey } from '@/features/auth/session'

import * as companiesApi from './api'

const membersKey = ['company', 'members'] as const
const invitationsKey = ['company', 'invitations'] as const

export function useRoles() {
  return useQuery({ queryKey: ['roles'], queryFn: companiesApi.listRoles, staleTime: Infinity })
}

export function useMembers(enabled: boolean) {
  return useQuery({ queryKey: membersKey, queryFn: companiesApi.listMembers, enabled })
}

export function useInvitations(enabled: boolean) {
  return useQuery({ queryKey: invitationsKey, queryFn: companiesApi.listInvitations, enabled })
}

export function useSwitchCompany() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (companyId: number) => companiesApi.switchCompany(companyId),
    onSuccess: async () => {
      await qc.invalidateQueries({ queryKey: sessionKey })
      await qc.invalidateQueries({ queryKey: ['company'] })
    },
  })
}

export function useCreateCompany() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (name: string) => companiesApi.createCompany(name),
    onSuccess: async () => {
      await qc.invalidateQueries({ queryKey: sessionKey })
    },
  })
}

export function useInviteMember() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ email, role }: { email: string; role: string }) =>
      companiesApi.inviteMember(email, role),
    onSuccess: async () => {
      await qc.invalidateQueries({ queryKey: invitationsKey })
    },
  })
}

export function useRevokeInvitation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => companiesApi.revokeInvitation(id),
    onSuccess: async () => {
      await qc.invalidateQueries({ queryKey: invitationsKey })
    },
  })
}

export function useUpdateMemberRole() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ userId, role }: { userId: number; role: string }) =>
      companiesApi.updateMemberRole(userId, role),
    onSuccess: async () => {
      await qc.invalidateQueries({ queryKey: membersKey })
    },
  })
}

export function useRemoveMember() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (userId: number) => companiesApi.removeMember(userId),
    onSuccess: async () => {
      await qc.invalidateQueries({ queryKey: membersKey })
    },
  })
}
