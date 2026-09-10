import { Button } from '@/components/ui'
import { useLogout, useSession } from '@/features/auth/session'
import { VerifyEmailBanner } from '@/features/auth/VerifyEmailBanner'
import { CompanySwitcher } from '@/features/companies/CompanySwitcher'
import { CreateCompanyCard } from '@/features/companies/CreateCompanyCard'
import { MembersPanel } from '@/features/companies/MembersPanel'
import { HealthCard } from '@/features/health/HealthCard'

export function DashboardPage() {
  const { data: session } = useSession()
  const logout = useLogout()

  if (!session) return null

  const company = session.current_company

  return (
    <main className="mx-auto flex min-h-dvh max-w-3xl flex-col gap-6 px-4 py-8 text-neutral-900 dark:text-neutral-100">
      <header className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-lg font-semibold">{company ? company.name : 'Nexora'}</h1>
          <p className="text-sm text-neutral-500 dark:text-neutral-400">
            {session.user.name} · {session.user.email}
          </p>
        </div>
        <div className="flex items-center gap-3">
          <CompanySwitcher />
          <Button variant="ghost" onClick={() => logout.mutate()} disabled={logout.isPending}>
            Sign out
          </Button>
        </div>
      </header>

      <VerifyEmailBanner />

      {company ? (
        <MembersPanel />
      ) : session.companies.length > 0 ? (
        <p className="text-sm text-neutral-500 dark:text-neutral-400">
          Select a company above to continue.
        </p>
      ) : (
        <CreateCompanyCard />
      )}

      <HealthCard />
    </main>
  )
}
