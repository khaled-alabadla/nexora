import { Alert, Button } from '@/components/ui'

import { useResendVerification, useSession } from './session'

export function VerifyEmailBanner() {
  const { data: session } = useSession()
  const resend = useResendVerification()

  if (!session || session.user.email_verified) return null

  return (
    <Alert tone="info">
      <span className="mr-2">Your email address isn't verified yet.</span>
      <Button variant="ghost" onClick={() => resend.mutate()} disabled={resend.isPending}>
        {resend.isPending
          ? 'Sending…'
          : resend.isSuccess
            ? 'Sent — check your inbox'
            : 'Resend link'}
      </Button>
    </Alert>
  )
}
