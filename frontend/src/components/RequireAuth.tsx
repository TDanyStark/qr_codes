import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import axios from 'axios'

export default function RequireAuth({ children }: { children?: React.ReactNode }) {
  const [checking, setChecking] = useState(true)
  const navigate = useNavigate()

  useEffect(() => {
    const token = localStorage.getItem('token')
    if (!token) {
      navigate('/')
      return
    }

    async function verify() {
      try {
        await axios.get('/api/token/verify', { headers: { Authorization: `Bearer ${token}` } })
        setChecking(false)
      } catch (err) {
        console.debug('token verify failed', err)
        localStorage.removeItem('token')
        navigate('/')
      }
    }

    verify()
  }, [navigate])

  if (checking) {
    return <div className="p-6 text-gray-300">Comprobando sesión...</div>
  }

  return <>{children}</>
}
