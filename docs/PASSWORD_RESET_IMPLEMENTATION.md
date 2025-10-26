# Password Reset Implementation Architecture

## ANÁLISIS DE ESTRUCTURA ACTUAL

### Shared (Componentes Base)
- **BaseMutation**: Clase base para todas las mutations
- **Exceptions**: HelpdeskException, ValidationException, RateLimitExceededException
- **GraphQL Patterns**: Resolvers, Directives (JWT, RateLimit), Error Handling

### Authentication Feature
- **Services**: 
  - `AuthService` - Login, refresh tokens, sesiones
  - `PasswordResetService` - YA EXISTE, pero necesita ajustes
  - `TokenService` - Tokens JWT, revoke de sesiones
- **Events**: PasswordResetRequested, PasswordResetCompleted
- **Mutations**: ResetPasswordMutation (dummy), ConfirmPasswordResetMutation (dummy)
- **Models**: User (con password_hash)

### Company Pattern (Para seguir)
- **Mail**: CompanyApprovalMailForNewUser, CompanyApprovalMailForExistingUser
- **Job**: SendCompanyApprovalEmailJob (en cola 'emails')
- **Resolver**: En Mutations (LoginMutation como ejemplo)

---

## REQUIREMENTS DEL PASSWORD RESET

### 3 GraphQL Operations:
1. `resetPassword(email: Email!) -> Boolean` - Request reset
2. `confirmPasswordReset(input: PasswordResetInput!) -> PasswordResetPayload` - Confirm reset
3. `passwordResetStatus(token: String!) -> PasswordResetStatus` - Check token validity

### Token + Code Strategy:
- **Token**: 32-char random string via email link
- **Code**: 6-digit code via email (or SMS future)
- User can reset with EITHER token OR code, but NOT both in same request
- Must reject if both provided

### Rate Limiting:
- 1 minuto entre resends del mismo email
- Máximo 2 emails cada 3 horas por usuario

### Auto-Login After Reset:
- Genera nuevo JWT (accessToken + refreshToken)
- Invalida TODAS las sesiones previas
- Retorna User + tokens para auto-login

---

## FILES TO CREATE

```
app/Features/Authentication/
├── GraphQL/
│   └── Mutations/
│       ├── ResetPasswordMutation.php        (IMPLEMENT)
│       └── ConfirmPasswordResetMutation.php (IMPLEMENT)
│
├── Mail/
│   └── PasswordResetMail.php                (CREATE - email con token + code)
│
├── Jobs/
│   └── SendPasswordResetEmailJob.php        (CREATE - async email sender)
│
├── Listeners/
│   └── PasswordResetListener.php            (CREATE - event listener)
│
└── Services/
    └── PasswordResetService.php             (MODIFY - already exists)

app/Shared/GraphQL/
└── Types/
    ├── PasswordResetInput.graphql           (CREATE)
    └── PasswordResetStatus.graphql          (CREATE)

graphql/
└── authentication.graphql                   (CREATE - mutations + queries)

resources/views/
└── mail/
    └── password-reset.blade.php             (CREATE - email template)
```

---

## IMPLEMENTATION ORDER

1. **PasswordResetMail** - Email mailable class
2. **SendPasswordResetEmailJob** - Queue job
3. **PasswordResetListener** - Event listener
4. **Update PasswordResetService** - Ajustar para token + code + rate limiting
5. **ResetPasswordMutation** - Solicita reset
6. **PasswordResetStatusResolver** - Query para validar token
7. **ConfirmPasswordResetMutation** - Confirm reset + auto-login
8. **Email template** - Blade template con token + code
9. **GraphQL Types** - Input + Status types
10. **Register in schema** - Mutations en graphql.yaml

---

## KEY PATTERNS TO FOLLOW

### From LoginMutation:
```php
public function __construct(
    private readonly AuthService $authService
) {}

public function __invoke($root, array $args, $context = null): array
{
    // 1. Extract input
    // 2. Call service
    // 3. Return formatted response
}
```

### From CompanyApprovalEmailJob:
```php
class SendPasswordResetEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $tries = 3;
    public int $timeout = 30;
    
    public function __construct(...) {
        $this->onQueue('emails');
    }
    
    public function handle(): void {
        Mail::to($email)->send(new PasswordResetMail(...));
    }
}
```

### From PasswordResetRequested Event:
```php
class PasswordResetRequested
{
    use Dispatchable, SerializesModels;
    
    public function __construct(
        public User $user,
        public string $resetToken
    ) {}
}
```

---

## CACHE KEYS STRATEGY (Redis)

```
password_reset:{user_id}              -> Token data + metadata
password_reset_code:{user_id}         -> 6-digit code
password_reset_resend:{user_id}       -> Last resend timestamp (rate limit: 1 min)
password_reset_count_3h:{user_id}     -> Count in 3-hour window (max: 2)
```

---

## READY TO IMPLEMENT ✅

Todos los patterns están claros. Empezar por:
1. PasswordResetMail
2. SendPasswordResetEmailJob  
3. PasswordResetListener
4. Actualizar PasswordResetService
5. Mutations + Query resolvers
