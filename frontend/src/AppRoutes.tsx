import { Routes, Route, Link } from 'react-router-dom'
import UsersPage from './pages/UsersPage'
import LoginEmail from './pages/LoginEmail'
import LoginCode from './pages/LoginCode'

export default function AppRoutes() {
  return (
    <div className="min-h-screen bg-gray-900 text-white">
      <nav className="bg-gray-800/60 p-4 mb-6">
        <div className="max-w-3xl mx-auto flex items-center justify-between">
          <Link to="/" className="text-white font-medium">Iniciar sesión</Link>
          <Link to="/users" className="text-blue-400 font-medium">Usuarios</Link>
        </div>
      </nav>

      <main className="max-w-3xl mx-auto px-4">
        <Routes>
          <Route path="/" element={<LoginEmail />} />
          <Route path="/login/code" element={<LoginCode />} />
          <Route path="/users" element={<UsersPage />} />
        </Routes>
      </main>
    </div>
  )
}
