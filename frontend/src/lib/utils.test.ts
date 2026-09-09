import { expect, it } from 'vitest'

import { cn } from './utils'

it('joins truthy class names', () => {
  expect(cn('a', false, 'b', undefined, 'c')).toBe('a b c')
})

it('de-duplicates conflicting tailwind utilities (last wins)', () => {
  expect(cn('px-2 py-1', 'px-4')).toBe('py-1 px-4')
})
