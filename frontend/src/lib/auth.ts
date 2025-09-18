// Safe JWT helper utilities
import { jwtDecode } from 'jwt-decode'

export type JwtPayload = Record<string, unknown> | null

export function getToken(): string | null {
  if (typeof window === 'undefined') return null
  return localStorage.getItem('token')
}

export function safeDecode(token: string | null): JwtPayload {
  if (!token) return null
  try {
    // jwt-decode has a default export that's a function
    return jwtDecode(token)
  } catch {
    return null
  }
}

export function getRoleFromToken(token: string | null): string | null {
  const payload = safeDecode(token)
  const obj = payload && typeof payload === 'object' ? (payload as Record<string, unknown>) : null
  const rol = obj && 'rol' in obj ? obj['rol'] : null
  return typeof rol === 'string' ? rol : null
}

export function clearToken(): void {
  if (typeof window === 'undefined') return
  localStorage.removeItem('token')
}

export function isAdmin(): boolean {
  const token = getToken()
  const rol = getRoleFromToken(token)
  return rol === 'admin'
}
