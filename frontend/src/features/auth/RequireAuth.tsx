import type { ReactNode } from 'react'

import { Navigate, useLocation } from 'react-router-dom'

import { useSession } from './session'

export function RequireAuth({ children }: { children: ReactNode }) {
  const { data: session, isPending } = useSession()
  const location = useLocation()

  if (isPending) {
    return (
      <div className="grid min-h-dvh place-items-center text-sm text-neutral-500 dark:text-neutral-400">
        Loading…
      </div>
    )
  }

  if (!session) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />
  }

  return <>{children}</>
}
