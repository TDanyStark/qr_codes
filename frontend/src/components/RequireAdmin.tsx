import { useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { getToken, isAdmin } from '../lib/auth'

export default function RequireAdmin({ children }: { children?: React.ReactNode }) {
  const navigate = useNavigate()

  useEffect(() => {
    const token = getToken()
    if (!token) {
      navigate('/login')
      return
    }

    if (!isAdmin()) {
      navigate('/qr_codes')
    }
  }, [navigate])

  return <>{children}</>
}
