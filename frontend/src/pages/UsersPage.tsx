import { useMemo } from 'react'
import { useQuery } from '@tanstack/react-query'
import axios from 'axios'

type User = {
  id: number
  name: string
  email: string
  rol: string
  created_at?: string | null
}

async function fetchUsers(): Promise<User[]> {
  const res = await axios.get('/api/users')
  const body = res.data as { statusCode?: number; data?: User[] }
  return body.data ?? []
}

export default function UsersPage() {
  const { data: users = [], isLoading, isError, error } = useQuery({ queryKey: ['users'], queryFn: fetchUsers })

  const errorMessage = useMemo(() => {
    if (!isError) return null
    return error?.message ?? 'Error fetching users'
  }, [isError, error])

  return (
    <div className="max-w-3xl mx-auto p-6 dark:bg-gray-900 min-h-screen">
      <h1 className="text-2xl font-semibold mb-4 text-gray-100">Usuarios</h1>

      {isLoading && (
        <div className="text-gray-400">Cargando usuarios...</div>
      )}

      {isError && (
        <div className="text-red-500">Error: {errorMessage}</div>
      )}

      {!isLoading && !isError && (
        <div className="bg-gray-800 shadow rounded-md overflow-hidden">
          <ul className="divide-y divide-gray-700">
            {users.length === 0 && (
              <li className="p-4 text-gray-400">No hay usuarios.</li>
            )}
            {users.map((u: User) => (
              <li key={u.id} className="p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                  <div className="font-medium text-gray-100">{u.name}</div>
                  <div className="text-sm text-gray-400">{u.email}</div>
                </div>

                <div className="flex items-center gap-3">
                  <div className="text-sm text-gray-400">ID: {u.id}</div>
                  <div className="text-xs px-2 py-1 rounded bg-gray-700 text-gray-200">{u.rol}</div>
                  {u.created_at && (
                    <div className="text-xs text-gray-500">{new Date(u.created_at).toLocaleString()}</div>
                  )}
                </div>
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  )
}
