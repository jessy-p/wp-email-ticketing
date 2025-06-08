import React, { useState } from 'react';
import type { TicketDetail } from './types/api';

interface TicketDetailProps {
  ticket: TicketDetail | null;
  handleStatusUpdate: (ticketId: string, status: string) => void;
  handleSendReply: (ticketId: string, message: string) => void;
}

const TicketDetailView: React.FC<TicketDetailProps> = ({ ticket, handleStatusUpdate, handleSendReply }) => {
  const [newStatus, setNewStatus] = useState<string>(ticket?.status || '');
  const [reply, setReply] = useState<string>('');

  const handleUpdateClick = () => {
    if (newStatus && ticket) {
      handleStatusUpdate(ticket.id, newStatus);
    }
  };

  if (!ticket) return null;

  return (
    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden animate-slide-up">
      <div className="p-8">
        {/* Ticket Header */}
        <div className="flex items-start justify-between mb-6">
          <div>
            <h2 className="text-2xl font-bold text-gray-900 mb-2">{ticket.subject}</h2>
            <div className="flex items-center space-x-4 text-sm text-gray-600">
              <span className="font-mono">#{ticket.id}</span>
              <span>•</span>
              <span>From: {ticket.requester}</span>
            </div>
          </div>
          <div className="flex items-center space-x-3">
            <select
              id="status"
              value={newStatus}
              onChange={(e) => setNewStatus(e.target.value)}
            >
              <option value="">Select Status</option>
              <option value="Open">Open</option>
              <option value="Pending">Pending</option>
              <option value="Closed">Closed</option>
            </select>
            <button
              onClick={handleUpdateClick}
              className="ml-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
            >
              Update Status
            </button>
          </div>
        </div>
        {/* Attachments */}
        <div className="mb-8">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Attachments</h3>
          <div className="flex flex-wrap gap-3">
            {ticket.attachments.length > 0 ? (
              ticket.attachments.map(att => (
                <div key={att.name} className="flex items-center space-x-2 bg-gray-50 px-4 py-2 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors duration-200">
                  <a href={att.url} className="text-blue-600 hover:text-blue-800 text-sm font-medium">{att.name}</a>
                </div>
              ))
            ) : (
              <span className="text-gray-500 text-sm">None</span>
            )}
          </div>
        </div>
        {/* Conversation */}
        <div className="mb-8">
          <h3 className="text-lg font-semibold text-gray-900 mb-6">Conversation</h3>
          <div className="space-y-6">
            {ticket.conversation.map((msg, idx) => (
              <div className="flex space-x-4" key={idx}>
                <div className="flex-shrink-0">
                  <div className={`w-10 h-10 rounded-full flex items-center justify-center ${msg.author === ticket.requester ? 'bg-blue-100' : 'bg-green-100'}`}>
                    <span className={`${msg.author_email === ticket.requester ? 'text-blue-600' : 'text-green-600'} font-medium text-sm`}>
                      {msg.author_email === ticket.requester
                        ? msg.author.split(' ').map(n => n[0]).join('').toUpperCase()
                        : 'CS'}
                    </span>
                  </div>
                </div>
                <div className="flex-1">
                  <div className={`${msg.author_email === ticket.requester ? 'bg-gray-50' : 'bg-white border border-gray-200'} rounded-lg p-4`}>
                    <div className="flex items-center justify-between mb-2">
                      <span className="font-medium text-gray-900">{msg.author}</span>
                      <span className="text-xs text-gray-500">{msg.date}</span>
                    </div>
                    <p className="text-gray-700">{msg.content}</p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
        {/* Reply Section */}
        <div className="border-t border-gray-200 pt-8">
          <h4 className="text-lg font-semibold text-gray-900 mb-4">Add Reply</h4>
          <form
            className="space-y-4"
            onSubmit={async (e) => {
              e.preventDefault(); // Prevent default form submission behavior
              if (reply.trim() && ticket) {
                try {
                  await handleSendReply(ticket.id, reply); // Call the API to send the reply
                  setReply(''); // Clear the reply field on success
                } catch (error) {
                  console.error('Failed to send reply:', error);
                  alert('Failed to send reply. Please try again.'); // Provide user feedback
                }
              }
            }}
          >
            <div>
              <textarea
                value={reply}
                onChange={e => setReply(e.target.value)}
                placeholder="Type your reply..."
                rows={4}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 resize-none"
              ></textarea>
            </div>
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-4">
                <button type="button" className="flex items-center space-x-2 text-gray-600 hover:text-gray-800 transition-colors duration-200">
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                  </svg>
                  <span className="text-sm">Attach files</span>
                </button>
              </div>
              <button
                type="submit"
                className="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
              >
                Send Reply
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};

export default TicketDetailView;