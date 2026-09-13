import { AppHeader } from '@/components/AppHeader'
import { useSession } from '@/features/auth/session'
import { VerifyEmailBanner } from '@/features/auth/VerifyEmailBanner'
import { CreateCompanyCard } from '@/features/companies/CreateCompanyCard'
import { MembersPanel } from '@/features/companies/MembersPanel'
import { HealthCard } from '@/features/health/HealthCard'

export function DashboardPage() {
  const { data: session } = useSession()

  if (!session) return null

  const company = session.current_company

  return (
    <main className="mx-auto flex min-h-dvh max-w-3xl flex-col gap-6 px-4 py-8 text-neutral-900 dark:text-neutral-100">
      <AppHeader />

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
