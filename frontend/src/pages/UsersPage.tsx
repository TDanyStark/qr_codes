import { useMemo } from 'react'
import { useQuery } from '@tanstack/react-query'
import axios from 'axios'

type User = {
  id: number
  username: string
  firstName: string
  lastName: string
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
    <div className="max-w-3xl mx-auto p-6">
      <h1 className="text-2xl font-semibold mb-4">Usuarios</h1>

      {isLoading && (
        <div className="text-gray-500">Cargando usuarios...</div>
      )}

      {isError && (
        <div className="text-red-600">Error: {errorMessage}</div>
      )}

      {!isLoading && !isError && (
        <div className="bg-white shadow rounded-md overflow-hidden">
          <ul className="divide-y">
            {users.length === 0 && (
              <li className="p-4 text-gray-600">No hay usuarios.</li>
            )}
            {users.map((u: User) => (
              <li key={u.id} className="p-4 flex justify-between items-center">
                <div>
                  <div className="font-medium">{u.firstName} {u.lastName}</div>
                  <div className="text-sm text-gray-500">{u.username}</div>
                </div>
                <div className="text-sm text-gray-400">ID: {u.id}</div>
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  )
}
