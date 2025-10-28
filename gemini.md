# 🤝 Marco de Colaboración y Reglas de Migración (GraphQL a REST)

Este documento define las reglas y el proceso de diagnóstico que seguiremos durante la migración de la API de GraphQL a REST para el proyecto Helpdesk. Estas reglas son innegociables y garantizan una migración profesional, segura y de alta calidad.

---

## 🏛️ Jerarquía de Diagnóstico de Errores

Ante cualquier fallo en los tests o comportamiento inesperado, seguiremos estrictamente la siguiente jerarquía de investigación, procediendo a la siguiente capa solo si la anterior ha sido descartada.

### 🥇 Capa 1: Comparación de Lógica (Controller vs. Resolver)

- **Objetivo:** Asegurar que la orquestación de la lógica de negocio sea idéntica.
- **Acción:** Realizar una comparación directa entre el código del nuevo `Controller` de REST y el `Resolver` de GraphQL correspondiente, que ya ha sido validado por tests. Se debe verificar que ambos invoquen los mismos servicios con los mismos parámetros en el mismo orden.

### 🥈 Capa 2: Verificación del Contrato de API (Formato de Respuesta)

- **Objetivo:** Garantizar que la estructura del JSON de respuesta de la API REST sea 100% idéntica a la que producía la API de GraphQL.
- **Acción:** Auditar el `ApiResource` de Laravel responsable de la respuesta. El resultado debe coincidir exactamente con la estructura definida en `documentacion/AUTHENTICATION FEATURE - DOCUMENTACIÓN.txt`.

### 🥉 Capa 3: Análisis del Manejo de Errores

- **Objetivo:** Confirmar que los errores de la API REST son consistentes y predecibles.
- **Acción:** Investigar la cadena de manejo de excepciones:
    1. La excepción específica del feature (ej. `app/Features/Authentication/Exceptions/...`).
    2. El registro central de códigos de error (`app/Shared/Errors/ErrorCodeRegistry.php`).
    3. El manejador principal de excepciones de la API (`app/Http/Middleware/ApiExceptionHandler.php`).
    El comportamiento debe replicar el sistema de errores original de GraphQL.

### 🏅 Capa 4: Auditoría de Middleware

- **Objetivo:** Descartar problemas de autenticación, autorización o procesamiento de requests.
- **Acción:** Si el error está relacionado con seguridad o acceso, se auditará el middleware JWT para REST. Se debe asegurar que su funcionalidad es un espejo exacto del middleware JWT que utilizaba GraphQL.

### 🎖️ Capa 5: Propuesta de Cambio en Lógica de Negocio

- **Condición:** Únicamente si un problema no puede ser resuelto tras agotar las cuatro capas anteriores.
- **Diagnóstico:** Se considerará una incompatibilidad fundamental entre el paradigma REST y la lógica de negocio existente.
- **Acción:**
    1. Analizaré la causa raíz en profundidad.
    2. Te presentaré una **propuesta de cambio** formal y detallada, explicando el porqué, el impacto y la solución sugerida.
    3. **No se modificará ninguna línea de la lógica de negocio (`Services`, `Models`, etc.) sin tu aprobación explícita a dicha propuesta.**

---

## 📜 Reglas Fundamentales de Colaboración

1.  **Inmutabilidad de la Lógica de Negocio:** Los servicios, modelos y cualquier archivo que contenga lógica de negocio se consideran "intocables" por defecto. Cualquier modificación requerirá seguir el protocolo de la **Capa 5**.

2.  **Commits Atómicos y Basados en Tests:** Realizaremos un `commit` únicamente cuando un conjunto de tests para una funcionalidad (ej. `register`, `login`, etc.) esté pasando al 100%. El mensaje del commit será claro, conciso y describirá la funcionalidad migrada.

3.  **Comunicación Proactiva:** Te mantendré informado en cada paso y solicitaré tu intervención cuando sea necesario, especialmente al finalizar una tarea o al necesitar una decisión (como en la Capa 5).
4. **responde en espanol**
