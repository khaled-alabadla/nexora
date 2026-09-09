import { CheckCircle2, Loader2, XCircle } from 'lucide-react'

import { cn } from '@/lib/utils'

import { useHealth } from './useHealth'

function Indicator({ label, ok }: { label: string; ok: boolean }) {
  return (
    <div className="flex items-center gap-2 text-sm">
      {ok ? (
        <CheckCircle2 className="size-4 text-emerald-600" aria-hidden />
      ) : (
        <XCircle className="size-4 text-red-600" aria-hidden />
      )}
      <span>{label}</span>
      <span className="sr-only">{ok ? 'healthy' : 'unavailable'}</span>
    </div>
  )
}

export function HealthCard() {
  const { data, isPending, isError } = useHealth()

  return (
    <section
      className={cn(
        'mx-auto mt-16 w-full max-w-sm rounded-xl border p-6 shadow-sm',
        'border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900',
      )}
      aria-live="polite"
    >
      <h1 className="text-lg font-semibold">Nexora API</h1>

      {isPending && (
        <p className="mt-4 flex items-center gap-2 text-sm text-neutral-500">
          <Loader2 className="size-4 animate-spin" aria-hidden />
          Checking backend…
        </p>
      )}

      {isError && (
        <p className="mt-4 text-sm text-red-600" role="alert">
          Cannot reach the backend.
        </p>
      )}

      {data && (
        <div className="mt-4 space-y-2">
          <Indicator label={`Status: ${data.status}`} ok={data.status === 'ok'} />
          <Indicator label="Database" ok={data.database} />
          <Indicator label="Cache" ok={data.cache} />
        </div>
      )}
    </section>
  )
}
