# 🔴 TEST FAILURE ANALYSIS - PASSWORD RESET FEATURE

**Fecha**: 26 Octubre 2025  
**Ejecución**: `php artisan test tests/Feature/Authentication/Mutations/PasswordResetCompleteTest.php`  
**Resultado**: ❌ 15 FAILED / ✅ 17 PASSED (53%)  
**Duración Total**: 19.83s

---

## 📊 RESUMEN EJECUTIVO

### Estado de Tests por Categoría

| Categoría | Pasando | Fallando | % | Prioridad |
|-----------|---------|----------|---|-----------|
| **Solicitud & Rate Limiting** | 3 | 4 | 43% | 🔴 CRÍTICO |
| **Validación de Tokens** | 5 | 0 | 100% | ✅ |
| **Confirmación de Reset** | 0 | 8 | 0% | 🔴 CRÍTICO |
| **Email/Mailpit** | 0 | 1 | 0% | 🟡 SECUNDARIO |
| **Seguridad** | 9 | 2 | 82% | 🟡 IMPORTANTE |

### Raíz de Fallo Principal

```
90% de fallos: confirmPasswordReset mutation retorna success: NULL
Causa: GraphQL response mapping en Lighthouse
Stack: Mutation → Resolver → GraphQL Type Mapping
```

---

## 📋 LISTA DETALLADA DE TESTS FALLANDO

### 🔴 [1/15] FAILED: `sends_reset_email_with_token_and_code`

**Línea**: 175  
**Error**: `The expected [App\Features\Authentication\Mail\PasswordResetMail] mailable was not sent.`

**Problema Raíz**: 
- Listener `SendPasswordResetEmail` no dispara `SendPasswordResetEmailJob`
- O job no se ejecuta en contexto de test
- O Mail::fake() no está capturando el envío

**Diagnóstico**:
```php
// Test intenta verificar:
Mail::assertSent(PasswordResetMail::class);

// Pero el email nunca se envía porque:
// 1. ¿Listener no ejecuta?
// 2. ¿Job no se ejecuta?
// 3. ¿Mail::fake() no funciona?
```

**Soluciones a probar** (en orden):
1. Verificar que `SendPasswordResetEmail::class` tiene `dispatch(SendPasswordResetEmailJob::class)`
2. Verificar que `SendPasswordResetEmailJob` existe y es correcta
3. Agregrar `Mail::fake()` y `Queue::fake()` explícitamente en setUp()
4. Ejecutar jobs con `$this->executeQueuedJobs()` o similar

**Impacto**: CRÍTICO - Bloquea validación de email delivery

---

### 🔴 [2/15] FAILED: `email_contains_token_and_6_digit_code`

**Línea**: 201  
**Error**: `The expected [App\Features\Authentication\Mail\PasswordResetMail] mailable was not sent.`

**Problema Raíz**: Idéntico al test #1

**Diagnóstico**: Sin email enviado, no se puede validar contenido

**Dependencia**: Bloqueado por solución de test #1

---

### 🟡 [3/15] FAILED: `rate_limits_reset_resends_to_1_per_minute`

**Línea**: 233  
**Error**: `Failed asserting that null is not null.` en `$response2->json('errors')`

**Problema Raíz**: 
```
Rate limit debe retornar GraphQL error, pero retorna null
Indica que mutation completó sin error (cuando debería fallar)
```

**Diagnóstico**:
```php
// Test espera:
$this->assertNotNull($response2->json('errors'));
$this->assertStringContainsString('Too many', $response2->json('errors.0.message'));

// Pero consigue:
errors: null  // ← La mutation se ejecutó sin error cuando debería fallar

// Significa:
// Mutation no validó rate limit en backend
// O validación falló silenciosamente
```

**Soluciones a probar** (en orden):
1. Verificar que `PasswordResetService::requestReset()` valida rate limit
2. Verificar que rate limit lance excepción o retorne error
3. Verificar que GraphQL convierte excepción a error field
4. Agregar logging en service para ver por qué no falla

**Impacto**: CRÍTICO - Rate limiting no funciona

---

### 🟡 [4/15] FAILED: `enforces_2_emails_per_3_hours_limit`

**Línea**: 268  
**Error**: `Failed asserting that null is not null.` en `$response3->json('errors')`

**Problema Raíz**: Idéntico al test #3

**Diagnóstico**: Rate limit con ventana de 3 horas también no se valida

**Dependencia**: Bloqueado por solución de test #3

---

### 🔴 [5/15] FAILED: `can_reset_with_token`

**Línea**: 454  
**Error**: `Failed asserting that null is true.` en `success`

**Problema Raíz**: 
```
confirmPasswordReset mutation retorna null en lugar de PasswordResetResult
Significa: Lighthouse no mapea respuesta correctamente
```

**Diagnóstico**:
```php
// Mutation retorna array:
[
    'success' => true,
    'message' => '...',
    'accessToken' => '...',
    'refreshToken' => '...',
    'user' => [...]
]

// Pero GraphQL retorna:
null

// Causa probable:
// 1. PasswordResetResult type no tiene resolver correcto
// 2. Lighthouse no convierte array → PasswordResetResult
// 3. Mutation directiva incompleta
```

**Soluciones a probar** (en orden):
1. Verificar `PasswordResetResult` type en schema
   - ¿Tiene todos los fields? (success, message, accessToken, refreshToken, user)
   - ¿Tiene resolver? (@field directives)
2. Verificar mutation en schema
   - ¿Retorna PasswordResetResult? (no null)
3. Verificar que mutation resolver no lanza exception
4. Agregar logging en mutation para capturar exception silenciosa

**Impacto**: 🔴 CRÍTICO - Bloquea 8+ tests

**Root Cause esperado**: Lighthouse response mapping

---

### 🔴 [6/15] FAILED: `returns_access_token_after_reset`

**Línea**: 485  
**Error**: `Failed asserting that null is true.` en `success`

**Problema Raíz**: Idéntico a test #5 (success es null)

**Dependencia**: Bloqueado por solución de test #5

---

### 🔴 [7/15] FAILED: `auto_logs_in_user_after_reset`

**Línea**: 516  
**Error**: `Failed asserting that a NULL is not empty.` en `accessToken`

**Problema Raíz**: Idéntico a test #5 (accessToken es null porque success es null)

**Dependencia**: Bloqueado por solución de test #5

---

### 🔴 [8/15] FAILED: `invalidates_all_sessions_on_reset`

**Línea**: 549  
**Error**: `Failed asserting that true is false.` en `isSessionValid(session1)`

**Problema Raíz**: 
```
Sesiones previas NO fueron invalidadas tras reset
Mutation no ejecutó la invalidación de sesiones
```

**Diagnóstico**:
```php
// Test: Las sesiones anteriores deben ser inválidas tras reset
$this->assertFalse($this->isSessionValid($session1));
// Pero retorna true ← sesión aún válida

// Significa:
// confirmReset() no está revocando sesiones
// O la revocación no funciona correctamente
```

**Soluciones a probar** (en orden):
1. Verificar que `PasswordResetService::confirmReset()` revoca sesiones
2. Verificar que `revokeAllSessions()` o similar ejecuta correctamente
3. Verificar que tokens anteriores se invalidan en DB/cache
4. Agregar logging para ver si se llamó a revoke

**Impacto**: IMPORTANTE - Seguridad comprometida

**Nota**: Probablemente este test falla porque test #5 falla primero (mutation retorna null, reset nunca ocurre)

---

### 🔴 [9/15] FAILED: `cannot_reuse_same_reset_token_twice`

**Línea**: 645  
**Error**: `Failed asserting that null is true.` en `success`

**Problema Raíz**: Idéntico a test #5

**Dependencia**: Bloqueado por solución de test #5

---

### 🔴 [10/15] FAILED: `can_reset_with_6_digit_code`

**Línea**: 692  
**Error**: `Failed asserting that null is true.` en `success`

**Problema Raíz**: Idéntico a test #5

**Dependencia**: Bloqueado por solución de test #5

---

### 🔴 [11/15] FAILED: `cannot_reuse_same_reset_code_twice`

**Línea**: 769  
**Error**: `Failed asserting that null is true.` en `success`

**Problema Raíz**: Idéntico a test #5

**Dependencia**: Bloqueado por solución de test #5

---

### 🔴 [12/15] FAILED: `validates_code_belongs_to_correct_user`

**Línea**: 820  
**Error**: `Failed asserting that null is true.` en `success`

**Problema Raíz**: Idéntico a test #5

**Dependencia**: Bloqueado por solución de test #5

---

### 🔴 [13/15] FAILED: `cannot_use_code_from_different_user`

**Línea**: 849  
**Error**: `Failed asserting that null is true.` en `success`

**Problema Raíz**: Idéntico a test #5

**Dependencia**: Bloqueado por solución de test #5

---

### 🔴 [14/15] FAILED: `multiple_users_can_reset_independently`

**Línea**: 893  
**Error**: `Failed asserting that null is true.` en `success`

**Problema Raíz**: Idéntico a test #5

**Dependencia**: Bloqueado por solución de test #5

---

### 🟡 [15/15] FAILED: `password_reset_email_arrives_to_mailpit_with_token_and_code`

**Línea**: 953  
**Error**: `Failed asserting that null is not null.` en `resetEmail`

**Problema Raíz**: 
```
Email nunca llega a Mailpit
Test busca mensaje pero collection vacía
```

**Diagnóstico**:
```php
// Test intenta encontrar email en Mailpit:
$messages = $this->getMailpitMessages();
$resetEmail = collect($messages)->first(...);

// Pero consigue:
$resetEmail = null

// Significa:
// 1. Email nunca se envió a Mailpit
// 2. O Mailpit no está disponible
// 3. O MAIL_FROM_ADDRESS no coincide
```

**Soluciones a probar** (en orden):
1. Verificar que Mailpit está corriendo (`docker ps | grep mailpit`)
2. Verificar que `.env` tiene `MAIL_HOST=mailpit` y `MAIL_PORT=1025`
3. Verificar que email se dispara (investigar con test #1)
4. Verificar que PasswordResetMail tiene `->build()` correcto

**Impacto**: SECUNDARIO - Integración con Mailpit, no bloquea funcionalidad principal

---

## 🎯 ROADMAP DE SOLUCIÓN

### PHASE 1: CRÍTICO (Bloquea 90% de tests)
**Objetivo**: Arreglar `confirmPasswordReset` mutation response mapping

#### Step 1.1: Debug GraphQL Response Mapping
```bash
# Ruta:
/app/Features/Authentication/GraphQL/Mutations/ConfirmPasswordResetMutation.php
/app/Features/Authentication/GraphQL/Schema/authentication.graphql

# Acciones:
1. ✅ Verificar que PasswordResetResult tipo está definido
2. ✅ Verificar que mutation retorna array completo
3. ? Agregar logging en mutation para capturar exception silenciosa
4. ? Verificar @field directives en schema
5. ? Verificar Lighthouse config
```

**Tests que se desbloquean**: #5, #6, #7, #8, #9, #10, #11, #12, #13, #14 (10 tests)

---

### PHASE 2: IMPORTANTE (Bloquea rate limiting)
**Objetivo**: Arreglar validación de rate limiting en requestReset()

#### Step 2.1: Implementar Rate Limiting Validation
```bash
# Ruta:
/app/Features/Authentication/Services/PasswordResetService.php
/app/Features/Authentication/GraphQL/Mutations/ResetPasswordMutation.php

# Acciones:
1. Verificar que requestReset() valida rate limit
2. Lanzar excepción si rate limit excedido
3. Verificar que GraphQL convierte excepción a error field
4. Agregar test assertions para errores
```

**Tests que se desbloquean**: #3, #4 (2 tests)

---

### PHASE 3: SECUNDARIO (Email testing)
**Objetivo**: Arreglar email delivery en tests

#### Step 3.1: Investigar Email Dispatch
```bash
# Ruta:
/app/Features/Authentication/Listeners/SendPasswordResetEmail.php
/app/Features/Authentication/Jobs/SendPasswordResetEmailJob.php
/tests/Feature/Authentication/Mutations/PasswordResetCompleteTest.php

# Acciones:
1. Verificar que listener dispara job correctamente
2. Verificar test setup usa Mail::fake() + Queue::fake()
3. Verificar que jobs encolados se ejecutan en tests
4. Agregar $this->executeQueuedJobs() si es necesario
```

**Tests que se desbloquean**: #1, #2, #15 (3 tests)

---

## 🔧 DIAGRAMA DE DEPENDENCIAS

```
MAIN ISSUE: confirmPasswordReset returns null
│
├─→ [ROOT] GraphQL Response Mapping
│   ├─ Mutation resolver retorna array correcto?
│   ├─ PasswordResetResult type está correcto?
│   ├─ Lighthouse convierte array a tipo correctamente?
│   └─ Exception silenciosa ocurre?
│
├─→ BLOCKED BY: success: null (10 tests)
│   ├─ Test #5: can_reset_with_token
│   ├─ Test #6: returns_access_token_after_reset
│   ├─ Test #7: auto_logs_in_user_after_reset
│   ├─ Test #8: invalidates_all_sessions_on_reset
│   ├─ Test #9: cannot_reuse_same_reset_token_twice
│   ├─ Test #10: can_reset_with_6_digit_code
│   ├─ Test #11: cannot_reuse_same_reset_code_twice
│   ├─ Test #12: validates_code_belongs_to_correct_user
│   ├─ Test #13: cannot_use_code_from_different_user
│   └─ Test #14: multiple_users_can_reset_independently
│
├─→ SECONDARY ISSUE: Rate limiting not validated (2 tests)
│   ├─ Test #3: rate_limits_reset_resends_to_1_per_minute
│   └─ Test #4: enforces_2_emails_per_3_hours_limit
│
└─→ TERTIARY ISSUE: Email delivery in tests (3 tests)
    ├─ Test #1: sends_reset_email_with_token_and_code
    ├─ Test #2: email_contains_token_and_6_digit_code
    └─ Test #15: password_reset_email_arrives_to_mailpit_with_token_and_code
```

---

## 📝 PRÓXIMAS ACCIONES

### Acción Inmediata: Investigación GraphQL
**Prioridad**: 🔴 CRÍTICO

```bash
# 1. Leer mutation resolver
cat /app/Features/Authentication/GraphQL/Mutations/ConfirmPasswordResetMutation.php

# 2. Leer schema
cat /app/Features/Authentication/GraphQL/Schema/authentication.graphql | grep -A 20 "PasswordResetResult"

# 3. Agregar logging en mutation
# Editar mutation para capturar exception y retornar error GraphQL

# 4. Correr tests nuevamente
php artisan test tests/Feature/Authentication/Mutations/PasswordResetCompleteTest.php --filter="can_reset_with_token"
```

### Acción Secundaria: Rate Limiting
**Prioridad**: 🟡 IMPORTANTE

```bash
# 1. Verificar lógica de rate limit en PasswordResetService
# 2. Buscar dónde se lanza excepción si rate limit excedido
# 3. Verificar que GraphQL maneja excepción como error
```

### Acción Terciaria: Email Testing
**Prioridad**: 🟡 SECUNDARIO

```bash
# 1. Verificar listener dispatch de job
# 2. Verificar test setup Mail::fake() y Queue::fake()
# 3. Ejecutar jobs encolados en tests
```

---

## 📌 NOTA IMPORTANTE

**El 90% de los fallos depende de arreglar el issue de GraphQL response mapping.**

Una vez que `confirmPasswordReset` retorne `success: true` correctamente, esperamos que **10-12 tests pasen automáticamente**.

Estima: 1-2 horas debugging + implementación para resolver Phase 1 (CRÍTICO).
