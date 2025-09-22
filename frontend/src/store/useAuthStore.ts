import { create } from 'zustand'
type AuthState = {
  pendingEmail: string | null
  setPendingEmail: (email: string) => void
  clearPending: () => void
}

const useAuthStore = create<AuthState>((set) => ({
  pendingEmail: null,
  setPendingEmail: (email: string) => set(() => ({ pendingEmail: email })),
  clearPending: () => set(() => ({ pendingEmail: null })),
}))

export default useAuthStore
