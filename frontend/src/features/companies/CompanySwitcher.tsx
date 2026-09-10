import { useSession } from '@/features/auth/session'

import { useSwitchCompany } from './hooks'

export function CompanySwitcher() {
  const { data: session } = useSession()
  const switchCompany = useSwitchCompany()

  const companies = session?.companies ?? []
  const currentId = session?.current_company?.id ?? ''

  if (companies.length === 0) return null

  return (
    <label className="flex items-center gap-2 text-sm">
      <span className="text-neutral-500 dark:text-neutral-400">Company</span>
      <select
        aria-label="Active company"
        className="rounded-md border border-neutral-300 bg-white px-2 py-1 text-sm dark:border-neutral-700 dark:bg-neutral-900"
        value={currentId}
        disabled={switchCompany.isPending}
        onChange={(e) => switchCompany.mutate(Number(e.target.value))}
      >
        {currentId === '' ? <option value="">Select…</option> : null}
        {companies.map((company) => (
          <option key={company.id} value={company.id}>
            {company.name} · {company.role.name}
          </option>
        ))}
      </select>
    </label>
  )
}
