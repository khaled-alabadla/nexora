import { create } from 'zustand'
import { createJSONStorage, persist } from 'zustand/middleware'

type Theme = 'light' | 'dark' | 'system'

interface UiState {
  theme: Theme
  setTheme: (theme: Theme) => void
}

/**
 * Guarded storage: `localStorage` can be unavailable (private mode, disabled
 * cookies) or absent in some test/SSR contexts. Fall back to an in-memory map.
 */
const memory = new Map<string, string>()

const safeStorage = createJSONStorage<UiState>(() => {
  try {
    if (typeof window !== 'undefined' && window.localStorage) {
      window.localStorage.getItem('__probe__')
      return window.localStorage
    }
  } catch {
    /* fall through */
  }

  return {
    getItem: (key) => memory.get(key) ?? null,
    setItem: (key, value) => void memory.set(key, value),
    removeItem: (key) => void memory.delete(key),
  }
})

/**
 * Small client-only UI store. Real app/session state lives server-side and is
 * fetched via TanStack Query — Zustand is only for ephemeral UI preferences.
 */
export const useUiStore = create<UiState>()(
  persist(
    (set) => ({
      theme: 'system',
      setTheme: (theme) => {
        set({ theme })
      },
    }),
    { name: 'nexora.ui', storage: safeStorage },
  ),
)
