import { api } from '@/lib/api'

import type { CompanySummary } from '@/features/auth/types'

export interface Member {
  user_id: number
  name: string
  email: string
  role: { slug: string; name: string; level: number }
  joined_at: string | null
}

export interface Invitation {
  id: number
  email: string
  role: { slug: string; name: string; level: number }
  status: 'pending' | 'accepted' | 'expired'
  expires_at: string
  accepted_at: string | null
  created_at: string | null
}

export interface RoleOption {
  slug: string
  name: string
  level: number
}

export const listRoles = (): Promise<RoleOption[]> => api.get<RoleOption[]>('/roles')

export const listCompanies = (): Promise<CompanySummary[]> =>
  api.get<CompanySummary[]>('/companies')

export const createCompany = (name: string): Promise<CompanySummary> =>
  api.post<CompanySummary>('/companies', { name })

export const switchCompany = (companyId: number): Promise<CompanySummary> =>
  api.put<CompanySummary>(`/companies/${companyId}/active`)

export const listMembers = (): Promise<Member[]> => api.get<Member[]>('/company/members')

export const listInvitations = (): Promise<Invitation[]> =>
  api.get<Invitation[]>('/company/invitations')

export const inviteMember = (email: string, role: string): Promise<Invitation> =>
  api.post<Invitation>('/company/invitations', { email, role })

export const revokeInvitation = (id: number): Promise<void> =>
  api.delete<void>(`/company/invitations/${id}`)

export const updateMemberRole = (userId: number, role: string): Promise<Member> =>
  api.patch<Member>(`/company/members/${userId}`, { role })

export const removeMember = (userId: number): Promise<void> =>
  api.delete<void>(`/company/members/${userId}`)

export const acceptInvitation = (token: string): Promise<{ id: number; name: string }> =>
  api.post<{ id: number; name: string }>(`/invitations/${token}/accept`)
