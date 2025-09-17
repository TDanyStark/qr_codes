const STORAGE_KEY = 'login_pending'

export function setPendingEmail(email: string) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify({ email }))
}

export function getPendingEmail(): string | null {
  const raw = localStorage.getItem(STORAGE_KEY)
  if (!raw) return null
  try {
    const parsed = JSON.parse(raw) as { email: string }
    return parsed.email
  } catch {
    return null
  }
}

export function clearPending() {
  localStorage.removeItem(STORAGE_KEY)
}

const pendingEmail = { setPendingEmail, getPendingEmail, clearPending }
export default pendingEmail
