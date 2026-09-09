import { beforeEach, expect, it } from 'vitest'

import { useUiStore } from './ui'

beforeEach(() => {
  useUiStore.getState().setTheme('system')
})

it('defaults the theme to system', () => {
  expect(useUiStore.getState().theme).toBe('system')
})

it('updates the theme through its action', () => {
  useUiStore.getState().setTheme('dark')
  expect(useUiStore.getState().theme).toBe('dark')

  useUiStore.getState().setTheme('light')
  expect(useUiStore.getState().theme).toBe('light')
})

it('writes the theme to its persisted storage', () => {
  useUiStore.getState().setTheme('dark')

  const raw = useUiStore.persist.getOptions().storage?.getItem('nexora.ui')

  expect(JSON.stringify(raw)).toContain('dark')
})
