import { create } from 'zustand'
import { persist } from 'zustand/middleware'

type Theme = 'light' | 'dark' | 'system'

interface UiState {
  theme: Theme
  setTheme: (theme: Theme) => void
}

/**
 * Small client-only UI store. Real app/session state lives server-side and is
 * fetched via TanStack Query — Zustand is only for ephemeral UI preferences.
 */
export const useUiStore = create<UiState>()(
  persist(
    (set) => ({
      theme: 'system',
      setTheme: (theme) => set({ theme }),
    }),
    { name: 'nexora.ui' },
  ),
)
