import { type FormEvent, useState } from 'react'

import { Alert, Button, Field, Input } from '@/components/ui'
import { usePermissions, useSession } from '@/features/auth/session'
import { errorMessage } from '@/lib/forms'

import {
  useInvitations,
  useInviteMember,
  useMembers,
  useRemoveMember,
  useRevokeInvitation,
  useRoles,
  useUpdateMemberRole,
} from './hooks'

export function MembersPanel() {
  const { data: session } = useSession()
  const permissions = usePermissions()
  const canView = permissions.has('member.view')
  const canInvite = permissions.has('member.invite')
  const canManageRoles = permissions.has('member.role.update')
  const canRemove = permissions.has('member.remove')

  const members = useMembers(canView)
  const invitations = useInvitations(canView)
  const roles = useRoles()
  const updateRole = useUpdateMemberRole()
  const removeMember = useRemoveMember()
  const revoke = useRevokeInvitation()

  if (!session?.current_company) return null
  if (!canView) {
    return (
      <p className="text-sm text-neutral-500 dark:text-neutral-400">
        You don't have access to member management.
      </p>
    )
  }

  const assignableRoles = (roles.data ?? []).filter((r) => r.slug !== 'owner')

  return (
    <section className="flex flex-col gap-6">
      {canInvite ? (
        <InviteForm roles={assignableRoles.map((r) => ({ slug: r.slug, name: r.name }))} />
      ) : null}

      <div>
        <h3 className="text-sm font-semibold">Members</h3>
        <ul className="mt-2 divide-y divide-neutral-200 dark:divide-neutral-800">
          {(members.data ?? []).map((member) => (
            <li
              key={member.user_id}
              className="flex flex-wrap items-center justify-between gap-2 py-2 text-sm"
            >
              <div>
                <span className="font-medium">{member.name}</span>
                <span className="ml-2 text-neutral-500 dark:text-neutral-400">{member.email}</span>
              </div>
              <div className="flex items-center gap-2">
                {canManageRoles && member.role.slug !== 'owner' ? (
                  <select
                    aria-label={`Role for ${member.name}`}
                    className="rounded-md border border-neutral-300 px-2 py-1 dark:border-neutral-700 dark:bg-neutral-900"
                    value={member.role.slug}
                    onChange={(e) =>
                      updateRole.mutate({ userId: member.user_id, role: e.target.value })
                    }
                  >
                    {assignableRoles.map((r) => (
                      <option key={r.slug} value={r.slug}>
                        {r.name}
                      </option>
                    ))}
                  </select>
                ) : (
                  <span className="rounded bg-neutral-100 px-2 py-0.5 text-xs dark:bg-neutral-800">
                    {member.role.name}
                  </span>
                )}
                {canRemove && member.role.slug !== 'owner' ? (
                  <Button variant="ghost" onClick={() => removeMember.mutate(member.user_id)}>
                    Remove
                  </Button>
                ) : null}
              </div>
            </li>
          ))}
        </ul>
        {updateRole.isError ? <Alert>{errorMessage(updateRole.error)}</Alert> : null}
        {removeMember.isError ? <Alert>{errorMessage(removeMember.error)}</Alert> : null}
      </div>

      {(invitations.data ?? []).length > 0 ? (
        <div>
          <h3 className="text-sm font-semibold">Pending invitations</h3>
          <ul className="mt-2 divide-y divide-neutral-200 dark:divide-neutral-800">
            {(invitations.data ?? []).map((invitation) => (
              <li
                key={invitation.id}
                className="flex items-center justify-between gap-2 py-2 text-sm"
              >
                <span>
                  {invitation.email}
                  <span className="ml-2 text-neutral-500 dark:text-neutral-400">
                    {invitation.role.name} · {invitation.status}
                  </span>
                </span>
                {canInvite ? (
                  <Button variant="ghost" onClick={() => revoke.mutate(invitation.id)}>
                    Revoke
                  </Button>
                ) : null}
              </li>
            ))}
          </ul>
        </div>
      ) : null}
    </section>
  )
}

function InviteForm({ roles }: { roles: { slug: string; name: string }[] }) {
  const invite = useInviteMember()
  const [email, setEmail] = useState('')
  const [role, setRole] = useState('')

  function submit(event: FormEvent) {
    event.preventDefault()
    invite.mutate(
      { email, role: role || (roles[0]?.slug ?? '') },
      { onSuccess: () => setEmail('') },
    )
  }

  return (
    <form onSubmit={submit} className="flex flex-wrap items-end gap-3">
      <Field label="Invite by email" htmlFor="invite-email">
        <Input
          id="invite-email"
          type="email"
          required
          value={email}
          onChange={(e) => setEmail(e.target.value)}
        />
      </Field>
      <Field label="Role" htmlFor="invite-role">
        <select
          id="invite-role"
          className="rounded-md border border-neutral-300 px-2 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
          value={role}
          onChange={(e) => setRole(e.target.value)}
        >
          {roles.map((r) => (
            <option key={r.slug} value={r.slug}>
              {r.name}
            </option>
          ))}
        </select>
      </Field>
      <Button type="submit" disabled={invite.isPending}>
        {invite.isPending ? 'Sending…' : 'Send invite'}
      </Button>
      {invite.isSuccess ? <Alert tone="success">Invitation sent.</Alert> : null}
      {invite.isError ? <Alert>{errorMessage(invite.error)}</Alert> : null}
    </form>
  )
}
