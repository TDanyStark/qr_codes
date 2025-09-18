import Sidebar from './Sidebar'
import { Outlet } from 'react-router-dom'

export default function Layout() {
  return (
    <div className="min-h-screen bg-gray-900 text-white">
      <div className="md:flex md:min-h-screen">
        <div className="md:flex-shrink-0">
          <Sidebar />
        </div>

        <div className="flex-1">
          <main className="dark:bg-gray-900  min-h-screen">
            <Outlet />
          </main>
        </div>
      </div>
    </div>
  )
}
