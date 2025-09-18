import { useMemo } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import axios from 'axios'
import CreateUser from '@/components/CreateUser'

type User = {
  id: number
  name: string
  email: string
  rol: string
  created_at?: string | null
}

async function fetchUsers(): Promise<User[]> {
  const token = localStorage.getItem('token')
  if (!token) {
    throw new Error('No auth token found')
  }

  const res = await axios.get('/api/users', {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  })
  const body = res.data as { statusCode?: number; data?: User[] }
  return body.data ?? []
}

export default function UsersPage() {
  const queryClient = useQueryClient()
  const { data: users = [], isLoading, isError, error } = useQuery({ queryKey: ['users'], queryFn: fetchUsers })

  const errorMessage = useMemo(() => {
    if (!isError) return null
    return error?.message ?? 'Error fetching users'
  }, [isError, error])

  const handleUserCreated = () => {
    // Refresh the users list when a new user is created
    queryClient.invalidateQueries({ queryKey: ['users'] })
  }

  return (
    <div className="container_section_main">
      <div className="flex justify-between items-center mb-4">
        <h1 className="text-4xl font-semibold text-foreground">Usuarios</h1>
        <CreateUser onUserCreated={handleUserCreated} />
      </div>

      {isLoading && (
        <div className="text-muted-foreground">Cargando usuarios...</div>
      )}

      {isError && (
        <div className="text-destructive">Error: {errorMessage}</div>
      )}

      {!isLoading && !isError && (
        <div className="bg-card shadow rounded-md overflow-hidden border border-border">
          <ul className="divide-y divide-border">
            {users.length === 0 && (
              <li className="p-4 text-muted-foreground">No hay usuarios.</li>
            )}
            {users.map((u: User) => (
              <li key={u.id} className="p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                  <div className="font-medium text-foreground">{u.name}</div>
                  <div className="text-sm text-muted-foreground">{u.email}</div>
                </div>

                <div className="flex items-center gap-3">
                  <div className="text-sm text-muted-foreground">ID: {u.id}</div>
                  <div className="text-xs px-2 py-1 rounded bg-secondary text-secondary-foreground">{u.rol}</div>
                  {u.created_at && (
                    <div className="text-xs text-muted-foreground">{new Date(u.created_at).toLocaleString()}</div>
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
