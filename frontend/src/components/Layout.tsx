import Sidebar from './Sidebar'
import { Outlet } from 'react-router-dom'

export default function Layout() {
  return (
    <div className="min-h-screen bg-gray-900 text-white">
      <div className="md:flex md:min-h-screen">
        <div className="md:flex-shrink-0">
          <Sidebar />
        </div>

        <div className="flex-1 p-4">
          <main className="max-w-5xl mx-auto">
            <Outlet />
          </main>
        </div>
      </div>
    </div>
  )
}
