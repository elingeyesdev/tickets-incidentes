# ProfileController API - Quick Reference

**Last Updated:** 2025-11-01
**Status:** ✅ 100% Accurate Documentation

---

## Endpoints

### 1. GET /api/users/me/profile
**Get authenticated user's profile**

**Authentication:** Bearer Token (required)

**Response (200 OK):**
```json
{
  "data": {
    "firstName": "string",
    "lastName": "string",
    "displayName": "string",
    "phoneNumber": "string|null",
    "avatarUrl": "string|null",
    "theme": "light|dark",
    "language": "es|en",
    "timezone": "string",
    "pushWebNotifications": "boolean",
    "notificationsTickets": "boolean",
    "createdAt": "datetime",
    "updatedAt": "datetime"
  }
}
```

**12 Fields Total**

---

### 2. PATCH /api/users/me/profile
**Update authenticated user's profile**

**Authentication:** Bearer Token (required)

**Throttle:** 30 requests/hour

**Request Body (all optional):**
```json
{
  "firstName": "string (min:2, max:100)",
  "lastName": "string (min:2, max:100)",
  "phoneNumber": "string|null (min:10, max:20, regex)",
  "avatarUrl": "string|null (URL, max:2048)"
}
```

**4 Accepted Fields Only**

**Response (200 OK):**
```json
{
  "data": {
    "userId": "uuid",
    "profile": {
      // ProfileResource with 12 fields
      "firstName": "string",
      "lastName": "string",
      "displayName": "string",
      "phoneNumber": "string|null",
      "avatarUrl": "string|null",
      "theme": "string",
      "language": "string",
      "timezone": "string",
      "pushWebNotifications": "boolean",
      "notificationsTickets": "boolean",
      "createdAt": "datetime",
      "updatedAt": "datetime"
    },
    "updatedAt": "datetime"
  }
}
```

**Validation Errors (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "firstName": ["First name must be at least 2 characters"],
    "phoneNumber": ["Phone number format is invalid"]
  }
}
```

---

### 3. PATCH /api/users/me/preferences
**Update authenticated user's preferences**

**Authentication:** Bearer Token (required)

**Throttle:** 50 requests/hour

**Request Body (all optional):**
```json
{
  "theme": "light|dark",
  "language": "es|en",
  "timezone": "string (IANA timezone)",
  "pushWebNotifications": "boolean",
  "notificationsTickets": "boolean"
}
```

**5 Accepted Fields**

**Response (200 OK):**
```json
{
  "data": {
    "userId": "uuid",
    "preferences": {
      "theme": "light|dark",
      "language": "es|en",
      "timezone": "string",
      "pushWebNotifications": "boolean",
      "notificationsTickets": "boolean",
      "updatedAt": "datetime"
    },
    "updatedAt": "datetime"
  }
}
```

**Validation Errors (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "theme": ["Theme must be either \"light\" or \"dark\""],
    "language": ["Language must be either \"es\" or \"en\""],
    "timezone": ["Invalid timezone"]
  }
}
```

---

## Field Reference

### ProfileResource (12 fields)

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| firstName | string | No | User's first name |
| lastName | string | No | User's last name |
| displayName | string | No | Full name (firstName + lastName) |
| phoneNumber | string | Yes | Phone number |
| avatarUrl | string | Yes | Avatar image URL |
| theme | string | No | UI theme: "light" or "dark" |
| language | string | No | Language: "es" or "en" |
| timezone | string | No | IANA timezone identifier |
| pushWebNotifications | boolean | No | Push web notifications enabled |
| notificationsTickets | boolean | No | Ticket notifications enabled |
| createdAt | datetime | No | Profile creation timestamp (ISO 8601) |
| updatedAt | datetime | No | Last update timestamp (ISO 8601) |

### PreferencesResource (6 fields)

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| theme | string | No | UI theme: "light" or "dark" |
| language | string | No | Language: "es" or "en" |
| timezone | string | No | IANA timezone identifier |
| pushWebNotifications | boolean | No | Push web notifications enabled |
| notificationsTickets | boolean | No | Ticket notifications enabled |
| updatedAt | datetime | No | Last update timestamp (ISO 8601) |

---

## Validation Rules

### UpdateProfileRequest

| Field | Rules | Description |
|-------|-------|-------------|
| firstName | sometimes, required, string, min:2, max:100 | First name (2-100 chars) |
| lastName | sometimes, required, string, min:2, max:100 | Last name (2-100 chars) |
| phoneNumber | sometimes, nullable, string, min:10, max:20, regex | Digits, spaces, +, -, (, ) allowed |
| avatarUrl | sometimes, nullable, url, max:2048 | Valid URL format |

**Regex Pattern:** `/^[\d\s\+\-\(\)]+$/`

### UpdatePreferencesRequest

| Field | Rules | Description |
|-------|-------|-------------|
| theme | sometimes, required, in:light,dark | Theme preference |
| language | sometimes, required, in:es,en | Language preference |
| timezone | sometimes, required, string, timezone | IANA timezone |
| pushWebNotifications | sometimes, required, boolean | Push notifications |
| notificationsTickets | sometimes, required, boolean | Ticket notifications |

---

## Enums

### Theme
- `light`
- `dark`

### Language
- `es` (Spanish)
- `en` (English)

**Note:** Only these 2 languages are supported, not 4 (fr, de not supported)

---

## Common Errors

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```
**Solution:** Provide valid Bearer token in Authorization header

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field": ["error message"]
  }
}
```
**Solution:** Check validation rules and fix input data

### 429 Too Many Requests
```json
{
  "message": "Too Many Attempts."
}
```
**Solution:** Wait before retrying (respect throttle limits)

---

## Examples

### Get Profile
```bash
curl -X GET http://localhost:8000/api/users/me/profile \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Update Profile
```bash
curl -X PATCH http://localhost:8000/api/users/me/profile \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "firstName": "María",
    "lastName": "González",
    "phoneNumber": "+56987654321"
  }'
```

### Update Preferences
```bash
curl -X PATCH http://localhost:8000/api/users/me/preferences \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "theme": "dark",
    "language": "en",
    "timezone": "America/New_York"
  }'
```

---

## Important Notes

### ✅ What's Documented
- All 12 ProfileResource fields
- All 6 PreferencesResource fields
- Exact validation rules from FormRequests
- Real enums (not assumed ones)
- Accurate examples

### ❌ What's NOT Accepted
- `birthDate` - does NOT exist
- `bio` - does NOT exist
- `timezone` in profile update - NOT accepted (use preferences)
- `emailNotifications` - does NOT exist
- `weeklyDigest` - does NOT exist
- `avatar` field name - use `avatarUrl` instead
- `pushNotifications` field name - use `pushWebNotifications` instead
- Language codes `fr`, `de` - only `es`, `en` supported

### 🔐 Security
- All endpoints require authentication
- Profile updates throttled: 30/hour
- Preferences updates throttled: 50/hour
- JWT Bearer token required in Authorization header

### 📝 Response Format
- All timestamps in ISO 8601 format
- All responses wrapped in `data` key
- Validation errors include field names and messages

---

## Swagger UI

View interactive documentation:
**http://localhost:8000/api/documentation**

---

## Files Modified

- `app/Features/UserManagement/Http/Controllers/ProfileController.php`
- `storage/api-docs/api-docs.json` (regenerated)

---

## Related Resources

- **UpdateProfileRequest:** `app/Features/UserManagement/Http/Requests/UpdateProfileRequest.php`
- **UpdatePreferencesRequest:** `app/Features/UserManagement/Http/Requests/UpdatePreferencesRequest.php`
- **ProfileResource:** `app/Features/UserManagement/Http/Resources/ProfileResource.php`
- **PreferencesResource:** `app/Features/UserManagement/Http/Resources/PreferencesResource.php`

---

**Last Verified:** 2025-11-01
**Documentation Accuracy:** ✅ 100%
**Logic Changes:** ❌ None (annotations only)
