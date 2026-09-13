import { Link } from 'react-router-dom'

import { Button } from '@/components/ui'
import { useLogout, usePermissions, useSession } from '@/features/auth/session'
import { CompanySwitcher } from '@/features/companies/CompanySwitcher'

export function AppHeader() {
  const { data: session } = useSession()
  const logout = useLogout()
  const permissions = usePermissions()

  if (!session) return null

  const company = session.current_company

  return (
    <header className="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 className="text-lg font-semibold">{company ? company.name : 'Nexora'}</h1>
        <p className="text-sm text-neutral-500 dark:text-neutral-400">
          {session.user.name} · {session.user.email}
        </p>
      </div>

      {company ? (
        <nav className="flex flex-wrap gap-4 text-sm">
          <Link to="/" className="hover:underline">
            Dashboard
          </Link>
          {permissions.has('product.view') && (
            <Link to="/products" className="hover:underline">
              Products
            </Link>
          )}
          {permissions.has('category.manage') && (
            <Link to="/categories" className="hover:underline">
              Categories
            </Link>
          )}
          {permissions.has('warehouse.view') && (
            <Link to="/warehouses" className="hover:underline">
              Warehouses
            </Link>
          )}
        </nav>
      ) : null}

      <div className="flex items-center gap-3">
        <CompanySwitcher />
        <Button variant="ghost" onClick={() => logout.mutate()} disabled={logout.isPending}>
          Sign out
        </Button>
      </div>
    </header>
  )
}
