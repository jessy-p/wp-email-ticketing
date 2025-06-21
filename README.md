# WP Email Ticketing

WP Email Ticketing is a demo WordPress plugin for support ticket management. It automatically converts inbound emails into support tickets and allows agents to reply directly from WordPress. Built with [Postmark](https://postmarkapp.com/) integration for reliable email processing.

![Ticket Listing View](./img/listing.png)

_Ticket Listing View_

![Ticket Detail View](./img/detail.png)

_Ticket Detail View_

## How it Works

* The support email (e.g., support@yourcompany.com) is connected to Postmark.
* Postmark parses incoming emails and sends structured JSON data to your WordPress webhook endpoint.
* The plugin converts email data into custom post type "Tickets" with proper metadata and attachments.
* Support agents can manage tickets through a React-powered dashboard.
* Agent responses are sent to customers.
* Customer replies containing ticket IDs are automatically threaded to existing tickets.

## Requirements

* PHP 8.0+
* Composer
* Node.js (for frontend development)

## Installation

### Production Installation

Download the latest plugin zip file from the [GitHub Releases](https://github.com/jessy-p/wp-email-ticketing/releases) page and install it through the WordPress admin:

1. Go to **Plugins > Add New** in your WordPress admin
2. Click **Upload Plugin**
3. Choose the downloaded zip file and click **Install Now**
4. **Activate** the plugin

### Development Installation

For local installation: 

```
git clone https://github.com/jessy-p/wp-email-ticketing.git
cd wp-email-ticketing
composer install
cd client && npm install && npm run build
```

## Technical Highlights

* **Webhook-First Architecture** - Real-time email processing without polling or cron jobs for instant ticket creation.

* **WordPress Integration Patterns**  
   - Leverages Custom Post Types for Tickets
   - Replies stored as WordPress comments
   - Email attachments handled via the WordPress Media Library
   - Custom taxonomies for ticket status and priority management
   - Structured metadata storage for efficient retrieval

* **PSR-4 Autoloading** - Proper namespace organization for maintainable and scalable code.

* **Interface-Driven Design** - `EmailProviderInterface` abstracts email provider logic for extensibility, can easily swap Postmark with SendGrid, Mailgun, or custom code that parses email to JSON.

* **Separation of Concerns**
   - Controllers handle HTTP requests and routing
   - DTOs provide structured data validation and transfer
   - Providers abstract email service logic
   - Clean namespace organization with single-responsibility classes

* **WordPress REST API Endpoints**  
   - Authentication via Application Passwords  
   - Authorization based on Editor role capabilities

* **Input Validation & Security** - Email validation, content length limits, and sanitization for secure webhook payload processing.

* **Frontend Stack** - React with TypeScript, using Vite for fast dev builds.

* **Code Quality Standards** - PHP CodeSniffer with PSR-12 standards for consistent PHP code style.

## Configuration

### Postmark Setup

1. Create Postmark Account: Sign up at https://postmarkapp.com/ and create a new inbound server.
2. Point Your Email to Postmark:
    - Follow Postmark's email setup guide to route your support email to Postmark.
    - This allows Postmark to receive emails sent to your support address (e.g., support@yourcompany.com) and parse them to JSON.
3. Connect Postmark to your WordPress site:
    - In WordPress, create a user with "Editor" role or higher (needs "Edit Others' Posts" capability)
    - Generate an application password for this user (Users → Profile → Application Passwords)
    - In your Postmark server settings, set the webhook URL to `https://username:applicationpassword@yoursite.com/wp-json/ticketing/v1/webhook`
    - This tells Postmark where to send the parsed email data when emails arrive.

### Testing with Postmark

1. **Send a Test Email**: Send an email to your configured support address.
2. **Verify Ticket Creation**: A new ticket should appear under "Tickets".
3. **Test Agent Reply** : Reply to the ticket from the Ticketing dashboard and the customer should receive your response via email (via wp_mail).
4. **Test Customer Reply**: Customer reply to the agent response should appear as an update to the existing ticket.

### Local Testing

To test locally without Postmark, use curl to simulate the parsed email JSON arriving at the webhook URL from PostMark

1. Create a ticket

```
curl -X POST 'https://<user>:<applicationpassword>@<yoursite>/wp-json/ticketing/v1/webhook' \
    -H 'Content-Type: application/json' \
    -d '{                                                  
      "Subject": "Mobile App Crashes",                    
      "FromName": "John Doe",    
      "From": "john@example.com",                         
      "HtmlBody": "<p>The mobile app keeps crashing every time I try to upload photos.</p>",      
      "TextBody": "The mobile app keeps crashing every time I try to upload photos.",      
      "Attachments": []   
    }'
```

2. Create a reply to ticket

```
curl -X POST 'https://<user>:<applicationpassword>@<yoursite>/wp-json/ticketing/v1/webhook' \
   -H 'Content-Type: application/json' \
   -d '{
      "Subject": "[Ticket #19]  Re: Mobile App Crashes",
      "FromName": "John Doe",
      "From": "john@example.com",
      "HtmlBody": "<p>More information about the crashing app.</p>",
      "TextBody": "More information about the crashing app",
      "Attachments": [],
      "is_reply": true,
      "ticket_id": 19
   }'
```

## API Documentation

**Frontend API Endpoints:**
- `GET /wp-json/ticketing/v1/tickets` - List tickets with filtering and pagination
- `GET /wp-json/ticketing/v1/ticket/{id}` - Get ticket details with full conversation thread
- `POST /wp-json/ticketing/v1/ticket/{id}/status` - Update ticket status
- `POST /wp-json/ticketing/v1/ticket/{id}/reply` - Send agent reply and notify customer

**Webhook Endpoint:**
- `POST /wp-json/ticketing/v1/webhook` - Accepts inbound email JSON

All endpoints require authentication and Editor-level permissions.

### Future Improvements

This demonstration plugin focuses on core functionality. Production-ready features would include:

**Features:**
  * Real-time updates on the dashboard
  * Ticket sorting and filtering on the dashboard
  * Pagination for ticket listing
  * Search capabilities to find tickets
  * Implement and leverage Priority taxonomy (added in backend but not used)
  * Email templates for agent responses
  * SLA tracking and notifications on dashboard.
  * Settings screen for configuring support email and provider

**Technical:**
  * Error logging.
  * Unit and integration testing suite
  * Custom roles and capabilities (currently using built-in Editor role)
  * Full Postmark API integration. To send emails to customer, we are currently using wp_mail due to Postmark test account limitation.
  * Webhook IP and Signature verification.
  * Rate limiting and more robust security checks on Webhook payload.

## License

This code is licensed under GPL v2 or later license. See [LICENSE](./LICENSE).
