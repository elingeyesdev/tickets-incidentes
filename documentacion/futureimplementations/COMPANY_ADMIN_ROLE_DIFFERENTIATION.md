# COMPANY_ADMIN Role Differentiation in Ticket Responses

## Executive Summary

Currently, **COMPANY_ADMIN users are saved as `author_type='user'`** in ticket responses instead of `'agent'`, despite having administrative privileges. This causes visibility and identification issues in:
- Chat component display
- Email notifications
- Dashboard filtering
- Historical data organization

**Status:** Investigation complete. Three low-risk solutions identified. Recommended: **Option 1 (Denormalized `user_role_code` column)**

---

## Problem Statement

### Current Behavior
- Javier Rodríguez (COMPANY_ADMIN) responds to ticket → `author_type='user'` is saved
- The chat shows "USER Javier Rodriguez" instead of "ADMIN Javier Rodriguez"
- Email templates show "Customer" instead of "Administrator"
- Cannot differentiate COMPANY_ADMIN responses from regular user responses

### Root Cause
**File:** `app/Features/TicketManagement/Services/ResponseService.php` (lines 91-100)

```php
private function determineAuthorType(User $user): AuthorType
{
    // Only checks for 'AGENT', not 'COMPANY_ADMIN'
    if (JWTHelper::hasRoleFromJWT('AGENT')) {
        return AuthorType::AGENT;
    }

    // COMPANY_ADMIN falls through to this
    return AuthorType::USER;
}
```

The logic only verifies if the user is an 'AGENT', ignoring that 'COMPANY_ADMIN' should also be treated differently.

---

## Investigation Results

### Affected Systems

| System | Impact | Severity |
|--------|--------|----------|
| **Database Triggers** | `author_type` drives auto-assignment logic | 🔴 CRITICAL |
| **Email Notifications** | SendTicketResponseEmail listener checks `isFromAgent()` | 🔴 CRITICAL |
| **Dashboard Filtering** | Filters by `last_response_author_type` | 🟠 HIGH |
| **Chat Component** | Shows role based on `author_type` | 🟠 HIGH |
| **Eloquent Scopes** | `byAgents()` and `byUsers()` queries | 🟠 HIGH |

### Critical Dependencies

#### 1. SQL Trigger: `assign_ticket_owner_function()`
**File:** `database/migrations/2025_11_05_000002_create_ticket_categories_table.php`

```sql
IF NEW.author_type = 'agent' THEN
    UPDATE ticketing.tickets
    SET
        owner_agent_id = NEW.author_id,
        status = 'pending',
        first_response_at = NOW()
    ...
ELSIF NEW.author_type = 'user' THEN
    -- Only updates last_response_author_type
    -- Does NOT assign owner_agent_id
    -- Does NOT change status
END IF;
```

#### 2. Email Listener: `SendTicketResponseEmail`
**File:** `app/Features/TicketManagement/Listeners/SendTicketResponseEmail.php` (line 46)

```php
if (!$response->isFromAgent()) {
    return; // NO EMAIL SENT
}
```

#### 3. Eloquent Scopes: `TicketResponse.php`
**Lines 107-118:**
```php
public function scopeByAgents(Builder $query): Builder
{
    return $query->where('author_type', AuthorType::AGENT);
}

public function scopeByUsers(Builder $query): Builder
{
    return $query->where('author_type', AuthorType::USER);
}
```

---

## Solution Options

### ⭐ OPTION 1: Denormalized `user_role_code` Column (RECOMMENDED)

**Risk Level:** 🟢 LOW
**Implementation Time:** 2 hours
**Database Impact:** Minimal
**Trigger Changes:** None
**Data Migration:** Simple

#### Why This Approach?
- ✅ Zero trigger modifications
- ✅ Maintains backward compatibility
- ✅ No schema migration complexity
- ✅ Excellent performance (denormalized data)
- ✅ Easy rollback if needed
- ✅ Scalable for future role types

#### Implementation Steps

**Step 1: Create Migration**
```php
// database/migrations/YYYY_MM_DD_add_user_role_code_to_ticket_responses.php

Schema::table('ticketing.ticket_responses', function (Blueprint $table) {
    $table->string('user_role_code')->nullable()->after('author_type');
    $table->index('user_role_code');
});
```

**Step 2: Update ResponseService.php**
```php
class ResponseService
{
    public function create(Ticket $ticket, array $data, User $user): TicketResponse
    {
        $authorType = $this->determineAuthorType($user);
        $userRole = $this->getUserRoleCode($user);

        $response = $ticket->responses()->create([
            'author_id' => $user->id,
            'content' => $data['content'],
            'author_type' => $authorType->value,
            'user_role_code' => $userRole,
        ]);

        return $response;
    }

    private function getUserRoleCode(User $user): string
    {
        $roles = JWTHelper::getRoles();
        return $roles[0]['code'] ?? 'USER';
    }
}
```

**Step 3: Add to TicketResponse Model**
```php
class TicketResponse extends Model
{
    protected $fillable = [
        'author_id',
        'content',
        'author_type',
        'user_role_code',
    ];

    public function getUserRoleDisplay(): string
    {
        return match($this->user_role_code) {
            'AGENT' => 'AGENT',
            'COMPANY_ADMIN' => 'ADMIN',
            'PLATFORM_ADMIN' => 'SUPER_ADMIN',
            default => 'USER',
        };
    }
}
```

**Step 4: Update Chat Component (ticket-chat.blade.php)**
```javascript
// Replace existing role determination
const userRoleCode = msg.user_role_code || msg.author_type;
const roleLabel = userRoleCode === 'user'
    ? 'USER'
    : userRoleCode.toUpperCase();
const displayName = `<strong>${roleLabel}</strong> ${authorName}`;
```

**Step 5: Update Email Template**
```php
// File: TicketResponseMail.php
$authorRole = match($resp->user_role_code) {
    'AGENT' => 'Agent',
    'COMPANY_ADMIN' => 'Administrator',
    'PLATFORM_ADMIN' => 'Super Administrator',
    default => 'Customer',
};
```

**Step 6: Update TicketResponseResource**
```php
class TicketResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'author_id' => $this->author_id,
            'author_type' => $this->author_type,
            'user_role_code' => $this->user_role_code,
            'author' => new UserResource($this->author),
            'attachments' => AttachmentResource::collection($this->attachments),
            'created_at' => $this->created_at,
        ];
    }
}
```

**Step 7: Populate Historical Data**
```sql
-- Identify COMPANY_ADMIN responses saved as 'user' and update
UPDATE ticketing.ticket_responses tr
SET user_role_code = ur.role_code
FROM auth.users u
JOIN auth.user_roles ur ON u.id = ur.user_id
WHERE tr.author_id = u.id
AND tr.user_role_code IS NULL;

-- For existing responses without role data, attempt to infer
UPDATE ticketing.ticket_responses
SET user_role_code = CASE
    WHEN author_type = 'agent' THEN 'AGENT'
    WHEN author_type = 'user' THEN 'USER'
    ELSE 'USER'
END
WHERE user_role_code IS NULL;
```

---

### OPTION 2: Separate Enum Value for COMPANY_ADMIN

**Risk Level:** 🟡 MEDIUM
**Implementation Time:** 3-4 hours
**Database Impact:** Enum modification
**Trigger Changes:** Conditional updates
**Data Migration:** Moderately complex

#### Implementation Summary
- Create new `AuthorType::COMPANY_ADMIN` enum value
- Update triggers to use `IN ('agent', 'company_admin')`
- Update all scopes and queries to include both values
- Requires historical data migration from 'agent' to 'company_admin'

#### Pros & Cons
✅ Clear semantic separation
✅ COMPANY_ADMIN is explicitly stored
✅ No denormalization
❌ More trigger updates required
❌ Complex historical data migration
❌ Potential inconsistency window

---

### OPTION 3: Role Lookup on Read (Runtime Mapping)

**Risk Level:** 🟢 VERY LOW
**Implementation Time:** 1 hour
**Database Impact:** None
**Trigger Changes:** None
**Data Migration:** None

#### Implementation Summary
- Keep `author_type` unchanged
- Join to `auth.user_roles` when needed
- Display role from joined relationship
- Zero database schema changes

#### Pros & Cons
✅ Zero database changes
✅ Zero risk
✅ Easy rollback
✅ No data migration
❌ Extra query join
❌ Performance impact (minor)
❌ Data not persisted (less queryable)

---

## Comparison Matrix

| Aspect | Option 1 | Option 2 | Option 3 |
|--------|----------|---------|---------|
| **Risk Level** | 🟢 LOW | 🟡 MEDIUM | 🟢 VERY LOW |
| **Differentiates ADMIN** | ✅ Yes | ✅ Yes | ✅ Yes |
| **DB Changes** | 1 column | Enum value | None |
| **Trigger Changes** | 0 | Multiple | 0 |
| **Implementation Time** | 2h | 3-4h | 1h |
| **Data Migration** | Simple | Complex | N/A |
| **Performance** | 🟢 Excellent | 🟢 Excellent | 🟡 OK (+1 join) |
| **Code Complexity** | 🟢 Low | 🟡 Medium | 🟢 Very Low |
| **Maintainability** | 🟢 Easy | 🟡 Medium | 🟡 Medium |
| **Scalability** | 🟢 High | 🟡 Medium | 🟡 Medium |

---

## Recommendation

### **Implement OPTION 1: Denormalized `user_role_code` Column**

#### Why?
1. **Lowest Risk:** No trigger modifications required
2. **Best Performance:** Denormalized data, no runtime joins
3. **Scalable:** Easy to add new role types in future
4. **Maintainable:** Clear intent, easy to understand
5. **Reversible:** Can be rolled back if needed
6. **Fast Implementation:** 2 hours including testing

#### Implementation Checklist
- [ ] Create migration for `user_role_code` column
- [ ] Update `ResponseService` to capture role
- [ ] Add helper methods to `TicketResponse` model
- [ ] Update `TicketResponseResource`
- [ ] Update chat component (ticket-chat.blade.php)
- [ ] Update email template (TicketResponseMail.php)
- [ ] Update dashboard filters if applicable
- [ ] Create data migration script for historical records
- [ ] Test with COMPANY_ADMIN user responses
- [ ] Verify emails are sent correctly
- [ ] Verify chat displays correct role
- [ ] Clear view cache and test in browser
- [ ] Run integration tests

---

## Testing Plan

### Manual Testing
```
1. Create ticket as USER
2. Login as COMPANY_ADMIN
3. Add response to ticket
4. Verify in database: user_role_code = 'COMPANY_ADMIN'
5. Check chat component: Shows "ADMIN Name"
6. Check email received: Shows "Administrator"
7. Verify dashboard: Filters work correctly
```

### Query Testing
```sql
-- Verify data saved correctly
SELECT author_id, user_role_code, author_type
FROM ticketing.ticket_responses
WHERE author_id = 'javier-uuid'
LIMIT 5;

-- Verify role capture works
SELECT DISTINCT user_role_code
FROM ticketing.ticket_responses
ORDER BY user_role_code;
```

---

## Rollback Plan

If issues occur:

1. **Remove column:** Drop `user_role_code` from migration
2. **Revert code:** Rollback `ResponseService` changes
3. **Clear cache:** `php artisan view:clear`
4. **Test:** Verify responses still display with `author_type` only

---

## Future Considerations

### Related Features to Monitor
- Dashboard metrics by role type
- Report generation (may need role grouping)
- Audit logging (should include role)
- Analytics (track response times by role)

### Potential Enhancements
- Store role_code for all responses (not just COMPANY_ADMIN)
- Create dashboard widget: "Responses by Role Type"
- Add role filtering to API endpoints
- Generate role-based SLA reports

---

## Questions & Notes

### Q: Will this break existing tickets?
**A:** No. Existing tickets with `author_type='agent'` will continue working. New COMPANY_ADMIN responses will have both `author_type='user'` (for trigger logic) and `user_role_code='COMPANY_ADMIN'` (for display).

### Q: What if a user has multiple roles?
**A:** Store the primary active role from `JWTHelper::getRoles()[0]`. Can be enhanced later to handle multiple roles per response.

### Q: Do we need to update existing COMPANY_ADMIN responses?
**A:** Optional. Historical data can remain without `user_role_code`. New responses will have it for better tracking.

### Q: Can this impact data migrations in the future?
**A:** No. The column is nullable and additive, making it backwards compatible.

---

## Related Files

### Files to Modify
- `app/Features/TicketManagement/Services/ResponseService.php`
- `app/Features/TicketManagement/Models/TicketResponse.php`
- `app/Features/TicketManagement/Http/Resources/TicketResponseResource.php`
- `app/Features/TicketManagement/Mail/TicketResponseMail.php`
- `resources/views/components/ticket-chat.blade.php`
- `database/migrations/[NEW] add_user_role_code_to_ticket_responses.php`

### Files to Monitor
- `app/Features/TicketManagement/Events/ResponseAdded.php`
- `app/Features/TicketManagement/Listeners/SendTicketResponseEmail.php`
- `database/migrations/2025_11_05_000002_create_ticket_categories_table.php` (triggers)

---

## Status

**Created:** 2025-11-23
**Investigation Status:** ✅ Complete
**Recommendation:** ⭐ Option 1
**Implementation Status:** ⏳ Pending Approval

---

## Next Steps

1. Review this document with team
2. Approve recommended approach
3. Create implementation PR
4. Execute testing plan
5. Deploy to production
6. Monitor for issues

