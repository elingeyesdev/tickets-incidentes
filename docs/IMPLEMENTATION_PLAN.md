# Password Reset Implementation Plan

## Tests Completados ✅

1. **PasswordResetMutationTest.php** - Tests básicos
2. **PasswordResetMutationTestExtended.php** - Tests exhaustivos con:
   - Rate limiting: 1 minuto entre resends, 2 emails cada 3 horas
   - Token + Code combinations
   - Security tests
   - Mailpit integration
   - Edge cases

## Próximos Pasos: EXPLORACIÓN DE ESTRUCTURA

Antes de implementar, necesitamos revisar:

### 1. Shared Architecture
- **GraphQL Types & Inputs**: `app/Shared/GraphQL/Types/`
- **Resolvers Pattern**: `app/Shared/GraphQL/Resolvers/`
- **Exceptions**: `app/Shared/Exceptions/`
- **Mail Templates**: `resources/views/mail/`
- **Rate Limiting**: `config/rate-limiting.php`

### 2. Authentication Feature Structure
- **Resolvers**: `app/Features/Authentication/GraphQL/Resolvers/`
- **Actions**: `app/Features/Authentication/Actions/`
- **Jobs**: `app/Features/Authentication/Jobs/`
- **Mail**: `app/Features/Authentication/Mail/`
- **Listeners**: `app/Features/Authentication/Listeners/`
- **Events**: `app/Features/Authentication/Events/`
- **Models**: `app/Features/Authentication/Models/`

### 3. Existing Patterns to Follow
- Check `ApproveCompanyRequestMutationTest.php` for email patterns
- Check existing resolvers for structure
- Check existing jobs for queuing patterns
- Check existing mail classes for template structure

## Implementation Files Needed

```
app/Features/Authentication/
├── GraphQL/
│   ├── Resolvers/
│   │   ├── ResetPasswordResolver.php        (NEW)
│   │   ├── ConfirmPasswordResetResolver.php (NEW)
│   │   └── PasswordResetStatusResolver.php  (NEW)
│   └── Types/ (si no existe)
│       └── PasswordResetInput.graphql       (NEW)
│
├── Actions/
│   ├── GeneratePasswordResetTokenAction.php (NEW)
│   ├── ConfirmPasswordResetAction.php       (NEW)
│   └── ValidatePasswordResetCodeAction.php  (NEW)
│
├── Jobs/
│   └── SendPasswordResetEmailJob.php        (NEW)
│
├── Mail/
│   └── PasswordResetMail.php                (NEW)
│
├── Listeners/
│   └── PasswordResetListener.php            (NEW)
│
├── Events/
│   └── PasswordResetRequested.php           (NEW)
│
└── Models/
    (usar User existente)

app/Shared/
├── GraphQL/
│   └── Types/
│       ├── PasswordResetInput.graphql       (NEW)
│       └── PasswordResetPayload.graphql     (NEW)
└── Exceptions/
    └── PasswordResetException.php           (NEW)

resources/
└── views/
    └── mail/
        └── password-reset.blade.php         (NEW)

config/
└── rate-limiting.php                       (MODIFY)

graphql/
└── mutations.graphql                        (MODIFY - ADD mutation definitions)
```

## Rate Limiting Configuration

```php
// config/rate-limiting.php
'password_reset_resend' => '1 per minute',     // 1 minuto entre resends
'password_reset_limit'  => '2 per 3 hours',    // 2 emails cada 3 horas
```

## Implementation Order

1. Define GraphQL types (Input & Payload)
2. Create Exceptions
3. Create Models/Events
4. Create Actions (business logic)
5. Create Mail class & template
6. Create Job (queue handler)
7. Create Listener (event subscriber)
8. Create Resolvers (GraphQL handlers)
9. Register resolver in schema
10. Run tests

## GraphQL Schema Changes

```graphql
# mutations.graphql

extend type Mutation {
    """
    Solicita un reset de contraseña. Retorna true siempre (por seguridad).
    Rate limited: 1 por minuto, máximo 2 cada 3 horas
    """
    resetPassword(email: Email!): Boolean!

    """
    Confirma el reset de contraseña con token O código.
    Retorna tokens JWT para auto-login
    """
    confirmPasswordReset(input: PasswordResetInput!): PasswordResetPayload!
}

# queries.graphql

extend type Query {
    """
    Valida si un token de reset es válido y no expirado
    """
    passwordResetStatus(token: String!): PasswordResetStatus!
}

# Types

input PasswordResetInput {
    token: String
    code: String  # 6 dígitos
    newPassword: String!
}

type PasswordResetPayload {
    success: Boolean!
    message: String
    accessToken: String!
    refreshToken: String!
    user: User!
}

type PasswordResetStatus {
    isValid: Boolean!
    canReset: Boolean!
    email: String
    expiresAt: DateTime
}
```

## Environment Variables (already set)

```bash
# .env
CACHE_DRIVER=redis          # Token storage
QUEUE_CONNECTION=redis      # Email job queue
MAIL_FROM_ADDRESS=noreply@helpdesk.local
MAIL_FROM_NAME=Helpdesk System
```

---

**Próximo paso**: Revisar estructura de carpetas y empezar implementación
