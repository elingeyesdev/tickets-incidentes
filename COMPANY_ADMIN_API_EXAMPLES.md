# COMPANY_ADMIN API REQUEST/RESPONSE EXAMPLES

## Authentication Header
All requests must include:
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

---

## TICKET CATEGORY EXAMPLES

### Create Category
**Request:**
```http
POST /api/tickets/categories
Content-Type: application/json

{
  "name": "Technical Support",
  "description": "Technical issues and troubleshooting"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Category created successfully",
  "data": {
    "id": "9c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
    "company_id": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
    "name": "Technical Support",
    "description": "Technical issues and troubleshooting",
    "is_active": true,
    "active_tickets_count": 0,
    "created_at": "2025-11-17T10:00:00Z",
    "updated_at": "2025-11-17T10:00:00Z"
  }
}
```

### List Categories
**Request:**
```http
GET /api/tickets/categories?company_id=8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f&is_active=true
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "9c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
      "company_id": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
      "name": "Technical Support",
      "description": "Technical issues and troubleshooting",
      "is_active": true,
      "active_tickets_count": 15,
      "created_at": "2025-11-17T10:00:00Z",
      "updated_at": "2025-11-17T10:00:00Z"
    },
    {
      "id": "7d2c4f0e-1a3b-4c5d-6e7f-8a9b0c1d2e3f",
      "company_id": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
      "name": "Billing Questions",
      "description": "Billing and payment inquiries",
      "is_active": true,
      "active_tickets_count": 8,
      "created_at": "2025-11-16T09:00:00Z",
      "updated_at": "2025-11-16T09:00:00Z"
    }
  ]
}
```

### Update Category
**Request:**
```http
PUT /api/tickets/categories/9c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f
Content-Type: application/json

{
  "name": "Advanced Technical Support",
  "is_active": true
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Category updated successfully",
  "data": {
    "id": "9c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
    "company_id": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
    "name": "Advanced Technical Support",
    "description": "Technical issues and troubleshooting",
    "is_active": true,
    "active_tickets_count": 15,
    "created_at": "2025-11-17T10:00:00Z",
    "updated_at": "2025-11-17T11:30:00Z"
  }
}
```

---

## TICKET MANAGEMENT EXAMPLES

### List Tickets with Filters
**Request:**
```http
GET /api/tickets?status=OPEN&status=PENDING&category_id=9c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f&search=login&page=1&per_page=15
```

**Response (200):**
```json
{
  "data": [
    {
      "id": "1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d",
      "ticket_code": "TKT-2025-00042",
      "title": "Cannot login to account",
      "description": "Getting error message when trying to login",
      "status": "OPEN",
      "priority": "MEDIUM",
      "category": {
        "id": "9c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
        "name": "Technical Support"
      },
      "created_by": {
        "id": "user-uuid",
        "email": "user@example.com",
        "user_code": "USR-001"
      },
      "owner_agent": null,
      "last_response_at": null,
      "last_response_author_type": null,
      "created_at": "2025-11-17T09:30:00Z",
      "updated_at": "2025-11-17T09:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 23,
    "last_page": 2,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "/api/tickets?page=1",
    "last": "/api/tickets?page=2",
    "prev": null,
    "next": "/api/tickets?page=2"
  }
}
```

### Get Single Ticket
**Request:**
```http
GET /api/tickets/TKT-2025-00042
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": "1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d",
    "ticket_code": "TKT-2025-00042",
    "title": "Cannot login to account",
    "description": "Getting error message when trying to login with correct credentials",
    "status": "OPEN",
    "priority": "MEDIUM",
    "category": {
      "id": "9c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
      "name": "Technical Support",
      "description": "Technical issues and troubleshooting"
    },
    "created_by": {
      "id": "user-uuid",
      "email": "user@example.com",
      "user_code": "USR-001",
      "profile": {
        "first_name": "John",
        "last_name": "Doe"
      }
    },
    "owner_agent": null,
    "responses_count": 0,
    "attachments_count": 1,
    "last_response_at": null,
    "last_response_author_type": null,
    "resolved_at": null,
    "closed_at": null,
    "created_at": "2025-11-17T09:30:00Z",
    "updated_at": "2025-11-17T09:30:00Z"
  }
}
```

### Assign Ticket to Agent
**Request:**
```http
POST /api/tickets/TKT-2025-00042/assign
Content-Type: application/json

{
  "owner_agent_id": "agent-uuid-here"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Ticket assigned successfully",
  "data": {
    "id": "1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d",
    "ticket_code": "TKT-2025-00042",
    "title": "Cannot login to account",
    "status": "PENDING",
    "owner_agent": {
      "id": "agent-uuid-here",
      "email": "agent@company.com",
      "user_code": "AGT-005",
      "profile": {
        "first_name": "Jane",
        "last_name": "Smith"
      }
    }
  }
}
```

### Resolve Ticket
**Request:**
```http
POST /api/tickets/TKT-2025-00042/resolve
Content-Type: application/json

{
  "resolution_notes": "Issue resolved by resetting password"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Ticket resolved successfully",
  "data": {
    "id": "1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d",
    "ticket_code": "TKT-2025-00042",
    "status": "RESOLVED",
    "resolved_at": "2025-11-17T11:00:00Z"
  }
}
```

### Close Ticket
**Request:**
```http
POST /api/tickets/TKT-2025-00042/close
Content-Type: application/json

{
  "closing_notes": "User confirmed issue resolved"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Ticket closed successfully",
  "data": {
    "id": "1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d",
    "ticket_code": "TKT-2025-00042",
    "status": "CLOSED",
    "closed_at": "2025-11-17T12:00:00Z"
  }
}
```

---

## TICKET RESPONSES EXAMPLES

### List Responses
**Request:**
```http
GET /api/tickets/TKT-2025-00042/responses
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "response-uuid-1",
      "ticket_id": "1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d",
      "content": "I'm having trouble logging in. Getting 'Invalid credentials' error.",
      "author_type": "user",
      "author": {
        "id": "user-uuid",
        "email": "user@example.com",
        "user_code": "USR-001",
        "profile": {
          "first_name": "John",
          "last_name": "Doe"
        }
      },
      "attachments_count": 1,
      "created_at": "2025-11-17T09:30:00Z",
      "updated_at": "2025-11-17T09:30:00Z"
    },
    {
      "id": "response-uuid-2",
      "ticket_id": "1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d",
      "content": "Hello! I'll help you with this. Have you tried resetting your password?",
      "author_type": "agent",
      "author": {
        "id": "agent-uuid",
        "email": "agent@company.com",
        "user_code": "AGT-005",
        "profile": {
          "first_name": "Jane",
          "last_name": "Smith"
        }
      },
      "attachments_count": 0,
      "created_at": "2025-11-17T10:15:00Z",
      "updated_at": "2025-11-17T10:15:00Z"
    }
  ]
}
```

### Add Response
**Request:**
```http
POST /api/tickets/TKT-2025-00042/responses
Content-Type: application/json

{
  "content": "I've investigated the issue. Please try resetting your password using the link I'll send to your email."
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Response added successfully",
  "data": {
    "id": "new-response-uuid",
    "ticket_id": "1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d",
    "content": "I've investigated the issue. Please try resetting your password using the link I'll send to your email.",
    "author_type": "agent",
    "author": {
      "id": "agent-uuid",
      "email": "agent@company.com",
      "user_code": "AGT-005"
    },
    "attachments_count": 0,
    "created_at": "2025-11-17T10:45:00Z",
    "updated_at": "2025-11-17T10:45:00Z"
  }
}
```

---

## ANNOUNCEMENT EXAMPLES

### Create Alert
**Request:**
```http
POST /api/announcements/alerts
Content-Type: application/json

{
  "title": "Security Alert: Suspicious Activity Detected",
  "content": "We have detected unusual login attempts on several accounts. Please review your recent activity and change your password immediately if you notice anything suspicious.",
  "metadata": {
    "urgency": "CRITICAL",
    "alert_type": "security",
    "message": "Change your password immediately and enable two-factor authentication",
    "action_required": true,
    "action_description": "Go to Settings > Security > Change Password"
  },
  "action": "publish"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Alert announcement created and published successfully",
  "data": {
    "id": "alert-uuid",
    "company_id": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
    "type": "ALERT",
    "title": "Security Alert: Suspicious Activity Detected",
    "content": "We have detected unusual login attempts...",
    "status": "PUBLISHED",
    "metadata": {
      "urgency": "CRITICAL",
      "alert_type": "security",
      "message": "Change your password immediately and enable two-factor authentication",
      "action_required": true,
      "action_description": "Go to Settings > Security > Change Password"
    },
    "published_at": "2025-11-17T10:00:00Z",
    "created_at": "2025-11-17T10:00:00Z",
    "updated_at": "2025-11-17T10:00:00Z"
  }
}
```

### Create News
**Request:**
```http
POST /api/announcements/news
Content-Type: application/json

{
  "title": "New Feature: Dark Mode Now Available",
  "body": "We're excited to announce that dark mode is now available across all our applications. You can enable it in your account settings under Appearance preferences.",
  "metadata": {
    "news_type": "feature_release",
    "target_audience": ["users", "agents"],
    "summary": "Dark mode is now available for all users",
    "call_to_action": {
      "text": "Enable Dark Mode",
      "url": "https://app.example.com/settings/appearance"
    }
  },
  "action": "publish"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "News announcement created and published successfully",
  "data": {
    "id": "news-uuid",
    "company_id": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
    "type": "NEWS",
    "title": "New Feature: Dark Mode Now Available",
    "content": "We're excited to announce that dark mode is now available...",
    "status": "PUBLISHED",
    "metadata": {
      "news_type": "feature_release",
      "target_audience": ["users", "agents"],
      "summary": "Dark mode is now available for all users",
      "call_to_action": {
        "text": "Enable Dark Mode",
        "url": "https://app.example.com/settings/appearance"
      }
    },
    "published_at": "2025-11-17T11:00:00Z",
    "created_at": "2025-11-17T11:00:00Z",
    "updated_at": "2025-11-17T11:00:00Z"
  }
}
```

### Create Incident
**Request:**
```http
POST /api/v1/announcements/incidents
Content-Type: application/json

{
  "title": "Service Disruption: API Gateway Experiencing Issues",
  "content": "Our API gateway is currently experiencing intermittent connectivity issues. Our engineering team is actively investigating the issue. We apologize for any inconvenience.",
  "urgency": "HIGH",
  "is_resolved": false,
  "started_at": "2025-11-17T08:30:00Z",
  "affected_services": ["API Gateway", "REST API", "Mobile App"],
  "action": "publish"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Incident announcement created successfully",
  "data": {
    "id": "incident-uuid",
    "company_id": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
    "type": "INCIDENT",
    "title": "Service Disruption: API Gateway Experiencing Issues",
    "content": "Our API gateway is currently experiencing intermittent connectivity issues...",
    "status": "PUBLISHED",
    "metadata": {
      "urgency": "HIGH",
      "is_resolved": false,
      "started_at": "2025-11-17T08:30:00Z",
      "ended_at": null,
      "resolved_at": null,
      "resolution_content": null,
      "affected_services": ["API Gateway", "REST API", "Mobile App"]
    },
    "published_at": "2025-11-17T08:35:00Z",
    "created_at": "2025-11-17T08:35:00Z",
    "updated_at": "2025-11-17T08:35:00Z"
  }
}
```

### Resolve Incident
**Request:**
```http
POST /api/v1/announcements/incidents/incident-uuid/resolve
Content-Type: application/json

{
  "resolution_content": "The issue was caused by a network configuration error. Our team has corrected the configuration and all services are now operating normally. We've implemented additional monitoring to prevent similar issues in the future.",
  "ended_at": "2025-11-17T10:15:00Z"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Incident resolved successfully",
  "data": {
    "id": "incident-uuid",
    "type": "INCIDENT",
    "title": "Service Disruption: API Gateway Experiencing Issues",
    "status": "PUBLISHED",
    "metadata": {
      "urgency": "HIGH",
      "is_resolved": true,
      "started_at": "2025-11-17T08:30:00Z",
      "ended_at": "2025-11-17T10:15:00Z",
      "resolved_at": "2025-11-17T10:20:00Z",
      "resolution_content": "The issue was caused by a network configuration error...",
      "affected_services": ["API Gateway", "REST API", "Mobile App"]
    },
    "updated_at": "2025-11-17T10:20:00Z"
  }
}
```

### Create Maintenance
**Request:**
```http
POST /api/announcements/maintenance
Content-Type: application/json

{
  "title": "Scheduled Database Maintenance",
  "content": "We will be performing scheduled database maintenance to improve system performance. During this time, the application may be temporarily unavailable. We apologize for any inconvenience.",
  "urgency": "HIGH",
  "scheduled_start": "2025-11-20T02:00:00Z",
  "scheduled_end": "2025-11-20T04:00:00Z",
  "is_emergency": false,
  "affected_services": ["Web Application", "Mobile App", "API"],
  "action": "schedule",
  "scheduled_for": "2025-11-19T18:00:00Z"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Maintenance announcement created and scheduled successfully",
  "data": {
    "id": "maintenance-uuid",
    "company_id": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
    "type": "MAINTENANCE",
    "title": "Scheduled Database Maintenance",
    "content": "We will be performing scheduled database maintenance...",
    "status": "SCHEDULED",
    "metadata": {
      "urgency": "HIGH",
      "scheduled_start": "2025-11-20T02:00:00Z",
      "scheduled_end": "2025-11-20T04:00:00Z",
      "actual_start": null,
      "actual_end": null,
      "is_emergency": false,
      "affected_services": ["Web Application", "Mobile App", "API"],
      "scheduled_for": "2025-11-19T18:00:00Z"
    },
    "published_at": null,
    "created_at": "2025-11-17T12:00:00Z",
    "updated_at": "2025-11-17T12:00:00Z"
  }
}
```

### List Announcements
**Request:**
```http
GET /api/announcements?status=PUBLISHED&type=ALERT&type=INCIDENT&page=1&per_page=20
```

**Response (200):**
```json
{
  "data": [
    {
      "id": "alert-uuid",
      "company_id": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
      "type": "ALERT",
      "title": "Security Alert: Suspicious Activity Detected",
      "content": "We have detected unusual login attempts...",
      "status": "PUBLISHED",
      "metadata": {
        "urgency": "CRITICAL",
        "alert_type": "security"
      },
      "published_at": "2025-11-17T10:00:00Z",
      "created_at": "2025-11-17T10:00:00Z"
    },
    {
      "id": "incident-uuid",
      "company_id": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
      "type": "INCIDENT",
      "title": "Service Disruption: API Gateway Experiencing Issues",
      "content": "Our API gateway is currently experiencing...",
      "status": "PUBLISHED",
      "metadata": {
        "urgency": "HIGH",
        "is_resolved": true
      },
      "published_at": "2025-11-17T08:35:00Z",
      "created_at": "2025-11-17T08:35:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 2,
    "last_page": 1
  }
}
```

---

## HELP CENTER EXAMPLES

### Create Article
**Request:**
```http
POST /api/help-center/articles
Content-Type: application/json

{
  "category_id": "category-uuid",
  "title": "How to Reset Your Password",
  "content": "If you've forgotten your password or need to reset it for security reasons, follow these steps:\n\n1. Go to the login page\n2. Click on 'Forgot Password'\n3. Enter your email address\n4. Check your email for the reset link\n5. Click the link and create a new password\n\nYour new password must be at least 8 characters long and include uppercase, lowercase, numbers, and special characters.",
  "excerpt": "Step-by-step guide to resetting your password"
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": "article-uuid",
    "company_id": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
    "category": {
      "id": "category-uuid",
      "name": "Account & Profile",
      "code": "ACCOUNT_PROFILE"
    },
    "title": "How to Reset Your Password",
    "content": "If you've forgotten your password...",
    "excerpt": "Step-by-step guide to resetting your password",
    "status": "DRAFT",
    "author": {
      "id": "admin-uuid",
      "email": "admin@company.com",
      "user_code": "ADM-001"
    },
    "views_count": 0,
    "published_at": null,
    "created_at": "2025-11-17T13:00:00Z",
    "updated_at": "2025-11-17T13:00:00Z"
  }
}
```

### Publish Article
**Request:**
```http
POST /api/help-center/articles/article-uuid/publish
```

**Response (200):**
```json
{
  "success": true,
  "message": "Article published successfully",
  "data": {
    "id": "article-uuid",
    "title": "How to Reset Your Password",
    "status": "PUBLISHED",
    "published_at": "2025-11-17T13:30:00Z",
    "updated_at": "2025-11-17T13:30:00Z"
  }
}
```

### List Articles
**Request:**
```http
GET /api/help-center/articles?status=PUBLISHED&category=ACCOUNT_PROFILE&search=password&page=1&per_page=10
```

**Response (200):**
```json
{
  "data": [
    {
      "id": "article-uuid",
      "company_id": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
      "category": {
        "id": "category-uuid",
        "name": "Account & Profile",
        "code": "ACCOUNT_PROFILE"
      },
      "title": "How to Reset Your Password",
      "excerpt": "Step-by-step guide to resetting your password",
      "status": "PUBLISHED",
      "author": {
        "id": "admin-uuid",
        "email": "admin@company.com",
        "profile": {
          "first_name": "Admin",
          "last_name": "User"
        }
      },
      "views_count": 127,
      "published_at": "2025-11-17T13:30:00Z",
      "created_at": "2025-11-17T13:00:00Z",
      "updated_at": "2025-11-17T13:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 1,
    "last_page": 1
  }
}
```

---

## USER & ROLE MANAGEMENT EXAMPLES

### List Company Users
**Request:**
```http
GET /api/users?role=AGENT&status=ACTIVE&page=1&per_page=20
```

**Response (200):**
```json
{
  "data": [
    {
      "id": "agent-uuid-1",
      "email": "agent1@company.com",
      "user_code": "AGT-001",
      "status": "ACTIVE",
      "email_verified_at": "2025-11-10T09:00:00Z",
      "profile": {
        "first_name": "Jane",
        "last_name": "Smith",
        "phone": "+1234567890"
      },
      "roleContexts": [
        {
          "role": {
            "code": "AGENT",
            "name": "Agent"
          },
          "company": {
            "id": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
            "name": "Acme Corporation"
          }
        }
      ],
      "created_at": "2025-11-10T08:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 5,
    "last_page": 1
  }
}
```

### Assign AGENT Role
**Request:**
```http
POST /api/users/user-uuid/roles
Content-Type: application/json

{
  "roleCode": "AGENT",
  "companyId": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Role assigned successfully",
  "data": {
    "id": "user-role-uuid",
    "user_id": "user-uuid",
    "role": {
      "id": "role-uuid",
      "code": "AGENT",
      "name": "Agent"
    },
    "company": {
      "id": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
      "name": "Acme Corporation"
    },
    "is_active": true,
    "assigned_at": "2025-11-17T14:00:00Z"
  }
}
```

### Remove Role
**Request:**
```http
DELETE /api/users/roles/user-role-uuid?reason=Employee%20left%20company
```

**Response (200):**
```json
{
  "success": true,
  "message": "Role removed successfully"
}
```

---

## ERROR RESPONSE EXAMPLES

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Forbidden - user does not have COMPANY_ADMIN role or category belongs to different company"
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": [
      "The name field is required.",
      "The name must be at least 3 characters."
    ],
    "email": [
      "The email has already been taken."
    ]
  }
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Category not found"
}
```

### 400 Bad Request
```json
{
  "success": false,
  "message": "Cannot delete category - it has active tickets assigned to it"
}
```
