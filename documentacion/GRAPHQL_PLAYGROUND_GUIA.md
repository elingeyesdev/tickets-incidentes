# Guía de Uso - GraphQL Playground / GraphiQL

## 🔐 Autenticación en GraphQL Playground

### Paso 1: Hacer Login

Ejecuta esta mutation para obtener tus tokens:

```graphql
mutation {
  login(input: {
    email: "demo@test.com"
    password: "password"
    rememberMe: false
  }) {
    accessToken
    refreshToken
    user {
      id
      email
      profile {
        firstName
        lastName
      }
    }
  }
}
```

**Respuesta esperada:**
```json
{
  "data": {
    "login": {
      "accessToken": "eyJ0eXAiOiJKV1QiLCJhbGc...",
      "refreshToken": "1029cb8268ed332d77ea...",
      "user": {
        "id": "uuid-aqui",
        "email": "demo@test.com",
        "profile": {
          "firstName": "Test",
          "lastName": "User"
        }
      }
    }
  }
}
```

### Paso 2: Configurar Headers

En GraphQL Playground/GraphiQL, ve a la sección **"HTTP HEADERS"** (abajo del panel de query) y agrega:

#### Opción 1: Token Directo (Recomendado para Playground)
```json
{
  "Authorization": "TU_ACCESS_TOKEN_AQUI"
}
```

#### Opción 2: Con "Bearer" (Estándar OAuth 2.0)
```json
{
  "Authorization": "Bearer TU_ACCESS_TOKEN_AQUI"
}
```

#### Para RefreshToken y Logout (agregar también):
```json
{
  "Authorization": "TU_ACCESS_TOKEN_AQUI",
  "X-Refresh-Token": "TU_REFRESH_TOKEN_AQUI"
}
```

### Paso 3: Ejecutar Queries Autenticadas

Ahora puedes ejecutar queries y mutations que requieren autenticación:

#### Refresh Token
```graphql
mutation {
  refreshToken {
    accessToken
    refreshToken
    tokenType
    expiresIn
  }
}
```

#### Ver Estado de Autenticación
```graphql
query {
  authStatus {
    user {
      id
      email
      profile {
        firstName
        lastName
      }
    }
    session {
      tokenExpiration
      isExpired
    }
  }
}
```

#### Logout
```graphql
mutation {
  logout(everywhere: false)
}
```

#### Logout Everywhere (cerrar todas las sesiones)
```graphql
mutation {
  logout(everywhere: true)
}
```

---

## ⚠️ Errores Comunes

### Error: "Authentication required: No valid token provided"

**Causa:** No configuraste el header Authorization o el formato es incorrecto.

**Solución:**
1. Verifica que copiaste el token completo (sin espacios al inicio/final)
2. Verifica que el header esté en la sección "HTTP HEADERS"
3. Verifica el formato: `"Authorization": "TOKEN"` o `"Authorization": "Bearer TOKEN"`

### Error: "Access token is invalid or has been revoked"

**Causas posibles:**
1. El token expiró (TTL: 60 minutos por defecto)
2. Hiciste logout y el token fue invalidado
3. Hiciste "logout everywhere" y todos los tokens fueron invalidados

**Solución:** Haz login nuevamente para obtener un token nuevo.

### Error: "Refresh token is invalid or has been revoked"

**Causas posibles:**
1. Ya usaste ese refresh token (token rotation - un refresh token solo se puede usar una vez)
2. Hiciste logout y el refresh token fue revocado
3. El refresh token expiró (TTL: 10080 minutos = 7 días por defecto)

**Solución:** Haz login nuevamente.

---

## 🔧 Formatos de Token Soportados

El sistema soporta **2 formatos** para el header Authorization:

### 1. Token Directo (Playground friendly)
```
Authorization: eyJ0eXAiOiJKV1QiLCJhbGc...
```

### 2. Bearer Token (Estándar OAuth 2.0)
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

Ambos formatos funcionan correctamente. El middleware automáticamente detecta cuál estás usando.

---

## 🧪 Testing con curl

### Login
```bash
curl -X POST http://localhost:8000/graphql \
  -H "Content-Type: application/json" \
  -d '{"query":"mutation { login(input: {email: \"demo@test.com\", password: \"password\", rememberMe: false}) { accessToken refreshToken } }"}'
```

### Query Autenticada (con Bearer)
```bash
curl -X POST http://localhost:8000/graphql \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -d '{"query":"query { authStatus { user { email } } }"}'
```

### Query Autenticada (sin Bearer)
```bash
curl -X POST http://localhost:8000/graphql \
  -H "Content-Type: application/json" \
  -H "Authorization: TU_TOKEN_AQUI" \
  -d '{"query":"query { authStatus { user { email } } }"}'
```

### Refresh Token
```bash
curl -X POST http://localhost:8000/graphql \
  -H "Content-Type: application/json" \
  -H "Authorization: TU_ACCESS_TOKEN" \
  -H "X-Refresh-Token: TU_REFRESH_TOKEN" \
  -d '{"query":"mutation { refreshToken { accessToken refreshToken } }"}'
```

---

## 📝 Notas Importantes

### Token Rotation
- **Cada vez que haces refresh token, el refresh token anterior se INVALIDA**
- Esto previene ataques de replay
- Si intentas usar el mismo refresh token dos veces, obtendrás error

### Logout Behavior
- **`logout(everywhere: false)`**: Solo cierra la sesión actual (invalida solo este access token y refresh token)
- **`logout(everywhere: true)`**: Cierra TODAS las sesiones (invalida todos los access tokens y refresh tokens del usuario)

### TTL (Time To Live)
- **Access Token**: 60 minutos
- **Refresh Token**: 7 días
- **Email Verification Token**: 24 horas
- **Password Reset Token**: 60 minutos

### Blacklist
- Los access tokens invalidados se agregan a una blacklist en Redis
- La blacklist se limpia automáticamente cuando el token expira naturalmente
- Esto permite logout inmediato sin esperar a que expire el token

---

## 🎯 Usuario de Prueba

Ya existe un usuario creado para pruebas:

```
Email: demo@test.com
Password: password
```

Puedes usar este usuario en GraphQL Playground para probar todas las funcionalidades.

---

## 📚 Documentación Adicional

- **Schema GraphQL**: `/graphql/shared/` (scalars, directives, enums)
- **Feature Schemas**: `app/Features/*/GraphQL/Schema/*.graphql`
- **Error Handling**: `documentacion/SISTEMA_ERRORES_GRAPHQL_IMPLEMENTADO.md`
- **Authentication Feature**: `documentacion/AUTHENTICATION FEATURE - DOCUMENTACIÓN.txt`

---

**Última actualización:** 09-Oct-2025
**Versión:** 1.0
