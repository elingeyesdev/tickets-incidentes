# COMPANY_ADMIN API ENDPOINTS REFERENCE

## AUTHENTICATION
All endpoints require JWT Bearer token authentication:
- **Header**: `Authorization: Bearer <jwt_token>`
- **Token obtained from**: `/api/auth/login` or `/api/auth/login/google`
- **Token refresh**: `/api/auth/refresh`

## BASE URL
`/api`

---

## 1. TICKET CATEGORY MANAGEMENT

### GET /api/tickets/categories
**List categories for a company**
- **Query Parameters**:
  - `company_id` (required): UUID of the company
  - `is_active` (optional): Filter by active status (true/false)
- **Response**: Array of categories with ticket counts
- **Role Access**: All authenticated users

### POST /api/tickets/categories
**Create new category**
- **Request Body**:
  ```json
  {
    "name": "Technical Support",
    "description": "Technical issues with the system"
  }
  ```
- **Notes**:
  - company_id automatically inferred from JWT
  - Category names must be unique within company
  - Created with is_active=true by default
- **Response**: 201 Created

### PUT /api/tickets/categories/{id}
**Update category**
- **Path Parameters**:
  - `id`: Category UUID
- **Request Body** (all optional):
  ```json
  {
    "name": "Advanced Technical Support",
    "description": "Complex technical issues",
    "is_active": true
  }
  ```
- **Notes**: Partial updates supported
- **Response**: 200 OK

### DELETE /api/tickets/categories/{id}
**Delete category**
- **Path Parameters**:
  - `id`: Category UUID
- **Notes**:
  - Cannot delete categories with active tickets (open, pending, resolved)
  - Hard delete (not soft delete)
- **Response**: 200 OK

---

## 2. TICKET MANAGEMENT

### GET /api/tickets
**List all company tickets with filters**
- **Query Parameters**:
  - `status`: Filter by status (OPEN, PENDING, RESOLVED, CLOSED)
  - `category_id`: Filter by category UUID
  - `owner_agent_id`: Filter by agent UUID ("null" for unassigned, "me" for yours)
  - `created_by_user_id`: Filter by creator UUID
  - `last_response_author_type`: Filter by last responder (user/agent)
  - `search`: Search in title and description
  - `created_from` / `created_to`: Date range filters
  - `sort_by`: Field to sort by
  - `sort_order`: asc/desc
  - `page`: Page number (default: 1)
  - `per_page`: Items per page
- **Role Access**: COMPANY_ADMIN sees all company tickets
- **Response**: Paginated ticket list

### GET /api/tickets/{ticket}
**Get single ticket details**
- **Path Parameters**:
  - `ticket`: Ticket code (e.g., TKT-2025-00001)
- **Response**: Full ticket details

### POST /api/tickets
**Create new ticket**
- **Request Body**:
  ```json
  {
    "title": "Issue with login",
    "description": "Cannot access my account",
    "category_id": "uuid-here"
  }
  ```
- **Response**: 201 Created

### PATCH /api/tickets/{ticket}
**Update ticket**
- **Path Parameters**:
  - `ticket`: Ticket code
- **Request Body** (partial):
  ```json
  {
    "title": "Updated title",
    "category_id": "new-category-uuid"
  }
  ```
- **Notes**: COMPANY_ADMIN can update tickets from their company
- **Response**: 200 OK

### DELETE /api/tickets/{ticket}
**Delete closed ticket (COMPANY_ADMIN only)**
- **Path Parameters**:
  - `ticket`: Ticket code
- **Authorization**: Only COMPANY_ADMIN
- **Restriction**: Ticket must be in CLOSED status
- **Response**: 200 OK

### POST /api/tickets/{ticket}/assign
**Assign ticket to agent**
- **Path Parameters**:
  - `ticket`: Ticket code
- **Request Body**:
  ```json
  {
    "owner_agent_id": "agent-uuid"
  }
  ```
- **Notes**: Agent must be from same company
- **Response**: 200 OK

### POST /api/tickets/{ticket}/resolve
**Mark ticket as resolved**
- **Path Parameters**:
  - `ticket`: Ticket code
- **Request Body** (optional):
  ```json
  {
    "resolution_notes": "Issue resolved by..."
  }
  ```
- **Notes**: Only AGENT/COMPANY_ADMIN can resolve
- **Response**: 200 OK

### POST /api/tickets/{ticket}/close
**Close ticket**
- **Path Parameters**:
  - `ticket`: Ticket code
- **Request Body** (optional):
  ```json
  {
    "closing_notes": "Ticket closed because..."
  }
  ```
- **Notes**: AGENT can close any status; USER can only close RESOLVED
- **Response**: 200 OK

### POST /api/tickets/{ticket}/reopen
**Reopen closed/resolved ticket**
- **Path Parameters**:
  - `ticket`: Ticket code
- **Request Body** (optional):
  ```json
  {
    "reason": "Issue not fully resolved"
  }
  ```
- **Response**: 200 OK

---

## 3. TICKET RESPONSES

### GET /api/tickets/{ticket}/responses
**List all responses**
- **Path Parameters**:
  - `ticket`: Ticket code
- **Response**: Array of responses in chronological order

### POST /api/tickets/{ticket}/responses
**Add new response**
- **Path Parameters**:
  - `ticket`: Ticket code
- **Request Body**:
  ```json
  {
    "content": "Response message here"
  }
  ```
- **Notes**:
  - author_type automatically determined from role
  - Cannot respond to CLOSED tickets
  - First AGENT response auto-assigns ticket
- **Response**: 201 Created

### GET /api/tickets/{ticket}/responses/{response}
**Get single response**
- **Path Parameters**:
  - `ticket`: Ticket code
  - `response`: Response UUID
- **Response**: Response details with author and attachments

### PATCH /api/tickets/{ticket}/responses/{response}
**Update response (within 30 min)**
- **Path Parameters**:
  - `ticket`: Ticket code
  - `response`: Response UUID
- **Request Body**:
  ```json
  {
    "content": "Updated response content"
  }
  ```
- **Restriction**: Only author, within 30 minutes
- **Response**: 200 OK

### DELETE /api/tickets/{ticket}/responses/{response}
**Delete response (within 30 min)**
- **Path Parameters**:
  - `ticket`: Ticket code
  - `response`: Response UUID
- **Restriction**: Only author, within 30 minutes
- **Response**: 200 OK

---

## 4. TICKET ATTACHMENTS

### GET /api/tickets/{ticket}/attachments
**List all attachments**
- **Path Parameters**:
  - `ticket`: Ticket code
- **Response**: Array of attachment metadata

### POST /api/tickets/{ticket}/attachments
**Upload attachment**
- **Path Parameters**:
  - `ticket`: Ticket code
- **Content-Type**: multipart/form-data
- **Body**:
  - `file`: File to upload (max 10MB)
- **Allowed Types**: pdf, txt, log, doc, docx, xls, xlsx, csv, jpg, jpeg, png, gif, bmp, webp, svg, mp4
- **Limit**: 5 attachments per ticket
- **Response**: 201 Created

### GET /api/tickets/attachments/{attachment}/download
**Download attachment**
- **Path Parameters**:
  - `attachment`: Attachment UUID
- **Response**: File download

### DELETE /api/tickets/{ticket}/attachments/{attachment}
**Delete attachment**
- **Path Parameters**:
  - `ticket`: Ticket code
  - `attachment`: Attachment UUID
- **Response**: 200 OK

---

## 5. ANNOUNCEMENT MANAGEMENT

### GET /api/announcements
**List all company announcements**
- **Query Parameters**:
  - `status`: DRAFT, PUBLISHED, SCHEDULED, ARCHIVED
  - `type`: ALERT, NEWS, INCIDENT, MAINTENANCE
  - `search`: Search in title and content
  - `sort`: Sort field with direction (e.g., -published_at)
  - `published_after` / `published_before`: Date filters
  - `company_id`: Filter by company (UUID)
  - `page`: Page number (default: 1)
  - `per_page`: Items per page (max: 100)
- **Role Access**: COMPANY_ADMIN sees all statuses from their company
- **Response**: Paginated announcement list

### GET /api/announcements/{announcement}
**Get single announcement**
- **Path Parameters**:
  - `announcement`: Announcement UUID
- **Response**: Full announcement details with metadata

### PUT /api/announcements/{announcement}
**Update announcement**
- **Path Parameters**:
  - `announcement`: Announcement UUID
- **Request Body**: Varies by type
- **Notes**:
  - Only DRAFT and SCHEDULED can be fully edited
  - Published ALERT can only update ended_at
- **Response**: 200 OK

### DELETE /api/announcements/{announcement}
**Delete draft/archived announcement**
- **Path Parameters**:
  - `announcement`: Announcement UUID
- **Restriction**: Only DRAFT or ARCHIVED status
- **Response**: 200 OK

### POST /api/announcements/{id}/publish
**Publish announcement**
- **Path Parameters**:
  - `id`: Announcement UUID
- **Notes**:
  - Can publish DRAFT or SCHEDULED
  - Sets published_at to current time
  - Cancels scheduled jobs if was SCHEDULED
- **Response**: 200 OK

### POST /api/announcements/{id}/archive
**Archive published announcement**
- **Path Parameters**:
  - `id`: Announcement UUID
- **Restriction**: Only PUBLISHED announcements
- **Notes**: Preserves published_at timestamp
- **Response**: 200 OK

### POST /api/announcements/{id}/restore
**Restore archived to draft**
- **Path Parameters**:
  - `id`: Announcement UUID
- **Restriction**: Only ARCHIVED announcements
- **Notes**: Clears published_at, returns to DRAFT
- **Response**: 200 OK

### POST /api/announcements/{id}/schedule
**Schedule for future publication**
- **Path Parameters**:
  - `id`: Announcement UUID
- **Request Body**:
  ```json
  {
    "scheduled_for": "2025-11-20T10:00:00Z"
  }
  ```
- **Notes**:
  - Must be 5 minutes to 1 year in future
  - Only DRAFT announcements
- **Response**: 200 OK

### POST /api/announcements/{id}/unschedule
**Cancel scheduled publication**
- **Path Parameters**:
  - `id`: Announcement UUID
- **Restriction**: Only SCHEDULED announcements
- **Notes**: Returns to DRAFT, cancels queued job
- **Response**: 200 OK

### GET /api/announcements/schemas
**Get metadata schemas for forms**
- **Response**: Schema structure for each announcement type
- **Use**: Frontend form generation

---

## 6. ALERTS

### POST /api/announcements/alerts
**Create alert announcement**
- **Request Body**:
  ```json
  {
    "title": "Security Breach Detected",
    "content": "We have detected unauthorized access attempts...",
    "metadata": {
      "urgency": "CRITICAL",
      "alert_type": "security",
      "message": "Change your password immediately",
      "action_required": true,
      "action_description": "Go to security settings"
    },
    "action": "publish",
    "scheduled_for": "2025-11-20T10:00:00Z"
  }
  ```
- **Metadata Fields**:
  - `urgency`: HIGH or CRITICAL only
  - `alert_type`: security, system, service, compliance
  - `message`: Alert message
  - `action_required`: boolean
  - `action_description`: required if action_required=true
- **Action Options**: draft (default), publish, schedule
- **Response**: 201 Created

---

## 7. NEWS

### POST /api/announcements/news
**Create news announcement**
- **Request Body**:
  ```json
  {
    "title": "New Feature Release: Dark Mode",
    "body": "We are excited to announce dark mode...",
    "metadata": {
      "news_type": "feature_release",
      "target_audience": ["users", "agents"],
      "summary": "Dark mode now available",
      "call_to_action": {
        "text": "Try it now",
        "url": "https://app.example.com/settings"
      }
    },
    "action": "publish"
  }
  ```
- **Metadata Fields**:
  - `news_type`: feature_release, policy_update, general_update
  - `target_audience`: Array of ["users", "agents", "admins"]
  - `summary`: Brief summary
  - `call_to_action`: Optional CTA object
- **Response**: 201 Created

---

## 8. INCIDENTS

### POST /api/v1/announcements/incidents
**Create incident announcement**
- **Request Body**:
  ```json
  {
    "title": "Service Outage",
    "content": "API service is experiencing issues...",
    "urgency": "HIGH",
    "is_resolved": false,
    "started_at": "2025-11-17T08:00:00Z",
    "affected_services": ["API", "Dashboard"],
    "action": "publish"
  }
  ```
- **Fields**:
  - `urgency`: LOW, MEDIUM, HIGH, CRITICAL
  - `is_resolved`: boolean
  - `started_at`: ISO 8601 (defaults to now)
  - `ended_at`: ISO 8601 (optional)
  - `resolved_at`: ISO 8601 (optional)
  - `resolution_content`: Resolution details (optional)
  - `affected_services`: Array of service names
- **Response**: 201 Created

### POST /api/v1/announcements/incidents/{id}/resolve
**Resolve incident**
- **Path Parameters**:
  - `id`: Incident UUID
- **Request Body**:
  ```json
  {
    "resolution_content": "Issue was caused by... Fixed by...",
    "title": "Updated title (optional)",
    "ended_at": "2025-11-17T10:00:00Z"
  }
  ```
- **Notes**:
  - Can only be called once
  - Sets is_resolved=true, resolved_at to current time
- **Response**: 200 OK

---

## 9. MAINTENANCE

### POST /api/announcements/maintenance
**Create maintenance announcement**
- **Request Body**:
  ```json
  {
    "title": "Server Maintenance",
    "content": "Scheduled maintenance on servers...",
    "urgency": "HIGH",
    "scheduled_start": "2025-11-20T10:00:00Z",
    "scheduled_end": "2025-11-20T12:00:00Z",
    "is_emergency": false,
    "affected_services": ["API", "Dashboard"],
    "action": "publish",
    "scheduled_for": "2025-11-20T09:00:00Z"
  }
  ```
- **Fields**:
  - `urgency`: LOW, MEDIUM, HIGH
  - `scheduled_start` / `scheduled_end`: ISO 8601 (end must be after start)
  - `is_emergency`: boolean
  - `affected_services`: Array (optional)
- **Response**: 201 Created

### POST /api/announcements/maintenance/{announcement}/start
**Mark maintenance started**
- **Path Parameters**:
  - `announcement`: Announcement UUID
- **Notes**:
  - Sets actual_start to current time
  - Can only be called once
- **Response**: 200 OK

### POST /api/announcements/maintenance/{announcement}/complete
**Mark maintenance completed**
- **Path Parameters**:
  - `announcement`: Announcement UUID
- **Notes**:
  - Sets actual_end to current time
  - Requires actual_start to be set first
  - Validates end is after start
- **Response**: 200 OK

---

## 10. HELP CENTER ARTICLES

### GET /api/help-center/articles
**List articles (all statuses for company)**
- **Query Parameters**:
  - `page`: Page number (1-indexed)
  - `per_page`: Items per page (max 100)
  - `search`: Search in title and content
  - `category`: ACCOUNT_PROFILE, SECURITY_PRIVACY, BILLING_PAYMENTS, TECHNICAL_SUPPORT
  - `status`: PUBLISHED, DRAFT (COMPANY_ADMIN sees both by default)
  - `sort`: Sort field with direction (e.g., -views, title)
  - `company_id`: Filter by company (for COMPANY_ADMIN)
- **Role Access**: COMPANY_ADMIN sees PUBLISHED + DRAFT from their company
- **Response**: Paginated article list

### POST /api/help-center/articles
**Create article (starts in DRAFT)**
- **Request Body**:
  ```json
  {
    "category_id": "category-uuid",
    "title": "How to Reset Your Password",
    "content": "Full article content here...",
    "excerpt": "Brief summary (optional)"
  }
  ```
- **Notes**:
  - Always starts in DRAFT status
  - company_id from JWT
  - author_id from authenticated user
  - Category must be one of 4 global categories
- **Response**: 201 Created

### GET /api/help-center/articles/{id}
**Get single article**
- **Path Parameters**:
  - `id`: Article UUID
- **Notes**:
  - COMPANY_ADMIN can view PUBLISHED or DRAFT from their company
  - Increments views_count for PUBLISHED articles
- **Response**: Full article details

### PUT /api/help-center/articles/{id}
**Update article**
- **Path Parameters**:
  - `id`: Article UUID
- **Request Body** (all optional):
  ```json
  {
    "category_id": "new-category-uuid",
    "title": "Updated title",
    "content": "Updated content",
    "excerpt": "Updated excerpt"
  }
  ```
- **Notes**:
  - Can update DRAFT or PUBLISHED
  - Preserves published_at and views_count
  - Title must be unique per company
- **Response**: 200 OK

### DELETE /api/help-center/articles/{id}
**Delete draft article**
- **Path Parameters**:
  - `id`: Article UUID
- **Restriction**: Only DRAFT articles (PUBLISHED cannot be deleted)
- **Notes**: Soft delete
- **Response**: 200 OK

### POST /api/help-center/articles/{id}/publish
**Publish article**
- **Path Parameters**:
  - `id`: Article UUID
- **Restriction**: Only DRAFT articles
- **Notes**:
  - Sets published_at to current time
  - Fires ArticlePublished event
- **Response**: 200 OK

### POST /api/help-center/articles/{id}/unpublish
**Unpublish to draft**
- **Path Parameters**:
  - `id`: Article UUID
- **Restriction**: Only PUBLISHED articles
- **Notes**:
  - Sets published_at to null
  - Preserves views_count
- **Response**: 200 OK

### GET /api/help-center/categories
**List help center categories**
- **Response**: Array of 4 global categories

---

## 11. COMPANY MANAGEMENT

### GET /api/companies
**List companies (COMPANY_ADMIN sees only theirs)**
- **Query Parameters**:
  - `search`: Search by name
  - `status`: Filter by status
  - `industry_id`: Filter by industry UUID
  - `sort_by`: Sort field
  - `sort_direction`: asc/desc
  - `page`: Page number
  - `per_page`: Items per page
- **Role Access**: COMPANY_ADMIN sees only their own company
- **Response**: Paginated company list

### GET /api/companies/{company}
**Get company details**
- **Path Parameters**:
  - `company`: Company UUID or ID
- **Response**: Full company details

### PATCH /api/companies/{company}
**Update company**
- **Path Parameters**:
  - `company`: Company UUID or ID
- **Request Body** (all optional):
  ```json
  {
    "name": "Updated Company Name",
    "description": "Updated description",
    "website": "https://example.com",
    "industry_id": "industry-uuid",
    "status": "ACTIVE"
  }
  ```
- **Authorization**: PLATFORM_ADMIN or COMPANY_ADMIN owner
- **Response**: 200 OK

### GET /api/companies/minimal
**Get minimal company list**
- **Response**: Minimal company data (id, name)

### GET /api/company-industries
**List industries**
- **Response**: Array of industries

---

## 12. USER MANAGEMENT

### GET /api/users
**List company users**
- **Query Parameters**:
  - `search`: Search by email, user_code, or profile name
  - `status`: Filter by status (ACTIVE, INACTIVE, SUSPENDED)
  - `emailVerified`: Filter by email verification (true/false)
  - `role`: Filter by role code
  - `companyId`: Filter by company UUID
  - `recentActivity`: Filter users active in last 7 days
  - `createdAfter` / `createdBefore`: Date filters
  - `order_by`: Order by field
  - `order_direction`: asc/desc
  - `page`: Page number
  - `per_page`: Items per page (max 50)
- **Role Access**: COMPANY_ADMIN sees only users from their company
- **Response**: Paginated user list

### GET /api/users/{id}
**Get user details**
- **Path Parameters**:
  - `id`: User UUID
- **Authorization**: COMPANY_ADMIN can view users from their company
- **Response**: Full user details with roles and profile

### GET /api/users/me
**Get current user info**
- **Response**: Current user info with profile, roles, and statistics

### GET /api/users/me/profile
**Get current user profile**
- **Response**: Current user profile details

### PATCH /api/users/me/profile
**Update current user profile**
- **Request Body** (all optional):
  ```json
  {
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+1234567890",
    "bio": "User bio"
  }
  ```
- **Throttle**: 30 requests/hour
- **Response**: 200 OK

### PATCH /api/users/me/preferences
**Update user preferences**
- **Request Body** (all optional):
  ```json
  {
    "language": "en",
    "timezone": "UTC",
    "notifications_enabled": true,
    "email_notifications": true
  }
  ```
- **Throttle**: 50 requests/hour
- **Response**: 200 OK

### PATCH /api/users/{id}/status
**Update user status**
- **Path Parameters**:
  - `id`: User UUID
- **Request Body**:
  ```json
  {
    "status": "ACTIVE"
  }
  ```
- **Values**: ACTIVE, INACTIVE, SUSPENDED
- **Response**: 200 OK

---

## 13. ROLE MANAGEMENT (AGENT ASSIGNMENT)

### GET /api/roles
**List all available roles**
- **Response**: Array of roles (USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN)
- **Authorization**: PLATFORM_ADMIN or COMPANY_ADMIN

### POST /api/users/{userId}/roles
**Assign AGENT role to user**
- **Path Parameters**:
  - `userId`: User UUID
- **Request Body**:
  ```json
  {
    "roleCode": "AGENT",
    "companyId": "company-uuid"
  }
  ```
- **Notes**:
  - roleCode can be: USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN
  - companyId REQUIRED for AGENT or COMPANY_ADMIN
  - companyId MUST be null for USER or PLATFORM_ADMIN
  - Returns 200 if reactivated, 201 if newly assigned
- **Throttle**: 100 requests/hour
- **Response**: 201 Created or 200 OK

### DELETE /api/users/roles/{roleId}
**Remove role from user**
- **Path Parameters**:
  - `roleId`: UserRole UUID (not Role UUID)
- **Query Parameters**:
  - `reason`: Optional reason for removal (max 500 chars)
- **Authorization**: COMPANY_ADMIN can only remove roles from their own company
- **Notes**: Soft delete (deactivates role assignment)
- **Response**: 200 OK

---

## KEY NOTES FOR COMPANY_ADMIN

### 1. SCOPE
- COMPANY_ADMIN can **only** manage resources within their own company
- Cannot access other companies' data
- `company_id` is **automatically inferred** from JWT token (no need to send it)

### 2. TICKET MANAGEMENT
- View all tickets from their company
- Delete tickets (only in CLOSED status)
- Assign tickets to agents
- Same permissions as AGENT for ticket operations

### 3. CATEGORY MANAGEMENT
- Full CRUD operations on ticket categories
- Category names must be unique within company
- Cannot delete categories with active tickets (OPEN, PENDING, RESOLVED)

### 4. ANNOUNCEMENT MANAGEMENT
- Can create all types: ALERT, NEWS, INCIDENT, MAINTENANCE
- Can publish, schedule, archive, restore announcements
- Can view all announcement statuses (DRAFT, PUBLISHED, SCHEDULED, ARCHIVED)

### 5. HELP CENTER
- Can create articles (always start in DRAFT)
- Can view PUBLISHED and DRAFT articles from company
- Can publish/unpublish articles
- Can only delete DRAFT articles (not PUBLISHED)

### 6. AGENT MANAGEMENT
- Assign AGENT role via `POST /api/users/{userId}/roles`
- Must provide `companyId` when assigning AGENT role
- Can remove AGENT roles from company users

### 7. USER MANAGEMENT
- View all users from their company
- View user details and profiles
- Cannot view users from other companies

### 8. PAGINATION
- Most list endpoints support pagination: `page`, `per_page`
- Default: `page=1`, `per_page` varies (usually 15-20)
- Max `per_page` varies by endpoint (often 50-100)

### 9. FILTERING & SEARCH
- **Tickets**: status, category, agent, date range, search
- **Announcements**: status, type, date range, search
- **Articles**: status, category, search, sort
- **Users**: role, status, email verified, search

### 10. HTTP STATUS CODES
- **200**: Success
- **201**: Created
- **400**: Bad request
- **401**: Unauthenticated (missing/invalid token)
- **403**: Forbidden (insufficient permissions)
- **404**: Not found
- **422**: Validation error
- **500**: Internal server error

### 11. COMMON RESPONSE STRUCTURE
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

Paginated responses:
```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

---

## QUICK REFERENCE: COMPANY_ADMIN DASHBOARD ENDPOINTS

### For Agent Management Page
```
GET  /api/users?role=AGENT                    - List all agents
POST /api/users/{userId}/roles                - Assign AGENT role
DELETE /api/users/roles/{roleId}              - Remove AGENT role
```

### For Category Management Page
```
GET    /api/tickets/categories?company_id=... - List categories
POST   /api/tickets/categories                - Create category
PUT    /api/tickets/categories/{id}           - Update category
DELETE /api/tickets/categories/{id}           - Delete category
```

### For Ticket Dashboard
```
GET    /api/tickets                           - List tickets (with filters)
GET    /api/tickets/{ticket}                  - View ticket details
POST   /api/tickets/{ticket}/assign           - Assign to agent
POST   /api/tickets/{ticket}/resolve          - Mark resolved
POST   /api/tickets/{ticket}/close            - Close ticket
DELETE /api/tickets/{ticket}                  - Delete closed ticket
```

### For Announcements Page
```
GET    /api/announcements                     - List announcements
POST   /api/announcements/alerts              - Create alert
POST   /api/announcements/news                - Create news
POST   /api/v1/announcements/incidents        - Create incident
POST   /api/announcements/maintenance         - Create maintenance
POST   /api/announcements/{id}/publish        - Publish
POST   /api/announcements/{id}/schedule       - Schedule
GET    /api/announcements/schemas             - Get form schemas
```

### For Help Center Management
```
GET    /api/help-center/articles              - List articles
POST   /api/help-center/articles              - Create article
PUT    /api/help-center/articles/{id}         - Update article
POST   /api/help-center/articles/{id}/publish - Publish article
DELETE /api/help-center/articles/{id}         - Delete draft
```

### For Company Settings
```
GET   /api/companies                          - Get company info
PATCH /api/companies/{company}                - Update company
GET   /api/company-industries                 - List industries
```

### For User Management
```
GET   /api/users                              - List company users
GET   /api/users/{id}                         - Get user details
PATCH /api/users/{id}/status                  - Update user status
```
