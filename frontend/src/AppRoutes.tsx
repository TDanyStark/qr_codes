import { Routes, Route } from 'react-router-dom'
import UsersPage from './pages/UsersPage'
import LoginEmail from './pages/LoginEmail'
import LoginCode from './pages/LoginCode'
import QrCodesPage from './pages/QrCodesPage'
import Layout from './components/Layout'
import RequireAuth from './components/RequireAuth'
import RequireAdmin from './components/RequireAdmin'

export default function AppRoutes() {
  return (
    <Routes>
      {/* Auth routes - no sidebar/layout */}
      <Route path="/" element={<LoginEmail />} />
      <Route path="/login/code" element={<LoginCode />} />

      {/* App routes - these render inside the Layout (which includes the Sidebar) */}
      <Route element={<Layout />}>
        <Route
          path="/qr_codes"
          element={
            <RequireAuth>
              <QrCodesPage />
            </RequireAuth>
          }
        />
        <Route
          path="/users"
          element={
            <RequireAuth>
              <RequireAdmin>
                <UsersPage />
              </RequireAdmin>
            </RequireAuth>
          }
        />
      </Route>
    </Routes>
  )
}
