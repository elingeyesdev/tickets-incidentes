# 📚 Laravel Lighthouse GraphQL - Guía Completa

## 🎯 Información clave extraída de la documentación oficial

Esta guía contiene toda la información importante sobre Laravel Lighthouse GraphQL obtenida durante la implementación del Schema First.

---

## 🏗️ Arquitectura y Estructura de Archivos

### **Organización Recomendada**
```
graphql/
├── schema.graphql              # Schema principal
└── features/                   # Schemas por feature (imports)
    ├── authentication.graphql
    ├── userManagement.graphql
    └── companyManagement.graphql

app/
├── GraphQL/
│   ├── Scalars/               # Scalars personalizados
│   │   ├── UUID.php
│   │   ├── Email.php
│   │   └── ...
│   ├── Directives/            # Directivas personalizadas
│   │   ├── RateLimitDirective.php
│   │   └── AuditDirective.php
│   ├── Queries/               # Resolvers de Query
│   ├── Mutations/             # Resolvers de Mutation
│   └── Types/                 # Types personalizados
└── Features/
    ├── Authentication/
    │   └── GraphQL/
    │       ├── Queries/
    │       ├── Mutations/
    │       └── DataLoaders/   # DataLoaders por feature
    └── ...
```

---

## 🎨 Scalars Personalizados

### **Implementación Correcta**

```php
<?php
namespace App\GraphQL\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

class UUID extends ScalarType
{
    public $name = 'UUID';  // ⚠️ IMPORTANTE: Sin type hint
    public $description = 'A UUID string in 8-4-4-4-12 format';

    public function serialize($value): string
    {
        return (string) $value;
    }

    public function parseValue($value): string
    {
        if (!is_string($value) || !Uuid::isValid($value)) {
            throw new Error('Value is not a valid UUID: ' . $value);
        }
        return $value;
    }

    public function parseLiteral($valueNode, ?array $variables = null): string
    {
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Can only parse strings to UUIDs but got a: ' . $valueNode->kind);
        }
        return $valueNode->value;
    }
}
```

### **Registro en Schema**
```graphql
# En schema.graphql
scalar UUID @scalar(class: "App\\GraphQL\\Scalars\\UUID")
scalar Email @scalar(class: "App\\GraphQL\\Scalars\\Email")
```

### **⚠️ Errores Comunes**
- **NO usar type hints** en `$name`: `public string $name` → ❌ Error
- **SÍ usar**: `public $name = 'UUID'` → ✅ Correcto
- Lighthouse registra automáticamente en namespace `App\GraphQL\Scalars`

---

## 🎛️ Directivas Personalizadas

### **Implementación de Directiva**

```php
<?php
namespace App\GraphQL\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Nuwave\Lighthouse\Schema\Values\FieldValue;

class RateLimitDirective extends BaseDirective implements FieldMiddleware
{
    public function handleField(FieldValue $fieldValue): void
    {
        $fieldValue->wrapResolver(fn (callable $resolver) => function (...$args) use ($resolver) {
            // Lógica de rate limiting aquí
            return $resolver(...$args);
        });
    }

    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'GRAPHQL'
        """
        Rate limit field access
        """
        directive @rateLimit(
            "Maximum number of requests"
            max: Int!
            "Time window in seconds"
            window: Int!
            "Custom error message"
            message: String
        ) on FIELD_DEFINITION
        GRAPHQL;
    }
}
```

### **Uso en Schema**
```graphql
type Mutation {
    login(input: LoginInput!): AuthPayload!
        @rateLimit(max: 5, window: 15, message: "Too many login attempts")
}
```

### **Directivas Built-in de Lighthouse**
- `@auth` - Autenticación requerida
- `@can(ability: "view", model: "User")` - Autorización
- `@cache(ttl: 300)` - Cache de resultados
- `@field(resolver: "...")` - Resolver personalizado
- `@rules(apply: ["required", "email"])` - Validación

---

## 📋 Schema Patterns

### **Imports de Archivos**
```graphql
# En schema.graphql principal
#import features/authentication.graphql
#import features/userManagement.graphql
```

### **Extend Types (Features)**
```graphql
# En features/authentication.graphql
extend type Query {
    authStatus: AuthStatus @auth
}

extend type Mutation {
    login(input: LoginInput!): AuthPayload!
}
```

### **Resolvers por Feature**
```graphql
# Resolver específico por feature
authStatus: AuthStatus
    @auth
    @field(resolver: "App\\Features\\Authentication\\GraphQL\\Queries\\AuthStatusQuery")
```

---

## 🔧 Configuración y Setup

### **Instalación**
```bash
composer require nuwave/lighthouse pusher/pusher-php-server
php artisan vendor:publish --tag=lighthouse-schema
php artisan vendor:publish --tag=lighthouse-config
```

### **Rutas GraphQL**
- Endpoint principal: `/graphql`
- GraphiQL playground: `/graphiql` (development)
- Introspección habilitada por defecto

### **Configuración CORS**
```php
// config/cors.php
'paths' => ['api/*', 'graphql', 'graphiql'],
```

---

## 🎯 Schema First Methodology

### **Flujo Recomendado**
1. **Diseñar Schema** sin resolvers (solo tipos)
2. **Validar en Apollo Studio** - estructura y tipos
3. **Implementar resolvers** con datos dummy
4. **Iterar** hasta schema perfecto
5. **Implementar lógica real** en fase 2

### **Beneficios**
- ✅ Validación temprana de tipos
- ✅ Detección de loops infinitos
- ✅ Frontend puede empezar con datos dummy
- ✅ Arquitectura sólida antes de implementar

---

## 🚫 Evitar Loops Infinitos

### **❌ Problema: Loop Infinito**
```graphql
type User {
    id: ID!
    company: Company  # ← User apunta a Company
}

type Company {
    id: ID!
    users: [User!]!   # ← Company apunta a Users
}
```

### **✅ Solución: Tipos Simplificados**
```graphql
# En Authentication feature
type AuthUser {        # ← Tipo simplificado
    id: UUID!
    email: Email!
    # NO incluir company o relaciones complejas
}

# En Company feature
type Company {
    id: UUID!
    name: String!
    userCount: Int!    # ← Contador en lugar de lista
}
```

---

## 🧪 Testing y Debugging

### **Introspección**
```bash
# Test básico
curl -X POST http://localhost:8000/graphql \
  -H "Content-Type: application/json" \
  -d '{"query": "{ __schema { queryType { name } } }"}'
```

### **Validación de Schema**
```bash
php artisan lighthouse:validate-schema
```

### **Queries de Prueba Apollo Studio**
```graphql
# Test de conexión
query TestConnection {
  __schema {
    queryType { name }
    mutationType { name }
  }
}

# Test de tipos personalizados
query TestCustomTypes {
  __type(name: "AuthPayload") {
    fields {
      name
      type { name }
    }
  }
}
```

---

## ⚡ Optimización y Performance

### **DataLoaders (Prevenir N+1)**
```php
// En cada feature: app/Features/*/GraphQL/DataLoaders/
namespace App\Features\Authentication\GraphQL\DataLoaders;

class UserDataLoader extends BaseDataLoader
{
    public function batchLoad(array $keys): array
    {
        // Batch loading logic
        return User::whereIn('id', $keys)->get()->keyBy('id')->toArray();
    }
}
```

### **Caching**
```graphql
type Query {
    publicCompanies: [Company!]!
        @cache(ttl: 300, key: "public_companies")
}
```

---

## 🔒 Seguridad y Autenticación

### **Authentication Flow**
```graphql
type Query {
    me: User @auth                    # Requiere autenticación
    adminUsers: [User!]! @auth(guards: ["admin"])  # Guard específico
}

type Mutation {
    updateUser(id: ID!, input: UpdateUserInput!): User!
        @auth
        @can(ability: "update", model: "User", find: "id")
}
```

### **Rate Limiting**
```graphql
type Mutation {
    login(input: LoginInput!): AuthPayload!
        @rateLimit(max: 5, window: 15)

    resetPassword(email: Email!): Boolean!
        @rateLimit(max: 3, window: 60)
}
```

---

## 🛠️ Comandos Útiles

```bash
# Generar resolver
php artisan lighthouse:query UserQuery
php artisan lighthouse:mutation CreateUser

# Generar directiva
php artisan lighthouse:directive --field RateLimit

# Generar scalar
php artisan lighthouse:scalar UUID

# Validar schema
php artisan lighthouse:validate-schema

# Cache clear (si hay problemas)
php artisan config:clear
php artisan cache:clear
```

---

## ❌ Errores Comunes y Soluciones

### **1. Schema Syntax Error**
```
Error: Expected Name, found }
```
**Solución**: Types no pueden estar vacíos
```graphql
# ❌ Malo
type Mutation { }

# ✅ Bueno
type Mutation {
    _: String  # Placeholder
}
```

### **2. Scalar Class Not Found**
```
Failed to find class UUID extends ScalarType
```
**Solución**: Verificar namespace y implementación

### **3. Resolver Not Found**
```
Could not locate a field resolver for "fieldName"
```
**Solución**: Agregar `@field(resolver: "...")` o implementar resolver

### **4. Loops Infinitos Apollo**
**Solución**: Usar tipos simplificados por feature, evitar relaciones bidireccionales

---

## 🎯 Next Steps para Implementación Completa

### **Phase 1: Schema Validation** ✅
- [x] Estructura de archivos
- [x] Scalars básicos funcionando
- [x] Schema validando en Apollo

### **Phase 2: Dummy Resolvers**
- [ ] Implementar resolvers que retornen datos dummy
- [ ] Probar queries completas en Apollo
- [ ] Validar no hay loops infinitos

### **Phase 3: Real Implementation**
- [ ] Conectar a base de datos real
- [ ] Implementar lógica de negocio
- [ ] JWT authentication
- [ ] DataLoaders para performance

---

**🚀 Con esta guía tienes toda la información necesaria para continuar el desarrollo GraphQL siguiendo las mejores prácticas de Laravel Lighthouse.**