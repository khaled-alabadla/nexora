export interface AuthUser {
  id: number
  name: string
  email: string
  email_verified: boolean
}

export interface Role {
  slug: string
  name: string
  level: number
}

export interface CompanySummary {
  id: number
  name: string
  slug: string
  status: 'active' | 'suspended'
  role: Role
}

export interface Session {
  user: AuthUser
  current_company: CompanySummary | null
  companies: CompanySummary[]
  permissions: string[]
}

export interface RegisterInput {
  name: string
  email: string
  password: string
  password_confirmation: string
  company_name: string
}

export interface LoginInput {
  email: string
  password: string
  remember?: boolean
}

export interface ResetPasswordInput {
  token: string
  email: string
  password: string
  password_confirmation: string
}
