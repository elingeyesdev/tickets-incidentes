# Dashboard System - Quick Start Guide

## Files Created Summary

### Controllers (5 files) ✅
```
app/Http/Controllers/Dashboard/
├── DashboardController.php           - Main redirect controller
├── PlatformAdminController.php       - Platform admin dashboard
├── CompanyAdminController.php        - Company admin dashboard
├── AgentController.php               - Agent dashboard
└── UserController.php                - User dashboard
```

### Views (8 files) ✅
```
resources/views/
├── layouts/
│   └── authenticated.blade.php       - Main authenticated layout
└── app/
    ├── shared/
    │   ├── navbar.blade.php          - Top navigation bar
    │   └── sidebar.blade.php         - Dynamic sidebar menu
    ├── platform-admin/
    │   └── dashboard.blade.php       - Platform admin dashboard view
    ├── company-admin/
    │   └── dashboard.blade.php       - Company admin dashboard view
    ├── agent/
    │   └── dashboard.blade.php       - Agent dashboard view
    └── user/
        └── dashboard.blade.php       - User dashboard view
```

### Middleware (1 file) ✅
```
app/Http/Middleware/
└── EnsureRoleSelected.php            - Ensures active role is selected
```

### Updated Files (3 files) ✅
```
routes/web.php                        - Dashboard routes added
bootstrap/app.php                     - Middleware alias registered
resources/views/public/login.blade.php - Redirect path updated
```

---

## How to Test

### 1. Access Login Page
Navigate to: `http://localhost/login` or `http://helpdesk.test/login`

### 2. Login
- Enter valid credentials
- You should be redirected to `/app/dashboard`

### 3. Dashboard Redirect
The system will:
1. Read your JWT token from localStorage
2. Extract `active_role.code` from the token
3. Redirect you to the appropriate dashboard:
   - Platform Admin → `/app/admin/dashboard`
   - Company Admin → `/app/company/dashboard`
   - Agent → `/app/agent/dashboard`
   - User → `/app/user/dashboard`

### 4. Verify Dashboard
Check that:
- ✅ Statistics cards display
- ✅ Sidebar shows correct menu for your role
- ✅ Navbar shows your name and role
- ✅ Logout button works

---

## Testing Different Roles

To test different role dashboards, you need users with different roles in your database.

### Create Test Users (if needed)

#### Option 1: Using Tinker
```bash
php artisan tinker
```

```php
// Create Platform Admin
$user = User::create([
    'name' => 'Platform Admin',
    'email' => 'admin@helpdesk.test',
    'password' => bcrypt('password123')
]);
$user->roles()->attach(Role::where('role_code', 'PLATFORM_ADMIN')->first());

// Create Company Admin
$user = User::create([
    'name' => 'Company Admin',
    'email' => 'company@helpdesk.test',
    'password' => bcrypt('password123')
]);
$user->roles()->attach(Role::where('role_code', 'COMPANY_ADMIN')->first(), ['company_id' => $companyId]);

// Create Agent
$user = User::create([
    'name' => 'Agent User',
    'email' => 'agent@helpdesk.test',
    'password' => bcrypt('password123')
]);
$user->roles()->attach(Role::where('role_code', 'AGENT')->first(), ['company_id' => $companyId]);

// Create Regular User
$user = User::create([
    'name' => 'Regular User',
    'email' => 'user@helpdesk.test',
    'password' => bcrypt('password123')
]);
$user->roles()->attach(Role::where('role_code', 'USER')->first());
```

---

## URL Structure

### Public Routes
- `/login` - Login page
- `/register` - Registration page
- `/forgot-password` - Password reset

### Authenticated Routes (require JWT)
- `/app/dashboard` - Main redirect (goes to role-specific dashboard)

### Platform Admin Routes (require PLATFORM_ADMIN role)
- `/app/admin/dashboard` - Platform admin dashboard

### Company Admin Routes (require COMPANY_ADMIN role)
- `/app/company/dashboard` - Company admin dashboard

### Agent Routes (require AGENT role)
- `/app/agent/dashboard` - Agent dashboard

### User Routes (require USER role)
- `/app/user/dashboard` - User dashboard

---

## Troubleshooting

### "Session expired" or redirects to login
**Problem**: JWT token not found or expired
**Solution**:
1. Check browser console for errors
2. Verify `access_token` exists in localStorage
3. Try logging in again

### "No active role" or redirects to role selector
**Problem**: JWT doesn't contain `active_role` claim
**Solution**:
1. Backend must add `active_role` to JWT during login
2. See backend integration guide in main report

### 403 Forbidden error
**Problem**: Trying to access dashboard for a role you don't have
**Solution**:
1. Verify your user has the correct role in database
2. Check JWT payload contains correct role

### Sidebar menu not showing
**Problem**: JavaScript error or role detection failed
**Solution**:
1. Open browser console and check for errors
2. Verify `getUserFromJWT()` function is available
3. Check JWT payload structure

### Statistics show 0 or mock data
**Expected**: Dashboards currently use mock data
**Solution**: Integrate with real API endpoints (see main report)

---

## Browser Console Testing

### Check JWT Token
```javascript
// Get access token
localStorage.getItem('access_token')

// Parse JWT payload
const token = localStorage.getItem('access_token');
if (token) {
    const payload = JSON.parse(atob(token.split('.')[1]));
    console.log('JWT Payload:', payload);
    console.log('Active Role:', payload.active_role);
}
```

### Test getUserFromJWT Function
```javascript
// Should return user data
const userData = getUserFromJWT();
console.log('User Data:', userData);
console.log('Active Role Code:', userData.activeRole.code);
```

### Test Logout
```javascript
// Should clear tokens and redirect
logout();
```

---

## Next Steps After Testing

### If Everything Works:
1. ✅ Integrate real API endpoints for statistics
2. ✅ Create role selector page for users with multiple roles
3. ✅ Build out role-specific features (users management, tickets, etc.)
4. ✅ Add real-time updates for statistics
5. ✅ Implement search and filtering

### If Issues Found:
1. Check DASHBOARD_IMPLEMENTATION_REPORT.md for detailed documentation
2. Verify all files are in correct locations
3. Check Laravel logs: `storage/logs/laravel.log`
4. Check browser console for JavaScript errors
5. Verify middleware is registered in `bootstrap/app.php`

---

## Quick Verification Checklist

Run these commands to verify installation:

```bash
# 1. Check controllers exist
ls app/Http/Controllers/Dashboard/

# Expected output:
# DashboardController.php
# PlatformAdminController.php
# CompanyAdminController.php
# AgentController.php
# UserController.php

# 2. Check views exist
ls resources/views/app/*/dashboard.blade.php

# Expected output:
# resources/views/app/agent/dashboard.blade.php
# resources/views/app/company-admin/dashboard.blade.php
# resources/views/app/platform-admin/dashboard.blade.php
# resources/views/app/user/dashboard.blade.php

# 3. Check middleware exists
ls app/Http/Middleware/EnsureRoleSelected.php

# Expected output:
# app/Http/Middleware/EnsureRoleSelected.php

# 4. Check routes are registered
php artisan route:list | grep dashboard

# Expected output should include:
# GET|HEAD  app/dashboard
# GET|HEAD  app/admin/dashboard
# GET|HEAD  app/company/dashboard
# GET|HEAD  app/agent/dashboard
# GET|HEAD  app/user/dashboard
```

---

## Support

For detailed implementation information, see:
- `DASHBOARD_IMPLEMENTATION_REPORT.md` - Complete technical documentation
- `routes/web.php` - Route definitions
- `app/Http/Controllers/Dashboard/` - Controller implementations

**Implementation Date**: November 8, 2025
**Status**: Ready for Testing ✅
