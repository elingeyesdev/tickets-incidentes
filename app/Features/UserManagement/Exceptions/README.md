# UserManagement Feature - Exceptions

Esta carpeta contendrá las excepciones específicas del feature **UserManagement**.

## 📋 Excepciones a Crear (cuando se necesiten)

### 1. EmailAlreadyExistsException
**Cuándo:** Al intentar registrar o actualizar un usuario con un email ya existente.
```php
throw EmailAlreadyExistsException::forEmail($email);
```

### 2. InvalidRoleAssignmentException
**Cuándo:** Al intentar asignar un rol inválido o con parámetros incorrectos.
```php
throw InvalidRoleAssignmentException::invalidRole($roleCode);
```

### 3. RoleRequiresCompanyException
**Cuándo:** Al intentar asignar rol AGENT o COMPANY_ADMIN sin especificar empresa.
```php
throw RoleRequiresCompanyException::forRole('AGENT');
```

### 4. RoleShouldNotHaveCompanyException
**Cuándo:** Al intentar asignar rol USER o PLATFORM_ADMIN con empresa especificada.
```php
throw RoleShouldNotHaveCompanyException::forRole('USER');
```

### 5. UserAlreadyHasRoleException
**Cuándo:** Al intentar asignar un rol que el usuario ya tiene activo.
```php
throw UserAlreadyHasRoleException::roleAndCompany($roleCode, $companyId);
```

### 6. CannotRevokeLastAdminException
**Cuándo:** Al intentar revocar el último admin de una empresa o plataforma.
```php
throw CannotRevokeLastAdminException::forCompany($companyId);
```

### 7. ProfileUpdateFailedException
**Cuándo:** Al fallar la actualización del perfil por razones de negocio.
```php
throw ProfileUpdateFailedException::withReason($reason);
```

## 📝 Plantilla Base

```php
<?php

namespace App\Features\UserManagement\Exceptions;

use App\Shared\Exceptions\HelpdeskException;

class [ExceptionName] extends HelpdeskException
{
    protected string $category = 'user_management';
    protected string $errorCode = '[ERROR_CODE]';

    public function __construct(string $message = '')
    {
        parent::__construct($message ?: 'Mensaje por defecto');
    }

    public static function [factoryMethod](): self
    {
        return new self('Mensaje específico');
    }
}
```

## 🎯 Cuándo Crear

Crea estas excepciones **SOLO cuando implementes la lógica** que las necesita.
No crear todas de antemano (principio YAGNI).

## 📖 Referencias

- Ver: `documentacion/USER MANAGEMENT FEATURE - DOCUMENTACIÓN.txt`
- Ver: `documentacion/ARQUITECTURA_ERRORES_Y_EXCEPCIONES.md`
