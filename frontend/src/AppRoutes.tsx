import { Routes, Route } from 'react-router-dom'
import UsersPage from './pages/UsersPage'
import LoginEmail from './pages/LoginEmail'
import LoginCode from './pages/LoginCode'
import QrCodesPage from './pages/QrCodesPage'
import QrCodeStatsPage from './pages/QrCodeStatsPage'
import ReportSettingsPage from './pages/ReportSettingsPage'
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
          path="/qr_codes/:id/stats"
          element={
            <RequireAuth>
              <QrCodeStatsPage />
            </RequireAuth>
          }
        />
        <Route
          path="/users"
          element={
            <RequireAdmin>
              <UsersPage />
            </RequireAdmin>
          }
        />
        <Route
          path="/report-settings"
          element={
            <RequireAdmin>
              <ReportSettingsPage />
            </RequireAdmin>
          }
        />
      </Route>
    </Routes>
  )
}
