import { UserCircleIcon, BellIcon } from '@heroicons/react/24/outline';
import Link from 'next/link';

export default function DashboardHeader({ user }) {
  return (
    <header className="bg-white shadow-sm">
      <div className="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between">
          <h2 className="text-lg font-semibold text-gray-900">Welcome, {user.username}</h2>
          <div className="flex items-center space-x-4">
            <Link href="/notifications" className="text-gray-500 hover:text-gray-700">
              <BellIcon className="h-6 w-6" />
            </Link>
            <Link href="/profile" className="text-gray-500 hover:text-gray-700">
              <UserCircleIcon className="h-6 w-6" />
            </Link>
          </div>
        </div>
      </div>
    </header>
  );
}
