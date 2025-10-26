# Password Reset Tests Setup Guide

## Overview

Los tests de reset de contraseña están configurados para usar **Redis** como almacenamiento de caché, lo que permite pruebas más realistas del flujo completo incluyendo:

- Token storage y retrieval
- Code generation y validation  
- TTL management
- Session invalidation
- Email queuing

## Configuration

### phpunit.xml

El archivo `phpunit.xml` ha sido configurado con:

```xml
<!-- Use Redis for cache, sessions, and queue -->
<env name="CACHE_DRIVER" value="redis"/>
<env name="CACHE_PREFIX" value="helpdesk_test_cache:"/>
<env name="REDIS_HOST" value="redis"/>
<env name="REDIS_PASSWORD" value="null"/>
<env name="REDIS_PORT" value="6379"/>
<env name="REDIS_CLIENT" value="phpredis"/>
<env name="QUEUE_CONNECTION" value="redis"/>
<env name="SESSION_DRIVER" value="redis"/>
```

### Prerequisites

Para ejecutar los tests, necesitas:

1. **Redis Container** ejecutándose:
   ```bash
   docker-compose up redis -d
   ```

2. **PostgreSQL Container** para la base de datos de tests:
   ```bash
   docker-compose up postgres -d
   ```

3. **Mailpit Container** para capturar emails:
   ```bash
   docker-compose up mailpit -d
   ```

## Test Structure

### `PasswordResetMutationTest.php`

Pruebas organizadas en 4 secciones principales:

#### 1. resetPassword Mutation Tests
- ✅ Usuario puede solicitar reset de contraseña
- ✅ Email no existente retorna true (por seguridad)
- ✅ Token generado en Redis Cache
- ✅ Email enviado con token y código de 6 dígitos
- ✅ Rate limiting (máximo 3 intentos)

#### 2. passwordResetStatus Query Tests
- ✅ Validar token válido
- ✅ Retornar tiempo de expiración
- ✅ Rechazar token inválido
- ✅ Rechazar token expirado

#### 3. confirmPasswordReset Mutation Tests
- ✅ Confirmar reset con token
- ✅ Retornar accessToken y refreshToken
- ✅ Auto-login del usuario
- ✅ Invalidar todas las sesiones previas
- ✅ Validar token existe
- ✅ Validar token no expirado
- ✅ Validar requisitos de contraseña
- ✅ Usar código de 6 dígitos como alternativa
- ✅ Rechazar si falta token y código

#### 4. Hybrid Flow Tests
- ✅ Validar token → Confirmar con código

### Redis Test Helper

`tests/Helpers/RedisTestHelper.php` proporciona métodos útiles:

```php
RedisTestHelper::exists($key)              // Verificar existencia
RedisTestHelper::get($key)                 // Obtener valor
RedisTestHelper::put($key, $value, $ttl)  // Establecer valor
RedisTestHelper::keys($pattern)            // Obtener claves por patrón
RedisTestHelper::ttl($key)                 // Obtener TTL en segundos
RedisTestHelper::isConnected()             // Verificar conexión
RedisTestHelper::debugInfo()               // Info de debug
```

## Running Tests

### Ejecutar todos los tests de password reset:

```bash
./vendor/bin/phpunit tests/Feature/Authentication/Mutations/PasswordResetMutationTest.php
```

### Ejecutar test específico:

```bash
./vendor/bin/phpunit tests/Feature/Authentication/Mutations/PasswordResetMutationTest.php --filter user_can_request_password_reset
```

### Ejecutar con output verboso:

```bash
./vendor/bin/phpunit tests/Feature/Authentication/Mutations/PasswordResetMutationTest.php --verbose
```

## Setup/Teardown

Cada test automáticamente:

1. **Setup**: Limpia Redis completamente antes
2. **Teardown**: Limpia Redis después

```php
protected function setUp(): void
{
    parent::setUp();
    Redis::flushDb(); // Limpia todos los datos
}

protected function tearDown(): void
{
    Redis::flushDb();
    parent::tearDown();
}
```

## Expected Behavior

### resetPassword Flow
```
1. User requests reset with email
2. Token (32 chars) generated in Redis
3. Code (6 digits) generated in Redis
4. Both stored with 24-hour TTL
5. Email job queued with both token and code
6. Response: { data: { resetPassword: true } }
```

### confirmPasswordReset Flow
```
1. User provides token OR code
2. Validate exists and not expired
3. Update user password hash
4. Invalidate all active sessions
5. Generate new JWT access token
6. Return { success: true, accessToken, refreshToken, user }
```

## Debugging

### Verificar estado de Redis:

```php
dump(RedisTestHelper::debugInfo());
```

Output esperado:
```
[
    'connected' => true,
    'keys_count' => 5,
    'memory_info' => [...],
    'prefix' => 'helpdesk_test_cache:'
]
```

### Ver todas las claves en Redis:

```php
dump(RedisTestHelper::keys('password_reset*'));
```

## Troubleshooting

### Error: "Connection refused"

```
Redis::set() failed with error: Operation timed out
```

**Solución**: Asegurar que Redis container está running:
```bash
docker-compose ps redis
# Si no está running:
docker-compose up redis -d
```

### Error: "Key not found in Redis"

Si `RedisTestHelper::exists()` retorna false:

1. Verificar que la mutation fue ejecutada
2. Revisar que el prefijo está correcto: `helpdesk_test_cache:`
3. Usar `RedisTestHelper::keys('*')` para ver todas las claves

### Email no enviado

Asegurar que:

1. Queue driver es `redis` en phpunit.xml
2. Mail driver es `smtp` (Mailpit)
3. Ejecutar `$this->executeQueuedJobs()` en el test

## Next Steps

Una vez que estos tests pasen:

1. Implementar `ResetPasswordResolver`
2. Implementar `ConfirmPasswordResetResolver`
3. Implementar `PasswordResetStatusResolver`
4. Crear `PasswordResetMail` mailable
5. Crear `SendPasswordResetEmail` job
6. Crear `PasswordResetListener` para el evento
7. Agregar validaciones de seguridad y rate limiting
