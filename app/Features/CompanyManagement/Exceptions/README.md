# CompanyManagement Feature - Exceptions

Esta carpeta contendrá las excepciones específicas del feature **CompanyManagement**.

## 📋 Excepciones a Crear (cuando se necesiten)

### 1. CompanyAlreadyExistsException
**Cuándo:** Al intentar crear una empresa con nombre o código duplicado.
```php
throw CompanyAlreadyExistsException::byName($companyName);
```

### 2. CompanyNotActiveException
**Cuándo:** Al intentar operar con una empresa suspendida o inactiva.
```php
throw CompanyNotActiveException::suspended($companyId);
```

### 3. MaxCompaniesReachedException
**Cuándo:** Al alcanzar el límite de empresas del sistema o usuario.
```php
throw MaxCompaniesReachedException::systemLimit($currentCount, $maxAllowed);
```

### 4. AlreadyFollowingException
**Cuándo:** Al intentar seguir una empresa que ya se está siguiendo.
```php
throw AlreadyFollowingException::company($companyId);
```

### 5. NotFollowingException
**Cuándo:** Al intentar dejar de seguir una empresa que no se está siguiendo.
```php
throw NotFollowingException::company($companyId);
```

### 6. MaxFollowsExceededException
**Cuándo:** Al alcanzar el límite de empresas que se pueden seguir (50).
```php
throw MaxFollowsExceededException::limit($currentFollows, $maxAllowed);
```

### 7. RequestAlreadyExistsException
**Cuándo:** Al intentar crear una solicitud de empresa duplicada.
```php
throw RequestAlreadyExistsException::forEmail($adminEmail);
```

### 8. RequestNotPendingException
**Cuándo:** Al intentar aprobar/rechazar una solicitud que no está pendiente.
```php
throw RequestNotPendingException::currentStatus($requestStatus);
```

## 📝 Plantilla Base

```php
<?php

namespace App\Features\CompanyManagement\Exceptions;

use App\Shared\Exceptions\HelpdeskException;

class [ExceptionName] extends HelpdeskException
{
    protected string $category = 'company_management';
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

- Ver: `documentacion/COMPANY MANAGEMENT FEATURE - DOCUMENTACIÓN.txt`
- Ver: `documentacion/ARQUITECTURA_ERRORES_Y_EXCEPCIONES.md`
