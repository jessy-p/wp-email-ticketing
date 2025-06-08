import React from 'react';

interface HeaderProps {
  openCount: number;
  pendingCount: number;
}

const Header: React.FC<HeaderProps> = ({ openCount, pendingCount }) => (
  <div className="mb-8">
    <div className="flex items-center justify-between">
      <div>
        <h1 className="text-3xl font-bold text-gray-900">Support Tickets</h1>
        <p className="text-gray-600 mt-1">Manage and track customer support requests</p>
      </div>
      <div className="flex items-center space-x-4">
        <div className="flex items-center space-x-2 bg-white px-4 py-2 rounded-lg border border-gray-200">
          <div className="w-3 h-3 bg-green-500 rounded-full"></div>
          <span className="text-sm text-gray-700">{openCount} Open</span>
        </div>
        <div className="flex items-center space-x-2 bg-white px-4 py-2 rounded-lg border border-gray-200">
          <div className="w-3 h-3 bg-yellow-500 rounded-full"></div>
          <span className="text-sm text-gray-700">{pendingCount} Pending</span>
        </div>
      </div>
    </div>
  </div>
);

export default Header; 