// TypeScript interfaces matching the WordPress REST API responses

export interface Ticket {
  id: string;
  subject: string;
  status: string;
  requester: string;
  date: string;
}

export interface TicketDetail extends Ticket {
  content: string;
  priority: string[];
  attachments: Attachment[];
  conversation: Message[];
}

export interface Attachment {
  id: number;
  name: string;
  url: string;
}

export interface Message {
  author: string;
  author_email: string;
  content: string;
  date: string;
}

export interface TicketsResponse {
  tickets: Ticket[];
  total: number;
  page: number;
  per_page: number;
}

export interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  error?: string;
}

export interface StatusUpdateRequest {
  status: string;
}

export interface ReplyRequest {
  message: string;
}