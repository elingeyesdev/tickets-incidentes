# Migration Guide: Secure Token Storage

## Overview

This guide explains how to migrate from the old token storage system (refresh tokens in localStorage) to the new secure system (refresh tokens in HttpOnly cookies).

## Migration Type

**Automatic Migration** - No manual user intervention required

## User Impact

**Zero Downtime** - Users will not experience any interruptions

## Migration Timeline

### Phase 1: Deployment (Immediate)
- New code deployed to production
- Backend continues to accept refresh tokens from both localStorage and cookies
- New logins create HttpOnly cookies
- Existing sessions continue to work

### Phase 2: Natural Migration (Ongoing)
- Users gradually migrate as they:
  - Login again
  - Token expires and refreshes
  - Close and reopen browser
- Migration completes organically over ~30 days (refresh token lifetime)

### Phase 3: Cleanup (Optional, after 30 days)
- Remove legacy localStorage refresh token support from backend
- All users migrated to HttpOnly cookies

## Technical Migration Flow

### Existing User Session

**Before Migration**:
```
localStorage:
  - access_token: "eyJ..."
  - refresh_token: "abc123..."  ❌ OLD METHOD

Cookies:
  (none)
```

**After Deployment (Automatic)**:
```
localStorage:
  - access_token: "eyJ..."
  - refresh_token: "abc123..."  ⚠️ IGNORED (will be cleared on next login)

Cookies:
  (none yet - will be set on next login or refresh)
```

**After Next Login/Refresh**:
```
localStorage:
  - access_token: "eyJ..."  ✅ NEW TOKEN

Cookies:
  - refresh_token: "xyz789..."  ✅ HttpOnly, Secure
```

**After Cleanup Script (see below)**:
```
localStorage:
  - access_token: "eyJ..."  ✅

Cookies:
  - refresh_token: "xyz789..."  ✅ HttpOnly, Secure
```

## Migration Strategies

### Strategy A: Passive Migration (Recommended)

**No action required** - Users migrate automatically over time

**Pros**:
- Zero user impact
- No forced logouts
- Gradual rollout

**Cons**:
- Takes up to 30 days for 100% migration
- Old refresh tokens remain in localStorage temporarily

**Implementation**:
```javascript
// Backend continues to accept both methods
if (refreshTokenFromCookie) {
  // Use cookie (new method)
  return refreshFromCookie(refreshTokenFromCookie);
} else if (refreshTokenFromBody) {
  // Use localStorage (old method - deprecated)
  return refreshFromBody(refreshTokenFromBody);
} else {
  throw new UnauthorizedException();
}
```

### Strategy B: Active Migration (Optional)

**Add cleanup script** - Remove old refresh tokens from localStorage on page load

**Pros**:
- Faster migration
- Cleaner localStorage
- Immediate security benefit

**Cons**:
- Requires additional code
- One-time performance cost

**Implementation**:

Add this to your main app initialization:

```javascript
// resources/js/app.js or equivalent
(function cleanupOldTokens() {
  const oldRefreshToken = localStorage.getItem('refresh_token');

  if (oldRefreshToken) {
    console.log('[Migration] Removing old refresh_token from localStorage');
    localStorage.removeItem('refresh_token');

    // Optional: Track migration metrics
    fetch('/api/metrics/token-migration', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ migrated: true })
    }).catch(() => {}); // Ignore errors
  }
})();
```

### Strategy C: Forced Migration (Not Recommended)

**Force all users to re-login** - Clear all sessions

**Pros**:
- Immediate 100% migration
- Guaranteed security

**Cons**:
- **Bad UX** - Users forced to login again
- Support tickets from confused users
- Potential reputation damage

**Implementation**:
```javascript
// NOT RECOMMENDED - Only use if absolutely necessary
if (localStorage.getItem('refresh_token')) {
  localStorage.clear();
  window.location.href = '/login?reason=security_update';
}
```

## Recommended Approach

**Use Strategy A (Passive Migration)** with optional **Strategy B (Active Migration)** for cleanup.

### Implementation Steps

1. **Deploy new code** (already done)
2. **Monitor migration progress** (optional - see metrics below)
3. **Add cleanup script** after 1 week (optional - Strategy B)
4. **Remove legacy support** after 30 days (optional - Phase 3)

## Monitoring Migration Progress

### Add Migration Tracking (Optional)

Track how many users have migrated:

```javascript
// Backend: Track cookie vs localStorage usage
router.post('/api/auth/refresh', (req, res) => {
  const cookieToken = req.cookies.refresh_token;
  const bodyToken = req.body.refreshToken;

  // Track migration metrics
  if (cookieToken) {
    metrics.increment('auth.refresh.cookie'); // New method
  } else if (bodyToken) {
    metrics.increment('auth.refresh.localStorage'); // Old method
  }

  // ... rest of refresh logic
});
```

### Migration Dashboard Query

```sql
-- Check migration progress (example)
SELECT
  DATE(created_at) as date,
  COUNT(CASE WHEN refresh_method = 'cookie' THEN 1 END) as cookie_count,
  COUNT(CASE WHEN refresh_method = 'localStorage' THEN 1 END) as localStorage_count,
  ROUND(
    COUNT(CASE WHEN refresh_method = 'cookie' THEN 1 END) * 100.0 /
    COUNT(*), 2
  ) as migration_percentage
FROM auth_refresh_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

## User Communication

### Option 1: Silent Migration (Recommended)

**No communication needed** - Migration is transparent to users

### Option 2: Proactive Communication (Optional)

**For security-conscious users**, send optional notification:

**Email Template**:
```
Subject: Security Enhancement: Improved Session Management

Hi [Name],

We've enhanced the security of your Helpdesk account by upgrading our
session management system. This update happens automatically - no action
is required from you.

What changed:
- Your login sessions are now more secure
- Protection against certain types of security attacks improved
- No change to your login experience

If you notice anything unusual, please contact support.

Best regards,
Helpdesk Security Team
```

### Option 3: Banner Notification (Not Recommended)

**Only if you must communicate** - Show dismissible banner:

```html
<div class="alert alert-info alert-dismissible">
  <button type="button" class="close" data-dismiss="alert">&times;</button>
  <i class="fas fa-shield-alt"></i>
  <strong>Security Enhancement:</strong> We've upgraded our session security.
  You may need to login again soon.
</div>
```

## Rollback Plan

### If Issues Arise

**Rollback is simple** - revert to previous code:

```bash
# Git rollback
git revert <commit-hash>
git push

# Or restore previous files
git checkout HEAD~1 -- resources/js/lib/auth/TokenManager.js
git checkout HEAD~1 -- resources/views/public/login.blade.php
# ... restore other files
```

**User Impact**: None - old refresh tokens in localStorage will work again

## Testing Migration

### Test Scenarios

1. **New User Login**
   - Login with fresh account
   - Verify HttpOnly cookie set
   - Verify NO refresh_token in localStorage

2. **Existing User (Old Token)**
   - Login with account that has localStorage refresh token
   - Verify old token still works
   - Verify new HttpOnly cookie created on refresh

3. **Token Expiration**
   - Wait for access token to expire
   - Verify auto-refresh works
   - Verify new tokens issued

4. **Browser Restart**
   - Close browser completely
   - Reopen and navigate to app
   - Verify session restored from HttpOnly cookie

### Test Script

Run this in browser console to verify migration:

```javascript
// Migration Test
console.log('=== MIGRATION TEST ===');

const oldToken = localStorage.getItem('refresh_token');
const newToken = localStorage.getItem('access_token');
const hasCookie = document.cookie.includes('refresh_token');

console.log('Old refresh_token (localStorage):', oldToken ? 'EXISTS (needs cleanup)' : 'NULL (migrated)');
console.log('New access_token (localStorage):', newToken ? 'EXISTS' : 'NULL');
console.log('HttpOnly cookie (visible):', hasCookie ? 'VISIBLE (not HttpOnly!)' : 'HIDDEN (HttpOnly ✓)');

// Migration status
if (!oldToken && newToken && !hasCookie) {
  console.log('✅ FULLY MIGRATED');
} else if (oldToken && newToken) {
  console.log('⚠️  PARTIALLY MIGRATED (cleanup old token)');
} else if (oldToken && !newToken) {
  console.log('⏳ NOT MIGRATED YET (login to migrate)');
} else {
  console.log('❌ UNEXPECTED STATE');
}
```

## Cleanup Old Tokens (Phase 3)

### After 30 Days (Optional)

Remove legacy localStorage refresh token support:

**Frontend Cleanup** (run once on page load):

```javascript
// Add to app.js
if (localStorage.getItem('refresh_token')) {
  localStorage.removeItem('refresh_token');
  console.log('Removed legacy refresh_token');
}
```

**Backend Cleanup** (remove legacy support):

```javascript
// BEFORE (supports both methods)
router.post('/api/auth/refresh', (req, res) => {
  const refreshToken = req.cookies.refresh_token || req.body.refreshToken;
  // ...
});

// AFTER (only supports HttpOnly cookie)
router.post('/api/auth/refresh', (req, res) => {
  const refreshToken = req.cookies.refresh_token;

  if (!refreshToken) {
    return res.status(401).json({
      message: 'Refresh token not found. Please login again.'
    });
  }
  // ...
});
```

## Success Criteria

Migration is successful when:

- ✅ 95%+ of active users have HttpOnly cookie
- ✅ No increase in support tickets
- ✅ No increase in error rates
- ✅ Token refresh success rate remains stable
- ✅ Zero security incidents related to token storage

## FAQ

### Q: Will users be logged out?
**A**: No, existing sessions continue to work.

### Q: How long does migration take?
**A**: Immediate for new logins, up to 30 days for all users.

### Q: Do users need to take any action?
**A**: No, migration is automatic.

### Q: What if migration fails?
**A**: Old tokens continue to work. No user impact.

### Q: Can we speed up migration?
**A**: Yes, add cleanup script (Strategy B) or force re-login (not recommended).

### Q: Is rollback possible?
**A**: Yes, simple git revert. No data loss.

## Support Contacts

**Technical Issues**: dev-team@helpdesk.com
**Security Questions**: security@helpdesk.com
**User Support**: support@helpdesk.com

---

**Last Updated**: 2025-11-08
**Version**: 1.0
