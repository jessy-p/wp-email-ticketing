import React from 'react';
import type { Ticket } from './types/api';

interface TicketsTableProps {
  tickets: Ticket[];
  onSelectTicket: (id: string) => void;
  loading?: boolean;
}

const getStatusClass = (status: string) => {
  const normalizedStatus = typeof status === 'string' ? status.toLowerCase() : '';
  switch (normalizedStatus) {
    case 'open': return 'bg-green-100 text-green-800';
    case 'pending': return 'bg-yellow-100 text-yellow-800';
    case 'closed': return 'bg-gray-100 text-gray-600';
    default: return 'bg-blue-100 text-blue-800';
  }
};

const getDotClass = (status: string) => {
  const normalizedStatus = typeof status === 'string' ? status.toLowerCase() : '';
  switch (normalizedStatus) {
    case 'open': return 'bg-green-500';
    case 'pending': return 'bg-yellow-500';
    case 'closed': return 'bg-gray-400';
    default: return 'bg-blue-500';
  }
};

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const TicketsTable: React.FC<TicketsTableProps> = ({ tickets, onSelectTicket, loading = false }) => {
  return (
    <>
      {loading && <div className="text-center py-8 text-gray-600">Loading tickets...</div>}
      {!loading && (
    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8 animate-fade-in">
      <div className="overflow-x-auto">
        <table className="w-full">
          <thead>
            <tr className="bg-gray-50 border-b border-gray-200">
              <th className="text-left py-4 px-6 font-semibold text-gray-900 text-sm">ID</th>
              <th className="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Subject</th>
              <th className="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Status</th>
              <th className="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Requester</th>
              <th className="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Date</th>
              <th className="text-left py-4 px-6 font-semibold text-gray-900 text-sm">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {tickets.map(ticket => (
              <tr
                key={ticket.id}
                className="hover:bg-gray-50 transition-colors duration-200 cursor-pointer"
                onClick={() => onSelectTicket(ticket.id)}
              >
                <td className="py-4 px-6">
                  <span className="font-mono text-sm text-gray-600">#{ticket.id}</span>
                </td>
                <td className="py-4 px-6">
                  <div className="font-medium text-gray-900">{ticket.subject}</div>
                </td>
                <td className="py-4 px-6">
                  <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${getStatusClass(ticket.status)}`}>
                    <div className={`w-1.5 h-1.5 rounded-full mr-1.5 ${getDotClass(ticket.status)}`}></div>
                    {ticket.status}
                  </span>
                </td>
                <td className="py-4 px-6">
                  <div className="text-gray-900">{ticket.requester}</div>
                </td>
                <td className="py-4 px-6">
                  <div className="text-gray-600 text-sm">{formatDate(ticket.date)}</div>
                </td>
                <td className="py-4 px-6">
                  <button className="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                    View
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
      )}
    </>
  );
}

export default TicketsTable;