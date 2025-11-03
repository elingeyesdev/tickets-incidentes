# Análisis Completo: Estructura del JWT en Helpdesk System

**Fecha de Análisis:** 02-Noviembre-2025
**Rama:** feature/graphql-to-rest-migration
**Estado:** Migración completa a JWT puro

---

## 1. UBICACIÓN DEL CÓDIGO JWT

### Servicios Principales
- **TokenService**: `app/Features/Authentication/Services/TokenService.php`
- **AuthService**: `app/Features/Authentication/Services/AuthService.php`

### Middlewares JWT
- **JWTAuthenticationMiddleware**: `app/Http/Middleware/JWT/JWTAuthenticationMiddleware.php`
- **JWTAuthenticationTrait**: `app/Shared/Traits/JWTAuthenticationTrait.php`

### Configuración
- **config/jwt.php** - Configuración de JWT
- **config/auth.php** - Configuración de autenticación

---

## 2. CÓMO SE GENERA EL ACCESS TOKEN

### Ubicación: TokenService::generateAccessToken()

Payload generado:
```
{
  "iss": "helpdesk-api",
  "aud": "helpdesk-frontend",
  "iat": <timestamp>,
  "exp": <timestamp + 3600>,
  "sub": "<user_id>",
  "user_id": "<user_id>",
  "email": "<user_email>",
  "session_id": "<session_id>"
}
```

**Configuración:**
- TTL: 60 minutos (config/jwt.php)
- Algoritmo: HS256
- Clave: env(JWT_SECRET) o env(APP_KEY)

---

## 3. CLAIMS ACTUALES DEL TOKEN JWT

### Claims Estándar (RFC 7519)
- `iss`: "helpdesk-api"
- `aud`: "helpdesk-frontend"  
- `iat`: timestamp de emisión
- `exp`: timestamp de expiración
- `sub`: user_id

### Claims Personalizados
- `user_id`: UUID del usuario
- `email`: email del usuario
- `session_id`: ID único de la sesión

**Total: 8 claims**

---

## 4. ¿QUÉ INFORMACIÓN NO ESTÁ EN EL TOKEN?

### AUSENTE (no incluidos):
- Roles del usuario (no están en el payload JWT)
- ID de compañía (no están en el payload JWT)
- Permisos específicos (no están en el payload JWT)

### Cómo se obtiene:
1. Se valida el token y se extrae user_id
2. Se carga: User::with('activeRoles')->find($user_id)
3. Se obtienen roles y companies desde la BD en cada request

---

## 5. FLUJO DE VALIDACIÓN

1. Cliente envía: `Authorization: Bearer <TOKEN>`
2. Middleware extrae token
3. TokenService::validateAccessToken()
   - Decodifica JWT y valida firma
   - Verifica claims requeridos
   - Checkea blacklist
4. Si es válido, carga User con relaciones
5. Almacena en request attributes

---

## 6. MODELOS RELACIONADOS

### User Model
- Relación: `hasMany('userRoles')`
- Método: `activeRoles()` - solo roles activos
- Método: `hasRole($roleCode)` - verificar rol
- Método: `hasRoleInCompany($roleCode, $companyId)`

### UserRole Model
- FK: user_id
- FK: role_code (VARCHAR)
- FK: company_id (NULLABLE)
- Relación: `belongsTo('Company')`

---

## 7. REFRESH TOKEN

### Almacenamiento:
- Tabla: auth.refresh_tokens
- Token plano se genera: bin2hex(random_bytes(32))
- Se guarda hash SHA256 en BD
- TTL: 30 días

### Rotación:
- Al refrescar, se invalida token viejo
- Se genera nuevo token
- Se crea nuevo access token

---

## 8. BLACKLIST Y LOGOUT

### Blacklist:
- Cache key: `jwt_blacklist:{session_id}`
- Almacena: true
- TTL: duración del token

### Logout Everywhere:
- Cache key: `jwt_user_blacklist:{user_id}`
- Almacena: timestamp
- Invalida todos los tokens emitidos antes

---

## 9. INFORMACIÓN IMPORTANTE

### Por qué roles no están en el token?
✅ Token más pequeño
✅ Cambios de roles se reflejan inmediatamente
✅ Datos siempre actualizados desde BD

### Si necesitas agregar roles al token:
```php
$payload['roles'] = $user->getRoleCodes();
$payload['companies'] = $user->activeRoles()
    ->whereNotNull('company_id')
    ->pluck('company_id');
```

---

