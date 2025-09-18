import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { getToken, isAdmin } from '../lib/auth'

/**
 * RequireAdmin
 * Ensures the wrapped children are only rendered when the current user
 * is authenticated and has admin privileges. Prevents a brief flash of the
 * children by keeping them hidden until checks complete.
 */
export default function RequireAdmin({ children }: { children?: React.ReactNode }) {
  const navigate = useNavigate()
  const token = getToken()
  const admin = token ? isAdmin() : false

  const [checked, setChecked] = useState<boolean>(false)

  useEffect(() => {
    if (!token) {
      navigate('/')
      return
    }

    if (!admin) {
      navigate('/qr_codes')
      return
    }

    // All good — allow rendering children
    setChecked(true)
  }, [navigate, token, admin])

  if (!checked) return null

  return <>{children}</>
}
