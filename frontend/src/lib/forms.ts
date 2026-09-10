import { ApiError } from '@/lib/api'

/** Flatten an ApiError's `errors` map to the first message per field. */
export function fieldErrors(error: unknown): Record<string, string> {
  if (!(error instanceof ApiError) || !error.errors) return {}

  const out: Record<string, string> = {}
  for (const [field, messages] of Object.entries(error.errors)) {
    if (messages[0] !== undefined) out[field] = messages[0]
  }
  return out
}

/** A single human-readable error line for a failed request. */
export function errorMessage(error: unknown, fallback = 'Something went wrong.'): string {
  if (error instanceof ApiError) return error.message || fallback
  if (error instanceof Error) return error.message || fallback
  return fallback
}
