import useAuthStore from '@/store/useAuthStore'

export function setPendingEmail(email: string) {
  useAuthStore.getState().setPendingEmail(email)
}

export function getPendingEmail(): string | null {
  return useAuthStore.getState().pendingEmail
}

export function clearPending() {
  useAuthStore.getState().clearPending()
}

const pendingEmail = { setPendingEmail, getPendingEmail, clearPending }
export default pendingEmail
