import { describe, expect, it } from 'vitest'

import { ApiError } from '@/lib/api'
import { errorMessage, fieldErrors } from '@/lib/forms'

describe('fieldErrors', () => {
  it('flattens an ApiError to the first message per field', () => {
    const error = new ApiError(422, 'Invalid', { email: ['Taken', 'Bad'], name: ['Required'] })
    expect(fieldErrors(error)).toEqual({ email: 'Taken', name: 'Required' })
  })

  it('returns an empty map for non-ApiError values or when there are no errors', () => {
    expect(fieldErrors(new Error('boom'))).toEqual({})
    expect(fieldErrors(new ApiError(500, 'Server error'))).toEqual({})
    expect(fieldErrors('nope')).toEqual({})
  })
})

describe('errorMessage', () => {
  it('prefers the ApiError message', () => {
    expect(errorMessage(new ApiError(403, 'Forbidden'))).toBe('Forbidden')
  })

  it('falls back for empty or unknown errors', () => {
    expect(errorMessage(new ApiError(500, ''), 'fallback')).toBe('fallback')
    expect(errorMessage(undefined)).toBe('Something went wrong.')
    expect(errorMessage(new Error('detail'))).toBe('detail')
  })
})
