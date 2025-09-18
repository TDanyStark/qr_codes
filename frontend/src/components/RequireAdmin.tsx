import { useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { jwtDecode } from 'jwt-decode'

export default function RequireAdmin({ children }: { children?: React.ReactNode }) {
  const navigate = useNavigate()

  useEffect(() => {
    const token = localStorage.getItem('token')
    if (!token) {
      navigate('/login')
      return
    }

    const payload = (() => {
      try {
        return jwtDecode(token)
      } catch {
        return null
      }
    })()
    // payload may be unknown; do runtime checks
  const obj = payload && typeof payload === 'object' ? (payload as unknown as Record<string, unknown>) : null
  const rol = obj && 'rol' in obj ? obj['rol'] : null

    if (rol !== 'admin') {
      navigate('/qr_codes')
    }
  }, [navigate])

  return <>{children}</>
}
