import { Link, useLocation, useNavigate } from 'react-router-dom'
import { isAdmin, clearToken } from '../lib/auth'

type Item = { to: string; label: string }

const baseItems: Item[] = [{ to: '/qr_codes', label: 'QR-Codes' }]

export default function Sidebar() {
  const loc = useLocation()
  const navigate = useNavigate()

  function handleLogout() {
    clearToken()
    navigate('/')
  }

  const items: Item[] = [...baseItems]
  if (isAdmin()) {
    items.push({ to: '/users', label: 'Users' })
  }

  return (
    <div className="text-white bg-gray-800/60">
      {/* Mobile: top bar */}
      <div className="md:hidden p-3 flex items-center gap-4">
        <div className="font-semibold">QR Manager</div>
        <nav className="ml-auto flex gap-2">
          {items.map((it) => {
            const active = loc.pathname === it.to
            return (
              <Link
                key={it.to}
                to={it.to}
                className={`px-3 py-1 rounded text-sm ${
                  active ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700'
                }`}
              >
                {it.label}
              </Link>
            )
          })}
        </nav>
      </div>

      {/* Desktop: left sidebar */}
      <aside className="hidden md:block w-60 h-full p-4 border-r border-gray-700">
        <div className="mb-6 text-xl font-semibold">QR Manager</div>
        <nav className="flex flex-col gap-2">
          {items.map((it) => {
            const active = loc.pathname === it.to
            return (
              <Link
                key={it.to}
                to={it.to}
                className={`px-3 py-2 rounded ${
                  active ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700'
                }`}
              >
                {it.label}
              </Link>
            )
          })}
          <button
            onClick={handleLogout}
            className="mt-4 text-left px-3 py-2 rounded text-gray-300 hover:bg-gray-700"
          >
            Logout
          </button>
        </nav>
      </aside>
    </div>
  )
}
