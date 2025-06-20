import type { 
  TicketDetail, 
  TicketsResponse, 
} from '../types/api';

declare global {
  interface Window {
    wpApiSettings?: {
      root: string;
      nonce: string;
    };
    standaloneApiUrl?: string;
  }
}

class ApiService {
  private baseUrl: string;
  private nonce: string;

  constructor() {
    this.baseUrl = (window.wpApiSettings?.root ?? window.standaloneApiUrl )|| '/wp-json/';
    this.nonce = window.wpApiSettings?.nonce || '';
  }

  private async request<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
    const url = `${this.baseUrl}ticketing/v1${endpoint}`;
    
    const defaultHeaders: HeadersInit = {
      'Content-Type': 'application/json',
    };

    if (this.nonce) {
      defaultHeaders['X-WP-Nonce'] = this.nonce;
    }

    const config: RequestInit = {
      ...options,
      headers: {
        ...defaultHeaders,
        ...options.headers,
      },
    };

    try {
      const response = await fetch(url, config);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      return data;
    } catch (error) {
      console.error('API request failed:', error);
      throw error;
    }
  }

  async getTickets(params: {
    page?: number;
    per_page?: number;
    status?: string;
    search?: string;
  } = {}): Promise<TicketsResponse> {
    const searchParams = new URLSearchParams();
    
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined) {
        searchParams.append(key, value.toString());
      }
    });

    const queryString = searchParams.toString();
    const endpoint = `/tickets${queryString ? `?${queryString}` : ''}`;
    
    return this.request<TicketsResponse>(endpoint);
  }

  async getTicketDetails(id: string): Promise<TicketDetail> {
    return this.request<TicketDetail>(`/ticket/${id}`);
  }

  async updateTicketStatus(id: string, status: string): Promise<{ success: boolean; ticket_id: string; status: string }> {
    return this.request(`/ticket/${id}/status`, {
      method: 'POST',
      body: JSON.stringify({ status }),
    });
  }

  async sendReply(id: string, message: string): Promise<{ success: boolean; ticket_id: string; comment_id: string }> {
    return this.request(`/ticket/${id}/reply`, {
      method: 'POST',
      body: JSON.stringify({ message }),
    });
  }
}

export const apiService = new ApiService();