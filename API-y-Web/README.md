# 🎯 Enterprise Helpdesk System

> **A professional, feature-first helpdesk platform built with Laravel 12, REST API, PostgreSQL 17, and AdminLTE v3**

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-17-336791?style=for-the-badge&logo=postgresql&logoColor=white)](https://postgresql.org)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://docker.com)
[![AdminLTE](https://img.shields.io/badge/AdminLTE-v3-00A6FB?style=for-the-badge&logo=bootstrap&logoColor=white)](https://adminlte.io)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](LICENSE)

---

## 📋 Table of Contents

- [✨ Features](#-features)
- [🏗️ Architecture](#️-architecture)
- [🚀 Quick Start](#-quick-start)
- [📡 API Documentation](#-api-documentation)
- [🧪 Testing](#-testing)
- [🔐 Security](#-security)
- [⚡ Performance](#-performance)
- [📚 Documentation](#-documentation)
- [🛠️ Tech Stack](#️-tech-stack)
- [👨‍💻 Author](#-author)
- [📄 License](#-license)

---

## ✨ Features

### 🎨 **Feature-First Architecture**
- Clean, maintainable, and scalable code organization
- Each feature is self-contained with its own controllers, services, models, and tests
- Easy to understand and extend

### 🔐 **Enterprise Authentication**
- Stateless JWT authentication (15min access + 7 day refresh tokens)
- Email verification and password reset flows
- Role-based access control (RBAC)
- Multi-tenant user isolation

### 🏢 **Multi-Tenant System**
- Company management with full isolation
- Company requests and approval workflow
- Company followers system
- Industry categorization

### 🎫 **Comprehensive Ticket Management**
- Ticket creation, assignment, and tracking
- Categories and priorities
- Response system with attachments
- Rating and feedback system
- Agent assignment and workload distribution

### 📢 **Content Management**
- Announcements system with targeting
- Help center articles with categories
- Rich text support
- File attachments

### 🚀 **Production-Ready**
- Docker containerized environment
- PostgreSQL with professional schema design (97% quality score)
- Redis caching and queue management
- Optimized for performance (OPcache, eager loading)
- Comprehensive test suite (174+ tests)

---

## 🏗️ Architecture

### **Feature-First Organization**

```
app/Features/
├── Authentication/          # JWT auth, login, register, verification
├── UserManagement/          # Users, profiles, roles (RBAC)
├── CompanyManagement/       # Multi-tenant companies
├── ContentManagement/       # Announcements, help articles
└── TicketManagement/        # Ticket system (in progress)
```

Each feature contains:
- **Controllers** - REST API endpoints
- **Services** - Business logic
- **Models** - Data and relationships
- **Policies** - Authorization rules
- **Resources** - JSON transformers
- **Requests** - Form validation
- **Migrations** - Database schema

### **PostgreSQL Multi-Schema Design**

**4 Schemas for Perfect Separation:**
- `auth` - Users, roles, permissions, sessions (13 tables)
- `business` - Companies, requests, industries (5 tables)
- `ticketing` - Tickets, responses, categories, ratings
- `audit` - System audit logs

**Professional Features:**
- ✅ UUID primary keys
- ✅ ENUM types for validation
- ✅ INET for IP addresses
- ✅ CITEXT for case-insensitive emails
- ✅ JSONB for flexible data
- ✅ CHECK constraints
- ✅ Partial indexes
- ✅ Soft deletes

**Quality Score:** 97% (Senior/Lead level)

### **REST API Design**

**Principles:**
- Resource-based URLs (`/api/tickets`, `/api/companies`)
- Proper HTTP verbs (GET, POST, PUT/PATCH, DELETE)
- Consistent JSON responses (camelCase keys)
- Comprehensive error handling
- Rate limiting on sensitive endpoints

**Response Format:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful"
}
```

---

## 🚀 Quick Start

### **Prerequisites**
- [Docker Desktop](https://www.docker.com/products/docker-desktop) installed
- [Git](https://git-scm.com/) installed

### **Installation**

```bash
# Clone the repository
git clone https://github.com/Lukehowland/helpdesk-system.git
cd helpdesk-system

# Start Docker containers
docker compose up -d

# Wait for services to be healthy (30-60 seconds)
docker compose ps

# Run migrations and seeders
docker compose exec app php artisan migrate --seed

# Optimize for performance
./scripts/optimize-performance.sh
```

### **Access the Application**

- 🌐 **Application:** http://localhost:8000
- 📧 **Mailpit (Email Testing):** http://localhost:8025
- 🗄️ **PostgreSQL:** localhost:5432
- 🔴 **Redis:** localhost:6379

### **Test the API**

```bash
# Register a new user
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "SecurePass123!",
    "passwordConfirmation": "SecurePass123!",
    "firstName": "John",
    "lastName": "Doe",
    "acceptsTerms": true,
    "acceptsPrivacyPolicy": true
  }'

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "SecurePass123!"
  }'

# Use JWT token for authenticated requests
curl -X GET http://localhost:8000/api/users/me \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

---

## 📡 API Documentation

### **Authentication Endpoints**

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/auth/register` | No | Register new user |
| POST | `/api/auth/login` | No | Login with credentials |
| POST | `/api/auth/refresh` | No | Refresh access token |
| POST | `/api/auth/logout` | JWT | Logout and revoke tokens |
| POST | `/api/auth/password-reset` | No | Request password reset |
| POST | `/api/auth/password-reset/confirm` | No | Confirm password reset |
| POST | `/api/auth/email/verify` | No | Verify email address |
| GET | `/api/auth/status` | JWT | Get auth status |
| GET | `/api/auth/sessions` | JWT | List active sessions |
| DELETE | `/api/auth/sessions/{id}` | JWT | Revoke specific session |

### **User Management**

| Method | Endpoint | Auth | Role | Description |
|--------|----------|------|------|-------------|
| GET | `/api/users/me` | JWT | Any | Get current user |
| GET | `/api/users/me/profile` | JWT | Any | Get user profile |
| PATCH | `/api/users/me/profile` | JWT | Any | Update profile |
| GET | `/api/users/{id}` | JWT | Any | View user details |
| GET | `/api/users` | JWT | Admin | List all users |
| PUT | `/api/users/{id}/status` | JWT | Admin | Change user status |

### **Company Management**

| Method | Endpoint | Auth | Role | Description |
|--------|----------|------|------|-------------|
| GET | `/api/companies/minimal` | No | - | List companies (public) |
| POST | `/api/companies` | JWT | Admin | Create company |
| GET | `/api/companies` | JWT | Admin | List all companies |
| GET | `/api/companies/explore` | JWT | Any | Explore companies |
| GET | `/api/companies/{id}` | JWT | Any | Get company details |
| PATCH | `/api/companies/{id}` | JWT | Owner | Update company |
| POST | `/api/companies/{id}/follow` | JWT | Any | Follow company |
| DELETE | `/api/companies/{id}/unfollow` | JWT | Any | Unfollow company |

### **Content Management**

| Method | Endpoint | Auth | Role | Description |
|--------|----------|------|------|-------------|
| GET | `/api/announcements` | JWT | Any | List announcements |
| POST | `/api/announcements` | JWT | Admin | Create announcement |
| GET | `/api/announcements/{id}` | JWT | Any | View announcement |
| PATCH | `/api/announcements/{id}` | JWT | Admin | Update announcement |
| DELETE | `/api/announcements/{id}` | JWT | Admin | Delete announcement |
| GET | `/api/help-center/articles` | JWT | Any | List help articles |
| GET | `/api/help-center/articles/{id}` | JWT | Any | View article |

---

## 🧪 Testing

### **Run Tests**

```bash
# Run all tests
docker compose exec app php artisan test

# Run specific test suite
docker compose exec app php artisan test tests/Feature/Authentication

# Run with coverage
docker compose exec app php artisan test --coverage

# Run in parallel (faster)
docker compose exec app php artisan test --parallel
```

### **Test Structure**

```
tests/
├── Feature/               # Integration tests
│   ├── Authentication/
│   ├── UserManagement/
│   └── CompanyManagement/
└── Unit/                  # Unit tests
    └── Services/
```

**Test Stats:**
- ✅ 174+ tests passing
- ✅ Feature tests for all API endpoints
- ✅ Unit tests for business logic
- ✅ Separate test database
- ✅ Automated CI/CD ready

---

## 🔐 Security

### **Authentication**
- ✅ Stateless JWT tokens (no server-side sessions)
- ✅ Secure password hashing (bcrypt)
- ✅ Email verification required
- ✅ Password reset with secure tokens
- ✅ Refresh token rotation

### **Authorization**
- ✅ Role-based access control (RBAC)
- ✅ Policy-based authorization
- ✅ Multi-tenant data isolation
- ✅ Middleware protection

### **Security Best Practices**
- ✅ Rate limiting on sensitive endpoints
- ✅ CORS configuration
- ✅ SQL injection protection (Eloquent ORM)
- ✅ XSS protection
- ✅ CSRF protection
- ✅ Input validation and sanitization
- ✅ Environment-based error messages

---

## ⚡ Performance

### **Optimization Features**

**Backend:**
- ✅ OPcache enabled with optimized settings
- ✅ Redis caching (config, routes, views)
- ✅ Stateless JWT (no DB lookups per request)
- ✅ Eager loading to prevent N+1 queries
- ✅ Database query optimization
- ✅ Partial indexes on PostgreSQL

**Infrastructure:**
- ✅ Docker with health checks
- ✅ PHP-FPM with static pool
- ✅ Nginx with optimized config
- ✅ PostgreSQL with tuned settings
- ✅ Redis for session and cache

### **Performance Metrics**

```
Cold start:        ~200-500ms
Warm requests:     <165ms
Database queries:  Optimized with eager loading
Cache hit rate:    >95%
```

### **Run Performance Optimization**

```bash
./scripts/optimize-performance.sh
```

---

## 📚 Documentation

### **Project Documentation**

Located in `/documentacion/`:

- **ESTADO_COMPLETO_PROYECTO.md** - Complete project status and roadmap
- **GUIA_ESTRUCTURA_CARPETAS_PROYECTO.md** - Feature-first architecture guide
- **OPINION_PROFESIONAL_MODELADO_V7.md** - Database design analysis (97% score)
- **COMPANY_ADMIN_API_ENDPOINTS.md** - Company API documentation
- **COMPANY_ADMIN_API_EXAMPLES.md** - API usage examples

### **Development Rules**

Located in `.cursor/rules/`:

- **backend-architecture.mdc** - Backend patterns and conventions
- **frontend-architecture.mdc** - Frontend structure (React/Inertia planned)
- **blade-components-jquery.mdc** - jQuery loading patterns
- **adminlte-forms-validation.mdc** - Form validation patterns
- **adminlte-buttons.mdc** - Button styling patterns

### **Main Guide**

- **CLAUDE.md** - Complete development guide for working with this codebase

---

## 🛠️ Tech Stack

### **Backend**
- **Framework:** Laravel 12 (latest)
- **Language:** PHP 8.3
- **API:** REST with OpenAPI 3.0
- **Database:** PostgreSQL 17 (multi-schema)
- **Cache/Queue:** Redis 8
- **Authentication:** JWT (Firebase PHP-JWT)

### **Frontend**
- **Current:** AdminLTE v3 + Blade + jQuery
- **Build Tool:** Vite 7
- **JavaScript:** Alpine.js 3.15
- **Planned:** React 19 + TypeScript + Inertia.js

### **Infrastructure**
- **Containerization:** Docker + Docker Compose
- **Web Server:** Nginx (Alpine)
- **PHP Runtime:** PHP-FPM 8.3
- **Email Testing:** Mailpit
- **Queue Worker:** Laravel Queue (Redis)
- **Task Scheduler:** Laravel Scheduler

### **Development Tools**
- **Code Quality:** Laravel Pint (PSR-12)
- **Testing:** PHPUnit 11.5
- **API Documentation:** L5 Swagger (OpenAPI)
- **Database:** PostgreSQL CLI tools

---

## 🎯 Development Commands

### **Docker Operations**
```bash
# Start environment
docker compose up -d

# Stop environment
docker compose down

# View logs
docker compose logs -f [service]

# Rebuild containers
docker compose up -d --build
```

### **Laravel Commands**
```bash
# Artisan commands
docker compose exec app php artisan [command]

# Run migrations
docker compose exec app php artisan migrate

# Seed database
docker compose exec app php artisan db:seed

# Clear caches
docker compose exec app php artisan optimize:clear

# Cache for performance
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

### **Code Quality**
```bash
# Format code (Laravel Pint)
docker compose exec app ./vendor/bin/pint

# Run tests
docker compose exec app php artisan test

# Run specific test
docker compose exec app php artisan test --filter=TestClassName
```

### **Database Access**
```bash
# PostgreSQL CLI
docker compose exec postgres psql -U helpdesk -d helpdesk

# Redis CLI
docker compose exec redis redis-cli
```

---

## 📊 Project Status

**Current Version:** 1.0-beta
**Status:** Active Development
**Last Updated:** November 2024

### **✅ Completed (100%)**
- ✅ REST API infrastructure (20+ endpoints)
- ✅ JWT authentication system
- ✅ User management with RBAC
- ✅ Company management (multi-tenant)
- ✅ Content management (announcements, articles)
- ✅ Email verification and password reset
- ✅ PostgreSQL multi-schema database
- ✅ Docker development environment
- ✅ Comprehensive test suite (174+ tests)
- ✅ AdminLTE v3 frontend integration
- ✅ OpenAPI/Swagger documentation

### **🚧 In Progress**
- ⏳ Ticket management system (80%)
- ⏳ Agent dashboard
- ⏳ Real-time notifications

### **📋 Planned**
- 📅 React + Inertia.js migration
- 📅 WebSocket integration
- 📅 Mobile app (React Native)
- 📅 Advanced reporting
- 📅 AI-powered ticket routing

---

## 🤝 Contributing

This is currently a learning/showcase project. Contributions, issues, and feature requests are welcome!

### **How to Contribute**
1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### **Code Quality Standards**
- Follow PSR-12 coding standards
- Write tests for new features
- Update documentation
- Use feature-first architecture
- Maintain type safety (strict types)

---

## 👨‍💻 Author

**Luke De La Quintana**
*Backend Developer*

- 🌐 GitHub: [@Lukehowland](https://github.com/Lukehowland)
- 📧 Email: [lukqs05@gmail.com](mailto:lukqs05@gmail.com)
- 🆔 ID: 62119184

### **About This Project**

This project was built as a demonstration of:
- ✨ Enterprise-grade Laravel architecture
- 🏗️ Feature-first organization at scale
- 🔐 Professional authentication and authorization
- 🗄️ Advanced PostgreSQL database design
- ⚡ Performance optimization techniques
- 🧪 Comprehensive testing practices
- 🐳 Production-ready Docker setup

**Key Achievements:**
- 📊 97% database design quality score (Senior/Lead level)
- ✅ 174+ automated tests passing
- 🚀 Complete REST API migration from GraphQL
- 📚 Comprehensive documentation
- ⚡ Optimized performance (<165ms response time)

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- **Laravel Team** - For the amazing framework
- **AdminLTE** - For the professional admin template
- **PostgreSQL Team** - For the robust database system
- **Docker Team** - For containerization technology
- **Open Source Community** - For the incredible tools and libraries

---

## 📞 Support

If you find this project helpful, please consider:

- ⭐ Starring the repository
- 🐛 Reporting bugs and issues
- 💡 Suggesting new features
- 📖 Improving documentation
- 🤝 Contributing code

---

<div align="center">

**Built with ❤️ by [Luke De La Quintana](https://github.com/Lukehowland)**

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-17-336791?style=flat-square&logo=postgresql)](https://postgresql.org)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=flat-square&logo=docker)](https://docker.com)

**Enterprise Helpdesk System** | Professional • Scalable • Production-Ready

</div>
