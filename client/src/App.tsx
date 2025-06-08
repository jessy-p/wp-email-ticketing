import './App.css'
import { useState, useEffect } from 'react';
import Header from './Header';
import TicketsTable from './TicketsTable';
import TicketDetailView from './TicketDetailView';
import { apiService } from './services/api';
import type { Ticket, TicketDetail } from './types/api';

function App() {
  const [tickets, setTickets] = useState<Ticket[]>([]);
  const [selectedTicketDetail, setSelectedTicketDetail] = useState<TicketDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [detailLoading, setDetailLoading] = useState(false);

  useEffect(() => {
    fetchTickets();
  }, []);

  const fetchTickets = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await apiService.getTickets();
      setTickets(response.tickets);
    } catch (err) {
      console.error('Failed to fetch tickets:', err);
      setError('Failed to load tickets. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleSelectTicket = async (ticketId: string) => {
    setSelectedTicketDetail(null);

    try {
      setDetailLoading(true);
      const detail = await apiService.getTicketDetails(ticketId);
      setSelectedTicketDetail(detail);
    } catch (err) {
      console.error('Failed to fetch ticket details:', err);
      setError('Failed to load ticket details. Please try again.');
    } finally {
      setDetailLoading(false);
    }
  };

  const handleStatusUpdate = async (ticketId: string, status: string) => {
    try {
      await apiService.updateTicketStatus(ticketId, status);
      // Refresh tickets and detail
      await fetchTickets();
      if (selectedTicketDetail && selectedTicketDetail.id === ticketId) {
        const updatedDetail = await apiService.getTicketDetails(ticketId);
        setSelectedTicketDetail(updatedDetail);
      }
    } catch (err) {
      console.error('Failed to update status:', err);
      setError('Failed to update status. Please try again.');
    }
  };

  const handleSendReply = async (ticketId: string, message: string) => {
    try {
      await apiService.sendReply(ticketId, message);
      // Refresh the ticket detail to show new reply
      if (selectedTicketDetail && selectedTicketDetail.id === ticketId) {
        const updatedDetail = await apiService.getTicketDetails(ticketId);
        setSelectedTicketDetail(updatedDetail);
      }
    } catch (err) {
      console.error('Failed to send reply:', err);
      setError('Failed to send reply. Please try again.');
    }
  };

  const openCount = tickets.filter(t => t.status === 'Open').length;
  const pendingCount = tickets.filter(t => t.status === 'Pending').length;

  if (error) {
    return (
      <div className="max-w-7xl mx-auto p-6 min-h-screen">
        <div className="bg-red-50 border border-red-200 rounded-lg p-4">
          <div className="text-red-800 font-medium">Error</div>
          <div className="text-red-600 mt-1">{error}</div>
          <button
            onClick={fetchTickets}
            className="mt-3 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
          >
            Try Again
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto p-6 min-h-screen">
      <Header openCount={openCount} pendingCount={pendingCount} />
      <TicketsTable
        tickets={tickets}
        onSelectTicket={handleSelectTicket}
        loading={loading}
      />
      {!detailLoading && selectedTicketDetail && (
        <TicketDetailView
          ticket={selectedTicketDetail}
          handleStatusUpdate={handleStatusUpdate}
          handleSendReply={handleSendReply}
        />
      )}
    </div>
  );
}

export default App;
