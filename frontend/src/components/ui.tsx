import type { ComponentProps, ReactNode } from 'react'

import { cn } from '@/lib/utils'

export function Field({
  label,
  error,
  htmlFor,
  children,
}: {
  label: string
  error?: string | undefined
  htmlFor: string
  children: ReactNode
}) {
  return (
    <div className="flex flex-col gap-1">
      <label
        htmlFor={htmlFor}
        className="text-sm font-medium text-neutral-700 dark:text-neutral-300"
      >
        {label}
      </label>
      {children}
      {error ? (
        <p role="alert" className="text-sm text-red-600 dark:text-red-400">
          {error}
        </p>
      ) : null}
    </div>
  )
}

export function Input(props: ComponentProps<'input'>) {
  return (
    <input
      {...props}
      className={cn(
        'rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm outline-none',
        'focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200',
        'dark:border-neutral-700 dark:bg-neutral-900 dark:focus:ring-neutral-800',
        props.className,
      )}
    />
  )
}

export function Button({
  variant = 'primary',
  className,
  ...props
}: ComponentProps<'button'> & { variant?: 'primary' | 'ghost' | 'danger' }) {
  const styles = {
    primary: 'bg-neutral-900 text-white hover:bg-neutral-800 dark:bg-white dark:text-neutral-900',
    ghost:
      'border border-neutral-300 hover:bg-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-800',
    danger: 'bg-red-600 text-white hover:bg-red-500',
  }[variant]

  return (
    <button
      {...props}
      className={cn(
        'inline-flex items-center justify-center rounded-md px-3 py-2 text-sm font-medium',
        'disabled:cursor-not-allowed disabled:opacity-60',
        styles,
        className,
      )}
    />
  )
}

export function Alert({
  tone = 'error',
  children,
}: {
  tone?: 'error' | 'success' | 'info'
  children: ReactNode
}) {
  const styles = {
    error: 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
    success: 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300',
    info: 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
  }[tone]

  return (
    <div role="status" className={cn('rounded-md px-3 py-2 text-sm', styles)}>
      {children}
    </div>
  )
}
