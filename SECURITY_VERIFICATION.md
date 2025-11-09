# Security Verification Guide

## Secure Token Storage Implementation

This document outlines the security verification steps for the HttpOnly cookie-based refresh token implementation.

## Architecture Overview

```
STORAGE LOCATION:
┌─────────────────────────────────────────┐
│ localStorage                             │
│  ├─ access_token (60 min TTL) ✅        │
│  ├─ helpdesk_token_expiry ✅            │
│  ├─ helpdesk_token_issued_at ✅         │
│  └─ other app data ✅                   │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ HttpOnly Cookie (browser managed)        │
│  ├─ refresh_token (30 days TTL) ✅      │
│  ├─ JavaScript CANNOT ACCESS ✅         │
│  └─ Sent automatically by browser ✅    │
└─────────────────────────────────────────┘

❌ REMOVED FROM STORAGE:
   refresh_token from localStorage
   refreshToken anywhere in JavaScript
```

## Security Checklist

### 1. localStorage Security Verification

Run these commands in browser DevTools console:

```javascript
// SECURITY TEST 1: Verify refresh_token is NOT in localStorage
console.log('=== SECURITY TEST 1: localStorage Check ===');
const refreshToken = localStorage.getItem('refresh_token');
if (refreshToken === null) {
    console.log('✅ PASS: refresh_token NOT found in localStorage (SECURE)');
} else {
    console.error('❌ FAIL: refresh_token found in localStorage (INSECURE!)');
    console.error('Value:', refreshToken);
}

// SECURITY TEST 2: Verify access_token exists in localStorage
console.log('\n=== SECURITY TEST 2: access_token Check ===');
const accessToken = localStorage.getItem('access_token');
if (accessToken) {
    console.log('✅ PASS: access_token found in localStorage');
    console.log('Token preview:', accessToken.substring(0, 50) + '...');
} else {
    console.error('❌ FAIL: access_token NOT found (should exist after login)');
}

// SECURITY TEST 3: Check all localStorage keys
console.log('\n=== SECURITY TEST 3: All localStorage Keys ===');
const allKeys = Object.keys(localStorage);
console.log('Total keys:', allKeys.length);
console.log('Keys:', allKeys);

const dangerousKeys = allKeys.filter(key =>
    key.toLowerCase().includes('refresh')
);
if (dangerousKeys.length === 0) {
    console.log('✅ PASS: No refresh token keys found');
} else {
    console.error('❌ FAIL: Found suspicious keys:', dangerousKeys);
}
```

### 2. Cookie Verification

Check browser cookies:

1. Open DevTools → Application → Cookies → `http://localhost` (or your domain)
2. Look for `refresh_token` cookie
3. Verify cookie attributes:
   - ✅ HttpOnly: YES
   - ✅ Secure: YES (in production)
   - ✅ SameSite: Lax or Strict
   - ✅ Path: /
   - ✅ Expires: ~30 days

```javascript
// SECURITY TEST 4: Try to access HttpOnly cookie (should fail)
console.log('=== SECURITY TEST 4: HttpOnly Cookie Access Attempt ===');
const allCookies = document.cookie;
console.log('Accessible cookies:', allCookies);

if (allCookies.includes('refresh_token')) {
    console.error('❌ FAIL: refresh_token is accessible via JavaScript (NOT HttpOnly!)');
} else {
    console.log('✅ PASS: refresh_token NOT accessible via JavaScript (HttpOnly protected)');
}
```

### 3. XSS Protection Test

Simulate XSS attack attempting to steal tokens:

```javascript
// SECURITY TEST 5: XSS Simulation - Attempt to steal refresh token
console.log('=== SECURITY TEST 5: XSS Attack Simulation ===');

// Attacker's payload trying to steal refresh token
const stolenRefreshToken = localStorage.getItem('refresh_token');
const stolenFromCookie = document.cookie.match(/refresh_token=([^;]+)/);

console.log('Stolen refresh_token from localStorage:', stolenRefreshToken);
console.log('Stolen refresh_token from cookie:', stolenFromCookie);

if (stolenRefreshToken === null && stolenFromCookie === null) {
    console.log('✅ PASS: XSS attack cannot steal refresh_token');
    console.log('✅ System is protected against token theft');
} else {
    console.error('❌ FAIL: Refresh token is vulnerable to XSS!');
}

// Attacker CAN steal access token (but 60min TTL limits damage)
const stolenAccessToken = localStorage.getItem('access_token');
if (stolenAccessToken) {
    console.warn('⚠️  NOTE: access_token CAN be stolen via XSS');
    console.warn('⚠️  However, 60-minute expiration limits damage window');
    console.warn('⚠️  Attacker cannot refresh token (needs HttpOnly cookie)');
}
```

### 4. Token Refresh Flow Test

Test automatic token refresh:

```javascript
// SECURITY TEST 6: Token Refresh Flow
console.log('=== SECURITY TEST 6: Token Refresh Flow ===');

// Check TokenManager
if (typeof tokenManager !== 'undefined') {
    console.log('TokenManager available');

    // Trigger manual refresh
    tokenManager.refresh()
        .then(() => {
            console.log('✅ PASS: Token refresh successful');
            console.log('New access_token:', localStorage.getItem('access_token').substring(0, 50) + '...');

            // Verify refresh_token still not in localStorage
            if (localStorage.getItem('refresh_token') === null) {
                console.log('✅ PASS: refresh_token still not in localStorage after refresh');
            } else {
                console.error('❌ FAIL: refresh_token appeared in localStorage after refresh!');
            }
        })
        .catch(err => {
            console.error('❌ Refresh failed:', err);
        });
} else {
    console.warn('TokenManager not loaded in this context');
}
```

### 5. API Request Test

Test automatic Authorization header injection:

```javascript
// SECURITY TEST 7: API Request with Auto-Refresh
console.log('=== SECURITY TEST 7: API Request Test ===');

// Make authenticated request
fetch('/api/auth/status', {
    method: 'GET',
    credentials: 'include', // Include cookies
    headers: {
        'Authorization': `Bearer ${localStorage.getItem('access_token')}`,
        'Accept': 'application/json'
    }
})
.then(response => response.json())
.then(data => {
    console.log('✅ Authenticated request successful');
    console.log('User data:', data);
})
.catch(err => {
    console.error('❌ Request failed:', err);
});
```

### 6. Session Persistence Test

Test session restoration after browser close:

1. Login to application
2. Run localStorage test (verify access_token exists)
3. Close browser completely
4. Reopen browser
5. Navigate to dashboard
6. Verify:
   - ✅ Access token expired (cleared)
   - ✅ Refresh token cookie still exists
   - ✅ Automatic token refresh happens
   - ✅ User logged in automatically

### 7. Logout Test

Verify tokens are cleared on logout:

```javascript
// SECURITY TEST 8: Logout Cleanup
console.log('=== SECURITY TEST 8: Logout Cleanup ===');

// Before logout
console.log('Before logout:');
console.log('- access_token:', localStorage.getItem('access_token') ? 'EXISTS' : 'NULL');
console.log('- refresh_token (localStorage):', localStorage.getItem('refresh_token') ? 'EXISTS' : 'NULL');

// Perform logout (you can call logout manually or via UI)
// After logout, run:
console.log('\nAfter logout:');
console.log('- access_token:', localStorage.getItem('access_token') ? 'EXISTS' : 'NULL');
console.log('- refresh_token (localStorage):', localStorage.getItem('refresh_token') ? 'EXISTS' : 'NULL');

// Check cookies in DevTools → Application → Cookies
// refresh_token cookie should be deleted by server
```

## Manual Testing Checklist

- [ ] Login flow works correctly
- [ ] Access token stored in localStorage
- [ ] Refresh token NOT in localStorage
- [ ] Refresh token in HttpOnly cookie
- [ ] Dashboard loads with valid token
- [ ] Token expires after 60 minutes
- [ ] Automatic refresh triggers before expiration
- [ ] New access token received and stored
- [ ] Original request retries with new token
- [ ] XSS test: `localStorage.getItem('refresh_token')` returns `null`
- [ ] XSS test: `document.cookie` does NOT contain refresh_token
- [ ] Close/reopen browser: session restores
- [ ] Logout clears access_token from localStorage
- [ ] Logout clears refresh_token cookie (check DevTools)
- [ ] Register flow works identically
- [ ] Password reset flow works identically
- [ ] No console errors during normal operation

## Security Guarantees

### What is Protected
- ✅ Refresh tokens cannot be stolen via XSS
- ✅ Refresh tokens cannot be read by JavaScript
- ✅ Long-term session security maintained
- ✅ Browser automatically manages refresh token
- ✅ Refresh token sent only to same domain (SameSite)

### What is NOT Protected (by design)
- ⚠️ Access tokens CAN be stolen via XSS
  - **Mitigation**: 60-minute expiration limits damage
  - **Mitigation**: Attacker cannot refresh token without HttpOnly cookie
  - **Mitigation**: CSP headers should prevent XSS in first place

### Attack Scenarios

#### Scenario 1: XSS Attack Steals Access Token
```javascript
// Attacker code injected via XSS
const stolen = localStorage.getItem('access_token');
sendToAttacker(stolen); // Attacker gets 60-min window

// What attacker CANNOT do:
// 1. Cannot get refresh_token (HttpOnly protected)
// 2. Cannot extend session beyond 60 minutes
// 3. Token expires, attacker loses access
```

**Damage**: Limited to 60 minutes
**Mitigation**: CSP headers, input sanitization, token expiration

#### Scenario 2: XSS Attack Attempts to Steal Refresh Token
```javascript
// Attacker code injected via XSS
const stolen1 = localStorage.getItem('refresh_token'); // null ✅
const stolen2 = document.cookie.match(/refresh_token/); // null ✅

// Attacker CANNOT steal refresh token
// Session security maintained
```

**Damage**: None - refresh token is protected
**Result**: Long-term session security maintained

## Files Modified

### Core Security Changes

1. **TokenManager.js** (`resources/js/lib/auth/TokenManager.js`)
   - Removed: `setRefreshToken()`, `getRefreshToken()`
   - Updated: `_performRefresh()` to use HttpOnly cookie
   - Changed: Storage key from `helpdesk_access_token` to `access_token`

2. **PersistenceService.js** (`resources/js/lib/auth/PersistenceService.js`)
   - Removed: `refreshToken` from saved state
   - Added: Security comments explaining why

3. **authStore.js** (`resources/js/alpine/stores/authStore.js`)
   - Removed: `setRefreshToken()` calls
   - Updated: `refreshToken()` method to use HttpOnly cookie
   - Added: `credentials: 'include'` to refresh request

### View Updates

4. **login.blade.php** (`resources/views/public/login.blade.php`)
   - Removed: `localStorage.setItem('refresh_token', ...)`
   - Kept: `localStorage.setItem('access_token', ...)`

5. **register.blade.php** (`resources/views/public/register.blade.php`)
   - Removed: `localStorage.setItem('refresh_token', ...)`
   - Kept: `localStorage.setItem('access_token', ...)`

6. **forgot-password.blade.php** (`resources/views/public/forgot-password.blade.php`)
   - Removed: `localStorage.setItem('refresh_token', ...)`
   - Kept: `localStorage.setItem('access_token', ...)`

### New Files

7. **ApiClient.js** (`resources/js/lib/api/ApiClient.js`)
   - New: Fetch wrapper with auto-refresh on 401
   - New: Automatic Authorization header injection
   - New: Request retry after token refresh
   - New: Concurrent refresh debouncing

## Quick Security Audit

Run this one-liner in console to verify security:

```javascript
// One-line security audit
console.log('🔒 SECURITY AUDIT:', {
  'refresh_token in localStorage': localStorage.getItem('refresh_token') === null ? '✅ SECURE' : '❌ VULNERABLE',
  'access_token in localStorage': localStorage.getItem('access_token') ? '✅ EXISTS' : '⚠️ MISSING',
  'refresh_token in cookies': document.cookie.includes('refresh_token') ? '❌ ACCESSIBLE' : '✅ HTTPONLY',
  'Overall Status': localStorage.getItem('refresh_token') === null && !document.cookie.includes('refresh_token') ? '✅ SECURE' : '❌ NEEDS FIX'
});
```

Expected output:
```
🔒 SECURITY AUDIT: {
  refresh_token in localStorage: "✅ SECURE",
  access_token in localStorage: "✅ EXISTS",
  refresh_token in cookies: "✅ HTTPONLY",
  Overall Status: "✅ SECURE"
}
```

## Additional Resources

- [OWASP: XSS Prevention](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)
- [OWASP: JWT Security](https://cheatsheetseries.owasp.org/cheatsheets/JSON_Web_Token_for_Java_Cheat_Sheet.html)
- [MDN: HttpOnly Cookies](https://developer.mozilla.org/en-US/docs/Web/HTTP/Cookies#restrict_access_to_cookies)

## Support

If you encounter security issues, please report them immediately to the security team.
