import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { isAdmin } from '../lib/auth'
import RequireAuth from './RequireAuth'

/**
 * RequireAdmin
 * Ensures the wrapped children are only rendered when the current user
 * is authenticated and has admin privileges. Prevents a brief flash of the
 * children by keeping them hidden until checks complete.
 */
export default function RequireAdmin({ children }: { children?: React.ReactNode }) {
  return (
    <RequireAuth>
      <AdminOnly>{children}</AdminOnly>
    </RequireAuth>
  )
}

function AdminOnly({ children }: { children?: React.ReactNode }) {
  const navigate = useNavigate()
  const admin = isAdmin()

  const [checked, setChecked] = useState<boolean>(false)

  useEffect(() => {
    if (!admin) {
      navigate('/qr_codes')
      return
    }

    setChecked(true)
  }, [navigate, admin])

  if (!checked) return 
    <div className="p-6 text-gray-300">Verificando permisos de administrador...</div>

  return <>{children}</>
}
