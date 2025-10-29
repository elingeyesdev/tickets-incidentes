# 🔍 PROMPT: Exploración CodeBase UserManagement (Pre-Migración)

## 📋 Objetivo

Explorar el codebase existente de **UserManagement Feature** para identificar complejidades reales, dependencias ocultas y decisiones de arquitectura que impactarán la migración de GraphQL a REST.

**Entrega esperada:** Un reporte detallado que permita formular **reglas de migración realistas y precisas**.

---

## 🎯 Secciones a Explorar (En Orden)

### SECCIÓN 1: Estructura de Archivos y Organización

**Busca y reporta:**

```
1. Ubicación y estructura de:
   □ Resolvers GraphQL: app/Features/UserManagement/GraphQL/Queries/
   □ Mutations GraphQL: app/Features/UserManagement/GraphQL/Mutations/
   □ Services: app/Features/UserManagement/Services/
   □ Models: app/Models/ (User, Role, UserRole, etc.)
   □ Exceptions: app/Features/UserManagement/Exceptions/
   □ Requests (Form Requests): app/Features/UserManagement/Http/Requests/
   □ Resources: app/Features/UserManagement/Http/Resources/

2. Para CADA carpeta encontrada, reporta:
   ✓ Archivos que contiene
   ✓ Líneas de código aproximadas
   ✓ Dependencias externas (qué imports/packages usa)
   ✓ Complejidad percibida (simple, media, alta)

3. ¿Existe ya carpeta Http/ con Controllers?
   ✓ Si existe: listar contenido
   ✓ Si NO existe: será necesario crear
```

**DESPUÉS de explorar, EDITA el archivo .md:**

```bash
# Abre: documentacion/REPORTE_EXPLORACION_USERMANAGEMENT.md

# Busca esta línea:
## 1. Estructura de Archivos
[Por completar]

# Reemplázala con:
## 1. Estructura de Archivos
✅ COMPLETADO - [Tu nombre], [hora]

### 📂 Árbol de Directorios

#### GraphQL Queries
**Ubicación:** app/Features/UserManagement/GraphQL/Queries/
**Archivos encontrados:** [Número]
**Líneas totales:** [Número]
**Archivos:**
- [NombreArchivo.php] → [Líneas] líneas, complejidad: [simple/media/alta]
- [NombreArchivo.php] → [Líneas] líneas, complejidad: [simple/media/alta]

#### GraphQL Mutations
**Ubicación:** app/Features/UserManagement/GraphQL/Mutations/
**Archivos encontrados:** [Número]
**Líneas totales:** [Número]
**Archivos:**
- [NombreArchivo.php] → [Líneas] líneas, complejidad: [simple/media/alta]

#### Services
**Ubicación:** app/Features/UserManagement/Services/
**Archivos encontrados:** [Número]
**Líneas totales:** [Número]
**Archivos:**
- [NombreArchivo.php] → [Líneas] líneas, complejidad: [simple/media/alta]

#### Models (En app/Models/)
**Archivos encontrados:** [Número]
- User.php → [Líneas] líneas
- Role.php → [Líneas] líneas
- [Otros]

#### Exceptions
**Ubicación:** app/Features/UserManagement/Exceptions/
**Archivos encontrados:** [Número]
- [NombreExcepción.php]

#### Form Requests (HTTP)
**Ubicación:** app/Features/UserManagement/Http/Requests/
**Archivos encontrados:** [Número]
**Nota:** ¿Carpeta Http/ ya tiene esta subcarpeta? [SÍ/NO]
- [NombreRequest.php]

#### Resources (HTTP)
**Ubicación:** app/Features/UserManagement/Http/Resources/
**Archivos encontrados:** [Número]
**Nota:** ¿Carpeta Http/Resources/ ya existe? [SÍ/NO]
- [NombreResource.php]

### 📊 Estadísticas Generales
- **Archivos totales:** [Número]
- **Líneas de código totales:** [Número]
- **Complejidad PROMEDIO:** [Baja/Media/Alta]
- **¿Carpeta Http/ existe para REST?** [SÍ/NO]
- **¿Controllers ya existen?** [SÍ/NO]

### ⚠️ Observaciones Iniciales
[Aquí agrega cualquier cosa que notaste importante]

---
```

**Luego:**
```bash
# Guarda el archivo
git add documentacion/REPORTE_EXPLORACION_USERMANAGEMENT.md
git commit -m "docs: complete section 1 - file structure exploration"

# Pasa a SECCIÓN 2
```

---

---

### SECCIÓN 2: Dataloaders - Análisis Crítico

**Busca y reporta:**

```
1. Ubicación de dataloaders:
   □ app/Shared/GraphQL/DataLoaders/
   □ Otros ubicaciones? (buscar archivos *DataLoader.php)

2. Para CADA dataloader encontrado, reporta:
   ✓ Nombre: ej. CompanyDataLoader
   ✓ ¿Qué resource batches? (ej. companies, roles, etc.)
   ✓ Código del método de carga:
     - ¿Recibe array de IDs?
     - ¿Retorna Collection keyed por ID?
     - ¿Tiene lógica de transformación?
   ✓ ¿Dónde se invoca?
     - En Resolvers GraphQL (¿cuáles?)
     - En Services (¿cuáles?)
     - En ambos?

3. Complejidad de cada dataloader:
   ✓ Líneas de código
   ✓ Número de operaciones (queries, transformaciones)
   ✓ ¿Cachea resultados?
   ✓ ¿Tiene fallback/default?

4. Pregunta crítica:
   ¿Algún dataloader se invoca SOLO en Resolver GraphQL?
   (Si es sí: será necesario MOVER al Service antes de usar en REST)
```

**DESPUÉS de explorar, EDITA el archivo .md:**

```bash
# Abre: documentacion/REPORTE_EXPLORACION_USERMANAGEMENT.md

# Busca esta línea:
## 2. Dataloaders
[Por completar]

# Reemplázala con:
## 2. Dataloaders
✅ COMPLETADO - [Tu nombre], [hora]

### 📊 Dataloaders Encontrados: [Número]

#### [NombreDataLoader] #1
**Ubicación:** app/Shared/GraphQL/DataLoaders/CompanyDataLoader.php
**Líneas:** [Número]
**Resource que batches:** companies
**Método principal:**
\`\`\`php
[Pega el código exacto del método load()]
\`\`\`
**¿Recibe array de IDs?** SÍ/NO
**¿Retorna Collection keyed?** SÍ/NO
**¿Tiene transformación?** SÍ/NO
**Complejidad:** [simple/media/alta]

**Invocado en:**
- Resolver: [NombreResolver.php] (línea X)
  - Contexto: ¿Cómo se invoca?
  - Parámetros: ¿Qué IDs le pasa?
- Resolver: [Otro Resolver] (línea Y)
- Service: [NombreService.php] (línea Z)
- Otros: [Listar]

**Observación:** ¿Se puede reutilizar en REST? [SÍ/NO] - ¿Por qué?

---

#### [Siguiente Dataloader] #2
[Repetir estructura de arriba]

---

### 🚨 Hallazgos Críticos - Dataloaders

**Dataloader(s) solo en Resolver:** [SÍ/NO]
- Si SÍ: listar cuáles y por qué
- Acción: Estos necesitarán MOVER a Service

**Dataloader(s) compartidos (Shared):** [SÍ/NO]
- Ubicación exacta
- Pueden reutilizarse en REST

**Dataloader(s) agnósticos:** [Número]
- Listan los que NO dependen de GraphQL

**⚠️ Riesgo de N+1 si NO se reutilizan:** [ALTO/MEDIO/BAJO]

---
```

**Luego:**
```bash
# Guarda el archivo
git add documentacion/REPORTE_EXPLORACION_USERMANAGEMENT.md
git commit -m "docs: complete section 2 - dataloaders analysis"

# Pasa a SECCIÓN 3
```

**EJEMPLO DE REPORTE:**
```
CompanyDataLoader:
  Location: app/Shared/GraphQL/DataLoaders/CompanyDataLoader.php
  Lines: 25
  Batches: companies
  Invoked in:
    - UserManagement Queries (roleContexts.company)
    - RoleManagement Queries (company.data)
  Code: static function load($ids) { return Company::whereIn('id', $ids)->get()->keyBy('id'); }
  Complexity: Simple (solo query + keyBy)
  Needs refactor: NO (ya está en compartido)
```

---

---

### SECCIÓN 3: Services - Análisis de Lógica de Negocio

**Busca y reporta:**

```
1. Lista TODOS los Services en UserManagement:
   □ UserService
   □ RoleService
   □ ProfileService
   □ Otros

2. Para CADA Service, reporta:
   ✓ Nombre completo del archivo
   ✓ Métodos públicos (nombra TODOS):
     - Parámetros que recibe
     - Qué retorna
     - Si invoca otros Services
     - Si invoca Dataloaders
   ✓ Métodos privados complejos (si existen)
   ✓ Excepciones que lanza (específicas del negocio)

3. Método por método de UserService:
   ┌─ getMe()
   │  ├─ Parámetros: none
   │  ├─ Retorna: User con relationships?
   │  ├─ Invoca: roleContexts? profile?
   │  ├─ Dataloader: ¿cuál?
   │  └─ Complejidad: simple/media/alta
   │
   └─ (repetir para TODOS los métodos)

4. ¿Hay métodos que SOLO se usan desde Resolvers?
   (Si es sí: podría haber lógica acoplada a GraphQL)

5. ¿Hay métodos que se reutilizan en Authentication?
   (Si es sí: cuidado con cambios, impacta login)
```

**DESPUÉS de explorar, EDITA el archivo .md:**

```bash
# Busca en el archivo:
## 3. Services
[Por completar]

# Reemplázala con:
## 3. Services
✅ COMPLETADO - [Tu nombre], [hora]

### 📋 Services Encontrados: [Número Total]

#### [NombreService].php - [Líneas] líneas
**Ubicación:** app/Features/UserManagement/Services/[NombreService].php
**Complejidad:** [simple/media/alta]

**Métodos Públicos:**

**1. metodoNombre(parámetros)**
- Parámetros: $param1 (type), $param2 (type)
- Retorna: [Tipo de retorno]
- Invoca otros Services: [SÍ/NO] - cuáles
- Invoca Dataloaders: [SÍ/NO] - cuáles
- Excepciones que lanza: [ExceptionNombre, Otra]
- Complejidad: [simple/media/alta]
- Reutilizable en REST: [SÍ/NO] - ¿por qué?
- **Nota:** [Cualquier observación importante]

**2. otroMetodo(...)**
[Repetir estructura de arriba]

**Métodos Privados Complejos:**
- privatoComplejo() - [Líneas] líneas - [Observación]

---

#### [OtroService].php - [Líneas] líneas
[Repetir estructura anterior]

---

### 🔗 Dependencias entre Services
- [Service A] usa [Service B]
- [Service C] usa [Service A] y [Service B]
- [Mapear el grafo de dependencias]

### 🚨 Hallazgos Críticos - Services

**Métodos SOLO en Resolvers (posible acoplamiento):**
- [Listar si existen]

**Métodos reutilizados en Authentication:**
- [Listar cuáles - CUIDADO CON CAMBIOS]

**Services agnósticos a GraphQL:**
- [Listar - se pueden reutilizar directamente]

---
```

**Luego:**
```bash
git add documentacion/REPORTE_EXPLORACION_USERMANAGEMENT.md
git commit -m "docs: complete section 3 - services analysis"

# Pasa a SECCIÓN 4
```

**EJEMPLO DE REPORTE:**
```
UserService:
  Location: app/Features/UserManagement/Services/UserService.php
  Lines: 150

  Public Methods:
  ├─ getMe()
  │  ├─ Params: none (usa auth()->user())
  │  ├─ Returns: User with profile, roleContexts
  │  ├─ Invokes: CompanyDataLoader->load($companyIds)
  │  ├─ Exceptions: none
  │  └─ Complexity: Media (batching + transformations)
  │
  ├─ updateProfile(userId, data)
  │  ├─ Params: UUID, array{firstName, lastName, phone}
  │  ├─ Returns: User (updated)
  │  ├─ Invokes: none
  │  ├─ Exceptions: UserNotFoundException, ValidationException
  │  └─ Complexity: Baja
  │
  └─ ...rest of methods

  Reused outside UserManagement: YES (Authentication uses getMe)
```

---

---

### SECCIÓN 4: GraphQL Resolvers - Análisis Detallado

**Busca y reporta:**

```
1. Lista TODOS los Resolvers (Queries): MeQuery, MyProfileQuery, UsersQuery, UserQuery, AvailableRolesQuery
2. Lista TODOS los Resolvers (Mutations): UpdateMyProfileMutation, UpdateMyPreferencesMutation, etc.

Para CADA Query/Mutation:
   ✓ Archivo y número de líneas
   ✓ Código EXACTO del método resolve()
   ✓ ¿Autentica? ¿Cómo?
   ✓ ¿Autoriza? ¿Qué directivas usa?
   ✓ ¿Invoca qué Service y Dataloader?
   ✓ ¿Transforma datos?
   ✓ Qué retorna
```

**DESPUÉS de explorar, EDITA el archivo .md:**

```bash
# Busca en el archivo:
## 4. Resolvers
[Por completar]

# Reemplázala con:
## 4. Resolvers (Queries y Mutations)
✅ COMPLETADO - [Tu nombre], [hora]

### QUERIES - Total encontradas: [Número]

#### MeQuery.php
**Ubicación:** app/Features/UserManagement/GraphQL/Queries/MeQuery.php
**Líneas:** [Número]

**Código resolve():**
\`\`\`php
[Pega el código EXACTO del método resolve()]
\`\`\`

**Análisis:**
- Autentica: [SÍ/NO] - ¿Cómo? [auth()->user(), directiva @auth, otro]
- Autoriza: [SÍ/NO] - Directiva: [cuál]
- Invoca Service(s): [cuáles y cómo]
- Invoca Dataloader(s): [cuáles]
- Transforma datos: [SÍ/NO] - ¿cómo?
- Retorna: [Qué]
- Complejidad: [simple/media/alta]

**Hallazgos:**
- [Observaciones importantes]

---

#### [OtraQuery].php
[Repetir estructura]

---

### MUTATIONS - Total encontradas: [Número]

#### UpdateMyProfileMutation.php
**Ubicación:** app/Features/UserManagement/GraphQL/Mutations/UpdateMyProfileMutation.php
**Líneas:** [Número]

**Input que recibe:**
\`\`\`
$input: UpdateProfileInput {
  [pega estructura exacta]
}
\`\`\`

**Código resolve():**
\`\`\`php
[Pega el código EXACTO]
\`\`\`

**Análisis:**
- Autentica: [SÍ/NO]
- Autoriza: [SÍ/NO]
- Valida input: [SÍ/NO] - ¿dónde?
- Invoca Service(s): [cuáles]
- Transacciones (DB::transaction): [SÍ/NO]
- Auditoría: [SÍ/NO]
- Excepciones: [cuáles lanza]
- Retorna: [Qué]
- Complejidad: [simple/media/alta]

**Hallazgos:**
- [Observaciones importantes]

---

#### [OtraMutation].php
[Repetir estructura]

---

### 🚨 Patrón Observado

**¿Dónde está la lógica?**
- [ ] Principalmente en Resolvers (problema para migración)
- [ ] Principalmente en Services (bueno para migración)
- [ ] Mezclada (complejo)

**Lógica NO documentada:** [SÍ/NO] - ¿dónde?

---
```

**Luego:**
```bash
git add documentacion/REPORTE_EXPLORACION_USERMANAGEMENT.md
git commit -m "docs: complete section 4 - resolvers analysis"

# Pasa a SECCIÓN 5
```

**EJEMPLO DE REPORTE:**
```
MeQuery Resolver:
  File: app/Features/UserManagement/GraphQL/Queries/MeQuery.php
  
  resolve() code:
  ```
public function resolve($root, $args, $context) {
$user = auth()->user();

      if (!$user) {
          throw new UnauthenticatedException();
      }
      
      if ($user->status === 'suspended') {
          throw new UserSuspendedException();
      }
      
      $user->roleContexts = $this->companyDataLoader->load(
          $user->roles->pluck('company_id')->unique()
      );
      
      return $user;
}
  ```

  Observations:
  - ✓ Autenticación directa (auth()->user())
  - ✓ Validación de estado
  - ✓ Dataloader invocado manualmente en Resolver (⚠️ MOVER al Service)
  - ✓ Retorna User con relationships
  - ✗ Sin auditoría de acceso
```

---

---

### SECCIÓN 5: Validaciones y Rules

**Busca y reporta:**

```
1. ¿Existen Form Requests o Rules?
   □ UpdateProfileRequest
   □ UpdatePreferencesRequest
   □ AssignRoleRequest
   □ Otros?

2. Para CADA Form Request, reporta:
   ✓ Archivo
   ✓ Código del método rules()
   ✓ Código del método authorize()
   ✓ Custom messages?

3. ¿Existen Rules personalizadas en app/Rules/?
   ✓ Nombres y qué validan

4. ¿Validaciones están en Resolver/Mutation o en Service?

5. ¿Hay validaciones de negocio complejas?
```

**DESPUÉS de explorar, EDITA el archivo .md:**

```bash
# Busca en el archivo:
## 5. Validaciones
[Por completar]

# Reemplázala con:
## 5. Validaciones y Rules
✅ COMPLETADO - [Tu nombre], [hora]

### Form Requests Encontrados: [Número]

#### [NombreRequest].php
**Ubicación:** app/Features/UserManagement/Http/Requests/[NombreRequest].php
**Líneas:** [Número]

**Método authorize():**
\`\`\`php
[Pega el código EXACTO]
\`\`\`

**Método rules():**
\`\`\`php
[Pega el código EXACTO]
\`\`\`

**Custom messages:**
- [Si existen, listar]

**¿Usa Custom Rules?** [SÍ/NO]
- [Si sí, listar cuáles y dónde están]

---

#### [OtroRequest].php
[Repetir estructura]

---

### Custom Rules (app/Rules/)

#### [NombreRule].php
**¿Existe?** [SÍ/NO]
**Ubicación:** app/Rules/[NombreRule].php
**Qué valida:** [Descripción]
**Mensaje:** [Cuál es]

---

### 🔍 Distribución de Validaciones

**Validaciones en Form Requests:** [SÍ/NO]
- [Listar cuáles]

**Validaciones en Resolver/Mutation:** [SÍ/NO]
- [Listar cuáles y dónde - ⚠️ Problemático]

**Validaciones en Service:** [SÍ/NO]
- [Listar cuáles]

**Validaciones de negocio complejas:**
- [Listar ejemplos]
- ¿Dónde están? [Resolver/Service/FormRequest]

### 📋 Validaciones de Negocio Críticas

**Ejemplo 1:**
- Regla: [ej: "no puedes asignar AGENT sin companyId"]
- Ubicación actual: [Resolver/Service/FormRequest]
- Excepción que lanza: [Cuál]
- Necesita mover para REST: [SÍ/NO]

---
```

**Luego:**
```bash
git add documentacion/REPORTE_EXPLORACION_USERMANAGEMENT.md
git commit -m "docs: complete section 5 - validations and rules"

# Pasa a SECCIÓN 6
```

---

---

### SECCIÓN 6: Excepciones Personalizadas

**Busca y reporta:**

```
1. Ubicación: app/Features/UserManagement/Exceptions/

2. Lista TODAS las excepciones:
   □ UserNotFoundException
   □ InvalidRoleAssignmentException
   □ ProfileUpdateFailedException
   □ Otros?

3. Para CADA excepción, reporta:
   ✓ Nombre exacto
   ✓ Código HTTP que debería retornar
   ✓ Mensaje de error
   ✓ ¿Tiene data adicional?
   ✓ ¿Dónde se lanza?

4. ¿Hay excepciones genéricas vs específicas?

5. ¿Existe ErrorCodeRegistry?
   Ubicación: app/Shared/Errors/ErrorCodeRegistry.php
   ✓ ¿Qué códigos tiene para UserManagement?
   ✓ ¿Están mapeados a HTTP status?
```

**DESPUÉS de explorar, EDITA el archivo .md:**

```bash
# Busca en el archivo:
## 6. Excepciones
[Por completar]

# Reemplázala con:
## 6. Excepciones Personalizadas
✅ COMPLETADO - [Tu nombre], [hora]

### Excepciones Encontradas: [Número]

#### [NombreException].php
**Ubicación:** app/Features/UserManagement/Exceptions/[NombreException].php
**Líneas:** [Número]

**Código de la excepción:**
\`\`\`php
[Pega la clase EXACTA]
\`\`\`

**Análisis:**
- HTTP Status esperado: [ej: 404, 409, 422]
- Mensaje por defecto: "[Mensaje]"
- ¿Acepta data adicional?** [SÍ/NO]
- Constructor parámetros: [Listar]
- Lanzada en: [cuáles Services/Resolvers]

**Hallazgos:**
- [Observaciones]

---

#### [OtraException].php
[Repetir estructura]

---

### 🔗 ErrorCodeRegistry

**Ubicación:** app/Shared/Errors/ErrorCodeRegistry.php
**¿Existe?** [SÍ/NO]

**Si existe, códigos de UserManagement:**
| Código | HTTP | Descripción |
|--------|------|-------------|
| USER_NOT_FOUND | 404 | [Descripción] |
| [Otro código] | [HTTP] | [Descripción] |

**Mapeo a HTTP Status:**
- [Listar cómo se mapea cada excepción a HTTP]

---

### 📋 Catálogo Completo de Excepciones

**Genéricas (reutilizables):**
- [Listar]

**Específicas de UserManagement:**
- [Listar]

**¿Hay excepciones sin mapear?** [SÍ/NO]
- [Cuáles y por qué]

---
```

**Luego:**
```bash
git add documentacion/REPORTE_EXPLORACION_USERMANAGEMENT.md
git commit -m "docs: complete section 6 - exceptions analysis"

# Pasa a SECCIÓN 7
```

**EJEMPLO DE REPORTE:**
```
UserNotFoundException:
  File: app/Features/UserManagement/Exceptions/UserNotFoundException.php
  HTTP Status: 404 (inferido, confirmar)
  Message: "User not found"
  Additional data: user_id?
  Thrown in: UserService::getUser(), UserService::updateProfile()
  
ErrorCodeRegistry entry:
  Code: USER_NOT_FOUND
  HTTP: 404
  Message: User not found
```

---

### SECCIÓN 7: Relaciones de Modelos (N+1 Risk)

**Busca y reporta:**

```
1. Model User (app/Models/User.php):
   ✓ Relaciones definidas:
     - profile() -> HasOne/BelongsTo?
     - roleContexts() or roles() -> BelongsToMany?
     - companies() -> through roles?
   ✓ Accessors/Mutators que puedan causar queries
   ✓ Scopes definidas

2. Model UserRole (o Role):
   ✓ Relaciones:
     - user() -> BelongsTo?
     - company() -> BelongsTo?
     - permissions() -> if exists?

3. Relaciones complejas:
   ✓ ¿User -> roleContexts -> company es correcta?
   ✓ ¿Hay Many-to-Many through?
   ✓ ¿Hay restricciones de soft delete?

4. Preguntas críticas:
   □ ¿Qué relaciones SIEMPRE necesitan eager load?
   □ ¿Hay relaciones opcionales (nullable)?
   □ ¿Hay relaciones con WHERE conditions?

5. Para cada endpoint futuro, reporta:
   ✓ Qué relaciones accede
   ✓ Profundidad de nesting (level 1, 2, 3?)
   ✓ Cantidad de records esperada
```

**EJEMPLO DE REPORTE:**
```
User Model Relations:

has_one: profile
  ├─ Type: HasOne
  ├─ Always needed: YES (en casi todos los endpoints)
  ├─ Nullable: NO
  └─ Eager load: user->load('profile')

many_to_many: roles (through user_roles)
  ├─ Type: BelongsToMany
  ├─ Pivot: user_roles (has is_active, revoked_at)
  ├─ Filtered: only where is_active = true
  └─ With company: role->company() -> BelongsTo

⚠️ N+1 RISK MATRIX:
  GET /api/users (20 records):
    - Without eager: 1 + 20 + (20*N roles) + (roles*M companies) = HIGH RISK
    - With eager: 1 + 1 + 1 + 1 = OK
    - Recommended: User::with('profile', 'roles.company')
```

---

### SECCIÓN 8: Auditoría y Logging

**Busca y reporta:**

```
1. ¿Existe sistema de auditoría?
   □ Ubicación
   □ Cómo se registra
   □ Qué campos guarda

2. Para UserManagement, reporta:
   ✓ ¿Qué eventos se auditan?
   ✓ ¿Dónde se disparan (en Resolver, Service, Middleware)?
   ✓ ¿Qué data se incluye en audit log?

3. ¿Hay registros de error?
   ✓ Ubicación de logs
   ✓ ¿Se loguean errores de UserManagement?
   ✓ ¿Qué información se guarda?

4. Integración con Sentry o similar:
   ✓ ¿Está configurado?
   ✓ ¿Se envían excepciones?
```

---

---

### SECCIÓN 9: Middleware y Autenticación

**Busca y reporta:**

```
1. ¿Qué middleware se usa en UserManagement?
   □ JWT middleware
   □ Auth middleware
   □ Custom middleware

2. Para CADA middleware, reporta:
   ✓ Nombre exacto
   ✓ Ubicación
   ✓ Qué valida
   ✓ Qué excepciones lanza

3. Autenticación GraphQL:
   ✓ ¿Cómo se valida el JWT?
   ✓ ¿Dónde se valida?
   ✓ ¿Qué directiva se usa?

4. Rate limiting:
   ✓ ¿Está implementado?
   ✓ ¿Cómo?
   ✓ ¿Se puede reutilizar en REST?
```

**DESPUÉS de explorar, EDITA el archivo .md:**

```bash
# Busca en el archivo:
## 9. Middleware y Autenticación
[Por completar]

# Reemplázala con:
## 9. Middleware y Autenticación
✅ COMPLETADO - [Tu nombre], [hora]

### Middleware Encontrado

#### [NombreMiddleware]
**Ubicación:** app/Http/Middleware/[NombreMiddleware].php
**Líneas:** [Número]

**Código:**
\`\`\`php
[Pega handle() method]
\`\`\`

**Análisis:**
- Qué valida: [Descripción]
- Excepciones que lanza: [Cuáles]
- Usado en GraphQL: [SÍ/NO]
- ¿Se puede reutilizar en REST?** [SÍ/NO/CON CAMBIOS]

---

#### [OtroMiddleware]
[Repetir estructura]

---

### Autenticación GraphQL

**¿Cómo se valida JWT?**
- Ubicación: [Middleware/Directive]
- Código de validación:
\`\`\`php
[Pega código]
\`\`\`

**Directivas GraphQL usadas:**
- @auth: [SÍ/NO]
- Otra: [SÍ/NO]

**Flujo de autenticación:**
1. [Paso 1]
2. [Paso 2]
3. [Paso 3]

---

### Rate Limiting

**¿Está implementado?** [SÍ/NO]

**Si SÍ:**
- Ubicación: [Dónde está]
- Tipo: [Middleware/Package]
- Límites configurados: [Listar]
- ¿Se usa en UserManagement?** [SÍ/NO]
- ¿Reutilizable en REST?** [SÍ/NO]

---

### 🚨 Hallazgos Críticos

**Middleware agnóstico:** [SÍ/NO]

**Cambios necesarios para REST:**
- [Listar si hay]

---
```

**Luego:**
```bash
git add documentacion/REPORTE_EXPLORACION_USERMANAGEMENT.md
git commit -m "docs: complete section 9 - middleware and authentication"

# Pasa a SECCIÓN 10
```

---

---

### SECCIÓN 10: Tests Existentes

**Busca y reporta:**

```
1. Ubicación: tests/Features/UserManagement/

2. Reporta:
   ✓ Archivos de test encontrados
   ✓ Número de tests total
   ✓ Tests por funcionalidad

3. Para CADA test principal, reporta:
   ✓ Nombre del test
   ✓ Qué setup hace
   ✓ Qué assertions hace
   ✓ ¿Valida happy path o edge cases?

4. Preguntas críticas:
   □ ¿Los tests son agnósticos al protocolo?
   □ ¿Qué % de cobertura tiene?
   □ ¿Hay tests de N+1?
   □ ¿Hay tests de rate limiting?

5. Reutilización para REST:
   ✓ ¿Cuántos tests se pueden reutilizar?
   ✓ ¿Cuántos necesitan cambios?
```

**DESPUÉS de explorar, EDITA el archivo .md:**

```bash
# Busca en el archivo:
## 10. Tests
[Por completar]

# Reemplázala con:
## 10. Tests Existentes
✅ COMPLETADO - [Tu nombre], [hora]

### Test Coverage Encontrado

**Ubicación:** tests/Features/UserManagement/
**Archivos de test:** [Número]
**Tests totales:** [Número]

---

### Tests por Funcionalidad

#### Get Me
**Archivos:** [Listar]
**Tests:** [Número]
**Ejemplos:**
- test_get_me_success
- test_get_me_unauthenticated
- test_get_me_suspended_user
- [Otros]

**¿Agnóstico al protocolo?** [SÍ/NO]
- Si NO: ¿usa GraphQL client? [SÍ/NO]
- Refactor necesario: [Cuántos cambios]

---

#### Get Users (Lista)
**Archivos:** [Listar]
**Tests:** [Número]
**Ejemplos:**
- test_list_users_with_filters
- test_list_users_pagination
- test_list_users_permissions
- [Otros]

**¿Valida N+1?** [SÍ/NO]
- Si SÍ: cómo lo valida

---

#### Update Profile
**Archivos:** [Listar]
**Tests:** [Número]
**Ejemplos:**
- test_update_profile_success
- test_update_profile_validation
- test_update_profile_permissions
- [Otros]

---

#### Assign Role
**Archivos:** [Listar]
**Tests:** [Número]
**Ejemplos:**
- test_assign_role_new
- test_assign_role_reactivate
- test_assign_role_validation
- test_assign_role_permissions
- [Otros]

---

### 🔍 Análisis de Tests

**¿Agnósticos al protocolo?** [SÍ/NO]
- Tests usan: [GraphQL client / Service direct / HTTP client]
- Si GraphQL: necesitará refactor para REST

**% Coverage:**
- UserService: [Número]%
- RoleService: [Número]%
- Exceptions: [Número]%
- Validations: [Número]%

**Tests de rendimiento:**
- ¿Hay tests de N+1?** [SÍ/NO]
- ¿Hay tests de rate limiting?** [SÍ/NO]
- ¿Hay tests de concurrency?** [SÍ/NO]

**Casos edge:**
- [Listar si hay tests de casos extremos]

---

### ♻️ Reutilización para REST

**Tests que NO necesitan cambios:**
- [Número] tests (ej: Service tests, validation tests)

**Tests que necesitan cambios MENORES:**
- [Número] tests (cambio de GraphQL client a HTTP)

**Tests que necesitan cambios MAYORES:**
- [Número] tests (lógica completamente diferente)

**Esfuerzo total de refactor:**
- [Estimación en horas]

---

### 📋 Ejemplos de Tests Key

**Test 1: Validación crítica**
\`\`\`php
[Pega un test importante que valida lógica crítica]
\`\`\`

**Test 2: Permiso crítico**
\`\`\`php
[Pega un test que valida permisos]
\`\`\`

---
```

**Luego:**
```bash
git add documentacion/REPORTE_EXPLORACION_USERMANAGEMENT.md
git commit -m "docs: complete section 10 - tests analysis"

# Pasa a SECCIONES FINALES (Hallazgos, Recomendaciones, Estado)
```

---

## 📋 Flujo de Trabajo: Archivo Vivo que se Va Editando

### Paso Inicial (ANTES de explorar)

1. **Crea el archivo base:**
   ```bash
   # Ubicación
   documentacion/REPORTE_EXPLORACION_USERMANAGEMENT.md
   ```

2. **Contenido inicial (copia esto):**
   ```markdown
   # Reporte de Exploración: UserManagement Feature
   
   > 🔄 Estado: EN CONSTRUCCIÓN
   > Generado: [fecha/hora]
   > Explorador: [tu nombre]
   
   ## Resumen Ejecutivo
   - Complejidad TOTAL: [Pendiente]
   - Riesgo de migración: [Pendiente]
   - Esfuerzo estimado: [Pendiente]
   - Bloqueadores identificados: [Pendiente]
   
   ## 1. Estructura de Archivos
   [Por completar]
   
   ## 2. Dataloaders
   [Por completar]
   
   ## 3. Services
   [Por completar]
   
   ## 4. Resolvers
   [Por completar]
   
   ## 5. Validaciones
   [Por completar]
   
   ## 6. Excepciones
   [Por completar]
   
   ## 7. Modelos y Relaciones
   [Por completar]
   
   ## 8. Auditoría y Logging
   [Por completar]
   
   ## 9. Middleware
   [Por completar]
   
   ## 10. Tests
   [Por completar]
   
   ## 🚨 Hallazgos Críticos
   [Por completar]
   
   ## 📋 Recomendaciones Inmediatas
   [Por completar]
   
   ## ⚠️ Riesgos Identificados
   [Por completar]
   
   ## ✅ Estado Final
   - ¿Está listo para migración?: [Pendiente]
   - Bloqueadores antes de migrar: [Pendiente]
   ```

### Paso por Paso: Explorar y EDITAR

**Para CADA sección (1 a 10):**

1. **Explora según instrucciones de la sección**
    - Lee archivos
    - Toma notas
    - Busca lo específico

2. **EDITA el archivo .md existente**
    - Reemplaza `[Por completar]` con tus hallazgos
    - Agrega detalles específicos
    - Si encuentras código importante, inclúyelo

3. **Commit parcial (opcional)**
   ```bash
   git add documentacion/REPORTE_EXPLORACION_USERMANAGEMENT.md
   git commit -m "docs: complete section X of exploration report"
   ```

4. **Continúa a la siguiente sección**

### Ejemplo: Cómo se ve MIENTRAS exploras

**Iteración 1 - Después de Sección 1 (Estructura de Archivos):**
```markdown
# Reporte de Exploración: UserManagement Feature

> 🔄 Estado: EN CONSTRUCCIÓN (Sección 1 de 10 completada)

...

## 1. Estructura de Archivos
✅ COMPLETADO

### Ubicación Principal
- Resolvers GraphQL: `app/Features/UserManagement/GraphQL/Queries/` (5 archivos, ~200 líneas)
- Mutations: `app/Features/UserManagement/GraphQL/Mutations/` (7 archivos, ~350 líneas)
- Services: `app/Features/UserManagement/Services/` (3 archivos, ~400 líneas)
  - UserService.php (150 líneas)
  - RoleService.php (120 líneas)
  - ProfileService.php (130 líneas)
...

## 2. Dataloaders
[Por completar]

## 3. Services
[Por completar]
...
```

**Iteración 2 - Después de Sección 2 (Dataloaders):**
```markdown
...
## 2. Dataloaders
✅ COMPLETADO

### CompanyDataLoader
- Ubicación: `app/Shared/GraphQL/DataLoaders/CompanyDataLoader.php`
- Líneas: 25
- Batches: companies
- Invocado en:
  - UserManagement MeQuery (roleContexts.company)
  - RoleManagement RoleQuery
- Código:
  ```php
  public static function load($ids) {
      return Company::whereIn('id', $ids)->get()->keyBy('id');
  }
  ```
- Observación: Agnóstico, puede reutilizarse en REST ✅

### RoleDataLoader
[Si existe, reportar de igual forma]

## 3. Services
[Por completar]
...
```

### Paso Final: Resumen Ejecutivo

**DESPUÉS de completar secciones 1-10, actualiza:**

```markdown
## Resumen Ejecutivo

### Complejidad TOTAL: MEDIA
- Estructura clara ✅
- Dataloaders agnósticos ✅
- Services bien separados ✅
- Algunos acoplamientos GraphQL ⚠️

### Riesgo de migración: AMARILLO
- Moldeadores bien desacoplados
- Algunas validaciones distribuidas
- Tests parcialmente agnósticos

### Esfuerzo estimado: 3-4 semanas
- Refactorings previos: 3 días
- Implementación REST: 2 semanas
- Testing y QA: 1 semana

### Bloqueadores identificados: 2
1. CompanyDataLoader invocado solo en Resolver (necesita mover)
2. Validaciones en Mutation en lugar de FormRequest
```

---

## 🎯 Instrucciones de Ejecución

**Paso 1: Lee Este Prompt Completo**
Entiende qué buscas y qué esperas encontrar.

**Paso 2: Explora el Codebase**
Navega por las carpetas mencionadas. Abre archivos.

**Paso 3: Reporta Hallazgos**
Usa la plantilla de reporte final.

**Paso 4: Sé Específico**
- No digas "el código está complejo"
- Di "UpdateProfileMutation tiene 45 líneas con 3 queries a BD"

**Paso 5: Cita el Código**
Cuando encuentres algo importante, pega el código exacto o la línea.

---

## 🎯 SECCIONES FINALES (DESPUÉS de secciones 1-10)

### SECCIÓN 11: Hallazgos Críticos

**Recopila lo más importante:**

```bash
# Busca en el archivo:
## 🚨 Hallazgos Críticos
[Por completar]

# Reemplázala con:
## 🚨 Hallazgos Críticos
✅ COMPILADO - [Tu nombre], [hora]

### Top 5 Hallazgos Más Importantes

**1. [Hallazgo crítico]**
- Sección donde encontrado: [X]
- Impacto: [ALTO/MEDIO/BAJO]
- Descripción: [Cuál es el problema]
- Evidencia: [Código/ubicación]

**2. [Hallazgo crítico]**
[Repetir estructura]

**3-5. [Repetir]**

---

### Bloqueadores Identificados

**¿Hay bloqueadores?** [SÍ/NO]

**Si SÍ, listar:**
1. Bloqueador: [Cuál es]
   - Ubicación: [Dónde]
   - Solución recomendada: [Cómo arreglarlo]
   - Esfuerzo: [Horas]

2. Bloqueador: [Otro]
   [Repetir]

---

### Acoplamientos a GraphQL

**¿Hay lógica acoplada a GraphQL?** [SÍ/NO]
- [Listar dónde y cómo]
- Necesita refactor: [SÍ/NO]

---
```

### SECCIÓN 12: Recomendaciones Inmediatas

```bash
# Busca en el archivo:
## 📋 Recomendaciones Inmediatas
[Por completar]

# Reemplázala con:
## 📋 Recomendaciones Inmediatas
✅ COMPILADO - [Tu nombre], [hora]

### Antes de Migrar a REST

**Refactorings previos necesarios:**
1. [Refactoring] - Esfuerzo: [X horas]
   - Por qué: [Razón]
   - Ubicación: [Dónde]

2. [Otro refactoring]
   [Repetir]

---

### Prioridad de Implementación REST

**Bloque 1 (Más fácil):**
- Endpoints: [Listar cuáles]
- Por qué: [Razón]
- Esfuerzo: [Horas]

**Bloque 2:**
- Endpoints: [Listar]
- Por qué: [Razón]
- Esfuerzo: [Horas]

**Bloque 3 (Más complejo):**
- Endpoints: [Listar]
- Por qué: [Razón]
- Esfuerzo: [Horas]

---

### Riesgos a Mitigar

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|--------|-----------|
| [Riesgo] | [ALTO/MEDIO/BAJO] | [ALTO/MEDIO/BAJO] | [Cómo mitigarlo] |
| [Otro] | [...] | [...] | [...] |

---
```

### SECCIÓN 13: Resumen Ejecutivo Final

```bash
# Actualiza la sección inicial:
## Resumen Ejecutivo

# Con:
## Resumen Ejecutivo
✅ FINAL - [Tu nombre], [hora]

### Complejidad TOTAL
**Estimación:** [Baja/Media/Alta]
**Justificación:**
- Estructura: [Bien organizada/OK/Caótica]
- Dataloaders: [Agnósticos/Acoplados]
- Services: [Limpios/Mezclados]
- Validaciones: [Centralizadas/Distribuidas]

### Riesgo de Migración
**Color:** [🟢 Verde / 🟡 Amarillo / 🔴 Rojo]
**Por qué:** [Razón principal]
**Mitigación:** [Cómo reducir riesgo]

### Esfuerzo Estimado (REALISTA)
- Refactorings previos: [X días]
- Implementación REST: [X días]
- Testing y QA: [X días]
- **TOTAL:** [X semanas]

### Bloqueadores Antes de Migrar
1. [Bloqueador]
2. [Bloqueador]
3. [Otro]

### ✅ Estado: ¿Listo para Migración?

**Respuesta:** [SÍ/NO]

**Si SÍ:**
- Proceder directamente a implementación
- Seguir bloques de prioridad definidos

**Si NO:**
- Tareas previas requeridas: [Listar]
- Esfuerzo de tareas previas: [Horas/días]
- Timeline: [Estimación]

---
```

---

## 📝 FLUJO FINAL DE TRABAJO

**Después de completar secciones 1-13:**

```bash
# 1. Final commit
git add documentacion/REPORTE_EXPLORACION_USERMANAGEMENT.md
git commit -m "docs: exploration report complete - all sections filled"

# 2. Verifica el archivo
cat documentacion/REPORTE_EXPLORACION_USERMANAGEMENT.md

# 3. Envía al revisor (yo / tu arquitecto)
# Con mensaje: "Reporte de exploración completado. Listo para análisis y formulación de REGLA 1."

# 4. El revisor analizará y:
#    - Validará hallazgos
#    - Identificará patrones
#    - Formulará REGLA 1 precisa
#    - Ajustará REGLA 2 si es necesario
```
