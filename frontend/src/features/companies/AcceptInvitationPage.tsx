import { useEffect, useRef } from 'react'

import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useNavigate, useParams } from 'react-router-dom'

import { Alert, Button } from '@/components/ui'
import { sessionKey, useSession } from '@/features/auth/session'
import { errorMessage } from '@/lib/forms'

import { acceptInvitation } from './api'

export function AcceptInvitationPage() {
  const { token } = useParams<{ token: string }>()
  const { data: session, isPending } = useSession()
  const navigate = useNavigate()
  const qc = useQueryClient()
  const attempted = useRef(false)

  const accept = useMutation({
    mutationFn: () => acceptInvitation(token ?? ''),
    onSuccess: () => qc.invalidateQueries({ queryKey: sessionKey }),
  })
  const { mutate } = accept

  useEffect(() => {
    if (isPending || attempted.current) return

    if (!session) {
      void navigate(`/login?next=${encodeURIComponent(`/invitations/${token ?? ''}/accept`)}`, {
        replace: true,
      })
      return
    }

    attempted.current = true
    mutate()
  }, [isPending, session, token, navigate, mutate])

  return (
    <main className="grid min-h-dvh place-items-center px-4 text-neutral-900 dark:text-neutral-100">
      <div className="w-full max-w-sm text-center">
        {(accept.isIdle || accept.isPending) && <p className="text-sm">Accepting invitation…</p>}
        {accept.isError && (
          <Alert>{errorMessage(accept.error, 'This invitation could not be accepted.')}</Alert>
        )}
        {accept.isSuccess && <Alert tone="success">You've joined the company.</Alert>}
        {(accept.isSuccess || accept.isError) && (
          <Button className="mt-4" onClick={() => void navigate('/', { replace: true })}>
            Go to dashboard
          </Button>
        )}
      </div>
    </main>
  )
}
