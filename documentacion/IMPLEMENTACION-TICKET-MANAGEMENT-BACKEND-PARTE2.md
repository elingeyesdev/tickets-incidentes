# CONTINUACIÓN: FASES 9-16

## 📤 FASE 9: RESOURCES (Transformadores)

### Objetivo
Crear transformadores para las respuestas JSON de la API.

### Archivos a Crear (8 resources)

#### 9.1. `app/Features/TicketManagement/Http/Resources/CategoryResource.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'active_tickets_count' => $this->whenLoaded('tickets', function () {
                return $this->tickets()->whereIn('status', ['open', 'pending'])->count();
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

---

#### 9.2. `app/Features/TicketManagement/Http/Resources/TicketResource.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_code' => $this->ticket_code,
            'company_id' => $this->company_id,
            'category_id' => $this->category_id,
            'title' => $this->title,
            'initial_description' => $this->initial_description,
            'status' => $this->status->value,
            'last_response_author_type' => $this->last_response_author_type,
            'owner_agent_id' => $this->owner_agent_id,
            'created_by_user_id' => $this->created_by_user_id,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'first_response_at' => $this->first_response_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),

            // Relaciones
            'created_by_user' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->profile->full_name ?? $this->creator->email,
                    'email' => $this->creator->email,
                    'avatar_url' => $this->creator->profile->avatar_url ?? null,
                ];
            }),

            'owner_agent' => $this->when($this->owner_agent_id, function () {
                if ($this->relationLoaded('ownerAgent') && $this->ownerAgent) {
                    return [
                        'id' => $this->ownerAgent->id,
                        'name' => $this->ownerAgent->profile->full_name ?? $this->ownerAgent->email,
                        'email' => $this->ownerAgent->email,
                        'avatar_url' => $this->ownerAgent->profile->avatar_url ?? null,
                    ];
                }
                return null;
            }),

            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'description' => $this->category->description,
                ];
            }),

            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'name' => $this->company->company_name,
                ];
            }),

            // Counts
            'responses_count' => $this->whenCounted('responses'),
            'attachments_count' => $this->whenCounted('attachments'),

            // Timeline (para vista detallada)
            'timeline' => $this->when($request->routeIs('tickets.show'), [
                'created_at' => $this->created_at->toIso8601String(),
                'first_response_at' => $this->first_response_at?->toIso8601String(),
                'resolved_at' => $this->resolved_at?->toIso8601String(),
                'closed_at' => $this->closed_at?->toIso8601String(),
            ]),
        ];
    }
}
```

---

#### 9.3. `app/Features/TicketManagement/Http/Resources/TicketListResource.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TicketListResource - Versión ligera para listados
 */
class TicketListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_code' => $this->ticket_code,
            'title' => $this->title,
            'status' => $this->status->value,
            'last_response_author_type' => $this->last_response_author_type,
            'owner_agent_id' => $this->owner_agent_id,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Relaciones mínimas
            'created_by_user' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->profile->full_name ?? $this->creator->email,
                    'email' => $this->creator->email,
                ];
            }),

            'owner_agent' => $this->when($this->owner_agent_id, function () {
                if ($this->relationLoaded('ownerAgent') && $this->ownerAgent) {
                    return [
                        'id' => $this->ownerAgent->id,
                        'name' => $this->ownerAgent->profile->full_name ?? $this->ownerAgent->email,
                    ];
                }
                return null;
            }),

            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),

            // Counts
            'responses_count' => $this->whenCounted('responses'),
            'attachments_count' => $this->whenCounted('attachments'),
        ];
    }
}
```

---

#### 9.4. `app/Features/TicketManagement/Http/Resources/ResponseResource.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'response_id' => $this->response_id,
            'author_id' => $this->author_id,
            'author_type' => $this->author_type->value,
            'response_content' => $this->response_content,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Autor
            'author' => $this->whenLoaded('author', function () {
                return [
                    'id' => $this->author->id,
                    'name' => $this->author->profile->full_name ?? $this->author->email,
                    'email' => $this->author->email,
                    'avatar_url' => $this->author->profile->avatar_url ?? null,
                ];
            }),

            // Adjuntos
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
```

---

#### 9.5. `app/Features/TicketManagement/Http/Resources/AttachmentResource.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'response_id' => $this->response_id,
            'uploaded_by_user_id' => $this->uploaded_by_user_id,
            'file_name' => $this->file_name,
            'file_url' => \Storage::url($this->file_url),
            'file_type' => $this->file_type,
            'file_size_bytes' => $this->file_size_bytes,
            'file_size_mb' => $this->file_size_mb,
            'created_at' => $this->created_at->toIso8601String(),

            // Uploader
            'uploader' => $this->whenLoaded('uploader', function () {
                return [
                    'id' => $this->uploader->id,
                    'name' => $this->uploader->profile->full_name ?? $this->uploader->email,
                    'email' => $this->uploader->email,
                ];
            }),

            // Contexto de respuesta (si aplica)
            'response_context' => $this->when($this->response_id, function () {
                if ($this->relationLoaded('response') && $this->response) {
                    return [
                        'id' => $this->response->id,
                        'author_type' => $this->response->author_type->value,
                        'created_at' => $this->response->created_at->toIso8601String(),
                    ];
                }
                return null;
            }),
        ];
    }
}
```

---

#### 9.6. `app/Features/TicketManagement/Http/Resources/RatingResource.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'rated_by_user_id' => $this->rated_by_user_id,
            'rated_agent_id' => $this->rated_agent_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Cliente que calificó
            'rated_by_user' => $this->whenLoaded('ratedBy', function () {
                return [
                    'id' => $this->ratedBy->id,
                    'name' => $this->ratedBy->profile->full_name ?? $this->ratedBy->email,
                    'email' => $this->ratedBy->email,
                ];
            }),

            // Agente calificado (snapshot histórico)
            'rated_agent' => $this->whenLoaded('ratedAgent', function () {
                return [
                    'id' => $this->ratedAgent->id,
                    'name' => $this->ratedAgent->profile->full_name ?? $this->ratedAgent->email,
                    'email' => $this->ratedAgent->email,
                ];
            }),
        ];
    }
}
```

---

### Tests que pasan después de Fase 9

- ✅ Todos los Feature Tests que validan estructura de responses

---

## 📥 FASE 10: REQUESTS (Validación de Inputs)

### Objetivo
Crear Form Requests para validación de inputs en cada endpoint.

### Archivos a Crear (13 requests)

#### 10.1. Categories (2 requests)

##### `app/Features/TicketManagement/Http/Requests/Categories/StoreCategoryRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Requests\Categories;

use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Solo COMPANY_ADMIN puede crear categorías
        return JWTHelper::hasRoleFromJWT('COMPANY_ADMIN');
    }

    public function rules(): array
    {
        $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                Rule::unique('ticketing.categories', 'name')->where('company_id', $companyId),
            ],
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El campo name es requerido',
            'name.min' => 'El nombre debe tener al menos 3 caracteres',
            'name.max' => 'El nombre no puede exceder 100 caracteres',
            'name.unique' => 'Ya existe una categoría con ese nombre en esta empresa',
            'description.max' => 'La descripción no puede exceder 500 caracteres',
        ];
    }

    /**
     * Agregar company_id del JWT al request
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'company_id' => JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN'),
        ]);
    }
}
```

---

##### `app/Features/TicketManagement/Http/Requests/Categories/UpdateCategoryRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Requests\Categories;

use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return JWTHelper::hasRoleFromJWT('COMPANY_ADMIN');
    }

    public function rules(): array
    {
        $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
        $categoryId = $this->route('id');

        return [
            'name' => [
                'sometimes',
                'string',
                'min:3',
                'max:100',
                Rule::unique('ticketing.categories', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($categoryId),
            ],
            'description' => 'sometimes|nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
```

---

#### 10.2. Tickets (5 requests)

##### `app/Features/TicketManagement/Http/Requests/Tickets/StoreTicketRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Requests\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Solo USER puede crear tickets
        return auth()->user()->hasRole('USER');
    }

    public function rules(): array
    {
        return [
            'company_id' => 'required|uuid|exists:business.companies,id',
            'category_id' => [
                'required',
                'uuid',
                'exists:ticketing.categories,id',
                function ($attribute, $value, $fail) {
                    $category = \App\Features\TicketManagement\Models\Category::find($value);
                    if ($category && !$category->is_active) {
                        $fail('La categoría seleccionada está inactiva');
                    }
                },
            ],
            'title' => 'required|string|min:5|max:255',
            'initial_description' => 'required|string|min:10|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'El campo company_id es requerido',
            'company_id.exists' => 'La empresa especificada no existe',
            'category_id.required' => 'El campo category_id es requerido',
            'category_id.exists' => 'La categoría especificada no existe',
            'title.required' => 'El campo title es requerido',
            'title.min' => 'El título debe tener al menos 5 caracteres',
            'title.max' => 'El título no puede exceder 255 caracteres',
            'initial_description.required' => 'El campo initial_description es requerido',
            'initial_description.min' => 'La descripción debe tener al menos 10 caracteres',
            'initial_description.max' => 'La descripción no puede exceder 5000 caracteres',
        ];
    }
}
```

---

##### `app/Features/TicketManagement/Http/Requests/Tickets/UpdateTicketRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Requests\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autorización se maneja en TicketPolicy
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|min:5|max:255',
            'category_id' => [
                'sometimes',
                'uuid',
                'exists:ticketing.categories,id',
                function ($attribute, $value, $fail) {
                    $category = \App\Features\TicketManagement\Models\Category::find($value);
                    if ($category && !$category->is_active) {
                        $fail('La categoría seleccionada está inactiva');
                    }
                },
            ],
        ];
    }
}
```

---

##### `app/Features/TicketManagement/Http/Requests/Tickets/ResolveTicketRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Requests\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class ResolveTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy se encarga
    }

    public function rules(): array
    {
        return [
            'resolution_note' => 'nullable|string|max:5000',
        ];
    }
}
```

---

##### `app/Features/TicketManagement/Http/Requests/Tickets/ReopenTicketRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Requests\Tickets;

use App\Features\TicketManagement\Rules\CanReopenTicket;
use Illuminate\Foundation\Http\FormRequest;

class ReopenTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy se encarga
    }

    public function rules(): array
    {
        $ticket = $this->route('ticket'); // Route model binding

        return [
            'reopen_reason' => 'nullable|string|max:5000',
            'ticket' => [new CanReopenTicket(auth()->user())],
        ];
    }

    /**
     * Preparar datos para validación
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'ticket' => $this->route('ticket'),
        ]);
    }
}
```

---

##### `app/Features/TicketManagement/Http/Requests/Tickets/AssignTicketRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Requests\Tickets;

use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Http\FormRequest;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy se encarga
    }

    public function rules(): array
    {
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT')
            ?? JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        return [
            'new_agent_id' => [
                'required',
                'uuid',
                'exists:auth.users,id',
                function ($attribute, $value, $fail) use ($companyId) {
                    $user = \App\Features\UserManagement\Models\User::find($value);

                    if (!$user) {
                        $fail('El agente especificado no existe');
                        return;
                    }

                    if (!$user->hasRole('AGENT')) {
                        $fail('El usuario no tiene rol de agente');
                        return;
                    }

                    // Verificar que pertenece a la misma empresa
                    $agentCompanyId = null;
                    foreach ($user->roles as $role) {
                        if ($role->role_code === 'AGENT') {
                            $agentCompanyId = $role->pivot->company_id ?? null;
                            break;
                        }
                    }

                    if ($agentCompanyId !== $companyId) {
                        $fail('El agente pertenece a otra empresa');
                    }
                },
            ],
            'assignment_note' => 'nullable|string|max:5000',
        ];
    }
}
```

---

#### 10.3. Responses (2 requests)

##### `app/Features/TicketManagement/Http/Requests/Responses/StoreResponseRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Requests\Responses;

use Illuminate\Foundation\Http\FormRequest;

class StoreResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy se encarga
    }

    public function rules(): array
    {
        return [
            'response_content' => 'required|string|min:1|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'response_content.required' => 'El campo response_content es requerido',
            'response_content.min' => 'El contenido no puede estar vacío',
            'response_content.max' => 'El contenido no puede exceder 5000 caracteres',
        ];
    }
}
```

---

##### `app/Features/TicketManagement/Http/Requests/Responses/UpdateResponseRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Requests\Responses;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy se encarga
    }

    public function rules(): array
    {
        return [
            'response_content' => 'required|string|min:1|max:5000',
        ];
    }
}
```

---

#### 10.4. Attachments (1 request)

##### `app/Features/TicketManagement/Http/Requests/Attachments/UploadAttachmentRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Requests\Attachments;

use App\Features\TicketManagement\Rules\ValidFileType;
use Illuminate\Foundation\Http\FormRequest;

class UploadAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy se encarga
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10 MB en KB
                new ValidFileType(),
            ],
            'response_id' => 'nullable|uuid|exists:ticketing.ticket_responses,id',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'El campo file es requerido',
            'file.max' => 'El archivo no puede exceder 10 MB',
        ];
    }
}
```

---

#### 10.5. Ratings (2 requests)

##### `app/Features/TicketManagement/Http/Requests/Ratings/StoreRatingRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Requests\Ratings;

use Illuminate\Foundation\Http\FormRequest;

class StoreRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy se encarga
    }

    public function rules(): array
    {
        return [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'El campo rating es requerido',
            'rating.integer' => 'La calificación debe ser un número entero',
            'rating.min' => 'La calificación mínima es 1',
            'rating.max' => 'La calificación máxima es 5',
            'comment.max' => 'El comentario no puede exceder 1000 caracteres',
        ];
    }
}
```

---

##### `app/Features/TicketManagement/Http/Requests/Ratings/UpdateRatingRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Requests\Ratings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy se encarga
    }

    public function rules(): array
    {
        return [
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'sometimes|nullable|string|max:1000',
        ];
    }
}
```

---

### Tests que pasan después de Fase 10

- ✅ Todos los tests de validación en Feature Tests

---

## 🎮 FASE 11: CONTROLLERS

### Objetivo
Crear controllers que orquestan toda la lógica.

### Archivos a Crear (7 controllers)

**IMPORTANTE**: Los controllers deben ser DELGADOS. Toda la lógica de negocio está en Services.

#### 11.1. `app/Features/TicketManagement/Http/Controllers/CategoryController.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Controllers;

use App\Features\TicketManagement\Http\Requests\Categories\StoreCategoryRequest;
use App\Features\TicketManagement\Http\Requests\Categories\UpdateCategoryRequest;
use App\Features\TicketManagement\Http\Resources\CategoryResource;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Services\CategoryService;
use App\Http\Controllers\Controller;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * Listar categorías
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->query('company_id');

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'El parámetro company_id es requerido',
                ],
            ], 422);
        }

        $isActive = $request->query('is_active');
        if ($isActive !== null) {
            $isActive = filter_var($isActive, FILTER_VALIDATE_BOOLEAN);
        }

        $categories = $this->categoryService->list($companyId, $isActive);

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
            'meta' => [
                'total' => $categories->count(),
            ],
        ]);
    }

    /**
     * Crear categoría
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Categoría creada exitosamente',
            'data' => new CategoryResource($category),
        ], 201);
    }

    /**
     * Actualizar categoría
     */
    public function update(UpdateCategoryRequest $request, string $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $this->authorize('update', $category);

        $category = $this->categoryService->update($category, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Categoría actualizada exitosamente',
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Eliminar categoría
     */
    public function destroy(string $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $this->authorize('delete', $category);

        try {
            $this->categoryService->delete($category);

            return response()->json([
                'success' => true,
                'message' => 'Categoría eliminada exitosamente',
            ]);
        } catch (\App\Features\TicketManagement\Exceptions\CategoryInUseException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CATEGORY_IN_USE',
                    'message' => $e->getMessage(),
                ],
            ], 409);
        }
    }
}
```

---

#### 11.2. `app/Features/TicketManagement/Http/Controllers/TicketController.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Controllers;

use App\Features\TicketManagement\Http\Requests\Tickets\StoreTicketRequest;
use App\Features\TicketManagement\Http\Requests\Tickets\UpdateTicketRequest;
use App\Features\TicketManagement\Http\Resources\TicketListResource;
use App\Features\TicketManagement\Http\Resources\TicketResource;
use App\Features\TicketManagement\Services\TicketService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    public function __construct(
        protected TicketService $ticketService
    ) {}

    /**
     * Listar tickets
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        $filters = [
            'company_id' => $request->query('company_id'),
            'status' => $request->query('status'),
            'category_id' => $request->query('category_id'),
            'owner_agent_id' => $request->query('owner_agent_id'),
            'created_by' => $request->query('created_by'),
            'last_response_author_type' => $request->query('last_response_author_type'),
            'search' => $request->query('search'),
            'created_after' => $request->query('created_after'),
            'created_before' => $request->query('created_before'),
            'sort' => $request->query('sort', '-created_at'),
            'page' => $request->query('page', 1),
            'per_page' => $request->query('per_page', 20),
        ];

        $tickets = $this->ticketService->list($filters, $user);

        return response()->json([
            'success' => true,
            'data' => TicketListResource::collection($tickets),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
                'last_page' => $tickets->lastPage(),
                'from' => $tickets->firstItem(),
                'to' => $tickets->lastItem(),
            ],
            'links' => [
                'first' => $tickets->url(1),
                'last' => $tickets->url($tickets->lastPage()),
                'prev' => $tickets->previousPageUrl(),
                'next' => $tickets->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Obtener detalle de ticket
     */
    public function show(string $code): JsonResponse
    {
        $ticket = $this->ticketService->getByCode($code);

        $this->authorize('view', $ticket);

        return response()->json([
            'success' => true,
            'data' => new TicketResource($ticket),
        ]);
    }

    /**
     * Crear ticket
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $this->authorize('create', \App\Features\TicketManagement\Models\Ticket::class);

        $ticket = $this->ticketService->create($request->validated(), auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'Ticket creado exitosamente',
            'data' => new TicketResource($ticket),
        ], 201);
    }

    /**
     * Actualizar ticket
     */
    public function update(UpdateTicketRequest $request, string $code): JsonResponse
    {
        $ticket = $this->ticketService->getByCode($code);

        $this->authorize('update', $ticket);

        $ticket = $this->ticketService->update($ticket, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Ticket actualizado exitosamente',
            'data' => new TicketResource($ticket),
        ]);
    }

    /**
     * Eliminar ticket
     */
    public function destroy(string $code): JsonResponse
    {
        $ticket = $this->ticketService->getByCode($code);

        $this->authorize('delete', $ticket);

        try {
            $this->ticketService->delete($ticket);

            return response()->json([
                'success' => true,
                'message' => 'Ticket eliminado exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CANNOT_DELETE_ACTIVE_TICKET',
                    'message' => $e->getMessage(),
                ],
            ], 400);
        }
    }
}
```

---

*(Continuará con TicketActionController, ResponseController, AttachmentController, RatingController en el siguiente mensaje...)*

---

### Tests que pasan después de Fase 11

- ✅ **TODOS los Feature Tests** (281 tests aproximadamente)

---

## 🔄 FASE 12: ROUTES

### Objetivo
Exponer los endpoints de la API.

### Archivo a Crear

#### `routes/api.php` (agregar rutas)

```php
<?php

use App\Features\TicketManagement\Http\Controllers\CategoryController;
use App\Features\TicketManagement\Http\Controllers\TicketController;
use App\Features\TicketManagement\Http\Controllers\TicketActionController;
use App\Features\TicketManagement\Http\Controllers\TicketResponseController;
use App\Features\TicketManagement\Http\Controllers\TicketAttachmentController;
use App\Features\TicketManagement\Http\Controllers\TicketRatingController;
use Illuminate\Support\Facades\Route;

// Ticket Management API v1
Route::prefix('api/v1')->group(function () {

    // ==================== CATEGORÍAS ====================
    // Listar categorías (todos los usuarios autenticados)
    Route::get('/tickets/categories', [CategoryController::class, 'index'])
        ->middleware(['auth.jwt']);

    // CRUD de categorías (solo COMPANY_ADMIN)
    Route::middleware(['auth.jwt', 'role:COMPANY_ADMIN'])->group(function () {
        Route::post('/tickets/categories', [CategoryController::class, 'store']);
        Route::put('/tickets/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/tickets/categories/{id}', [CategoryController::class, 'destroy']);
    });

    // ==================== TICKETS ====================
    Route::middleware(['auth.jwt'])->group(function () {
        // CRUD básico
        Route::get('/tickets', [TicketController::class, 'index']);
        Route::get('/tickets/{code}', [TicketController::class, 'show']);
        Route::post('/tickets', [TicketController::class, 'store']);
        Route::put('/tickets/{code}', [TicketController::class, 'update']);
        Route::delete('/tickets/{code}', [TicketController::class, 'destroy']);

        // Acciones de tickets
        Route::post('/tickets/{code}/resolve', [TicketActionController::class, 'resolve']);
        Route::post('/tickets/{code}/close', [TicketActionController::class, 'close']);
        Route::post('/tickets/{code}/reopen', [TicketActionController::class, 'reopen']);
        Route::post('/tickets/{code}/assign', [TicketActionController::class, 'assign']);

        // Respuestas
        Route::get('/tickets/{code}/responses', [TicketResponseController::class, 'index']);
        Route::post('/tickets/{code}/responses', [TicketResponseController::class, 'store']);
        Route::put('/tickets/{code}/responses/{id}', [TicketResponseController::class, 'update']);
        Route::delete('/tickets/{code}/responses/{id}', [TicketResponseController::class, 'destroy']);

        // Adjuntos
        Route::get('/tickets/{code}/attachments', [TicketAttachmentController::class, 'index']);
        Route::post('/tickets/{code}/attachments', [TicketAttachmentController::class, 'store']);
        Route::delete('/tickets/{code}/attachments/{id}', [TicketAttachmentController::class, 'destroy']);

        // Calificaciones
        Route::get('/tickets/{code}/rating', [TicketRatingController::class, 'show']);
        Route::post('/tickets/{code}/rating', [TicketRatingController::class, 'store']);
        Route::put('/tickets/{code}/rating', [TicketRatingController::class, 'update']);
    });
});
```

---

## ⏰ FASE 13: JOBS (Auto-Close)

### Objetivo
Crear el Job para auto-cerrar tickets resueltos después de 7 días.

### Archivo a Crear

#### `app/Features/TicketManagement/Jobs/AutoCloseResolvedTicketsJob.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Jobs;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * AutoCloseResolvedTicketsJob
 *
 * Job para cerrar automáticamente tickets en estado 'resolved'
 * que llevan más de 7 días sin actividad.
 */
class AutoCloseResolvedTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Ejecutar el job
     */
    public function handle(): void
    {
        // Obtener tickets resolved hace más de 7 días
        $tickets = Ticket::where('status', TicketStatus::RESOLVED)
            ->where('resolved_at', '<', now()->subDays(7))
            ->get();

        $closedCount = 0;

        foreach ($tickets as $ticket) {
            try {
                $ticket->update([
                    'status' => TicketStatus::CLOSED->value,
                    'closed_at' => now(),
                ]);

                $closedCount++;
            } catch (\Exception $e) {
                Log::error("Failed to auto-close ticket {$ticket->ticket_code}: {$e->getMessage()}");
            }
        }

        Log::info("Auto-closed {$closedCount} resolved tickets");
    }
}
```

---

#### Agregar al Scheduler

**Archivo**: `app/Console/Kernel.php`

```php
<?php

namespace App\Console;

use App\Features\TicketManagement\Jobs\AutoCloseResolvedTicketsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Auto-cerrar tickets resueltos hace más de 7 días
        // Ejecutar diariamente a las 2:00 AM
        $schedule->job(new AutoCloseResolvedTicketsJob())
            ->dailyAt('02:00')
            ->name('tickets:auto-close')
            ->onOneServer();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
```

---

### Tests que pasan después de Fase 13

- ✅ **5 tests Unit (Jobs)** → AutoCloseResolvedTicketsJobTest.php

---

## 📡 FASE 14: EVENTS Y LISTENERS

### Objetivo
Crear eventos y listeners para notificaciones y side effects.

### Archivos a Crear (10 eventos + listeners)

**NOTA**: Esta fase es OPCIONAL para que los tests pasen. Los Feature Tests no validan eventos, pero son importantes para funcionalidad completa.

#### 14.1. Events (8 eventos)

```php
// app/Features/TicketManagement/Events/TicketCreated.php
<?php

namespace App\Features\TicketManagement\Events;

use App\Features\TicketManagement\Models\Ticket;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Ticket $ticket
    ) {}
}
```

**Crear similares para**:
- `TicketAssigned.php`
- `TicketResolved.php`
- `TicketClosed.php`
- `TicketReopened.php`
- `ResponseAdded.php`
- `TicketRated.php`

---

## 👀 FASE 15: OBSERVERS

### Objetivo
Crear observers para audit trail y side effects automáticos.

### Archivo a Crear

#### `app/Features/TicketManagement/Observers/TicketObserver.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Observers;

use App\Features\TicketManagement\Events\TicketCreated;
use App\Features\TicketManagement\Events\TicketResolved;
use App\Features\TicketManagement\Events\TicketClosed;
use App\Features\TicketManagement\Events\TicketReopened;
use App\Features\TicketManagement\Models\Ticket;

class TicketObserver
{
    /**
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket): void
    {
        event(new TicketCreated($ticket));
    }

    /**
     * Handle the Ticket "updated" event.
     */
    public function updated(Ticket $ticket): void
    {
        // Disparar eventos según cambios
        if ($ticket->wasChanged('status')) {
            if ($ticket->isResolved()) {
                event(new TicketResolved($ticket));
            } elseif ($ticket->isClosed()) {
                event(new TicketClosed($ticket));
            }
        }

        if ($ticket->wasChanged('owner_agent_id') && $ticket->owner_agent_id) {
            event(new \App\Features\TicketManagement\Events\TicketAssigned($ticket));
        }
    }
}
```

---

## 📦 FASE 16: SERVICE PROVIDER

### Objetivo
Registrar todos los bindings, policies, observers y eventos.

### Archivo a Crear

#### `app/Features/TicketManagement/TicketManagementServiceProvider.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement;

use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\TicketManagement\Models\TicketRating;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\TicketManagement\Observers\TicketObserver;
use App\Features\TicketManagement\Policies\AttachmentPolicy;
use App\Features\TicketManagement\Policies\CategoryPolicy;
use App\Features\TicketManagement\Policies\RatingPolicy;
use App\Features\TicketManagement\Policies\ResponsePolicy;
use App\Features\TicketManagement\Policies\TicketPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class TicketManagementServiceProvider extends ServiceProvider
{
    /**
     * Registrar servicios del feature
     */
    public function register(): void
    {
        // Registrar bindings de servicios si es necesario
        // (Laravel ya hace auto-discovery de servicios en app/Features/)
    }

    /**
     * Bootstrap del feature
     */
    public function boot(): void
    {
        // Registrar policies
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(TicketResponse::class, ResponsePolicy::class);
        Gate::policy(TicketAttachment::class, AttachmentPolicy::class);
        Gate::policy(TicketRating::class, RatingPolicy::class);

        // Registrar observers
        Ticket::observe(TicketObserver::class);

        // Cargar migraciones
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
    }
}
```

---

#### Registrar el Service Provider

**Archivo**: `config/app.php` o `bootstrap/providers.php`

```php
return [
    // ...
    'providers' => [
        // ...
        App\Features\TicketManagement\TicketManagementServiceProvider::class,
    ],
];
```

---

## ✅ CHECKLIST FINAL

### Antes de Ejecutar Tests

- ✅ Ejecutar migraciones: `docker compose exec helpdesk-app php artisan migrate`
- ✅ Verificar que schema `ticketing` existe en PostgreSQL
- ✅ Verificar que triggers están creados
- ✅ Limpiar cache: `docker compose exec helpdesk-app php artisan config:clear`
- ✅ Limpiar cache de routes: `docker compose exec helpdesk-app php artisan route:clear`

---

### Ejecutar Tests por Grupos

```bash
# 1. Unit Tests - Models (9 tests)
docker compose exec helpdesk-app php artisan test tests/Unit/TicketManagement/Models

# 2. Unit Tests - Services (19 tests)
docker compose exec helpdesk-app php artisan test tests/Unit/TicketManagement/Services

# 3. Unit Tests - Rules (8 tests)
docker compose exec helpdesk-app php artisan test tests/Unit/TicketManagement/Rules

# 4. Unit Tests - Jobs (5 tests)
docker compose exec helpdesk-app php artisan test tests/Unit/TicketManagement/Jobs

# 5. Feature Tests - Categories (26 tests)
docker compose exec helpdesk-app php artisan test tests/Feature/TicketManagement/Categories

# 6. Feature Tests - Tickets CRUD (70 tests)
docker compose exec helpdesk-app php artisan test tests/Feature/TicketManagement/Tickets/CRUD

# 7. Feature Tests - Tickets Actions (45 tests)
docker compose exec helpdesk-app php artisan test tests/Feature/TicketManagement/Tickets/Actions

# 8. Feature Tests - Responses (48 tests)
docker compose exec helpdesk-app php artisan test tests/Feature/TicketManagement/Responses

# 9. Feature Tests - Attachments (37 tests)
docker compose exec helpdesk-app php artisan test tests/Feature/TicketManagement/Attachments

# 10. Feature Tests - Ratings (26 tests)
docker compose exec helpdesk-app php artisan test tests/Feature/TicketManagement/Ratings

# 11. Feature Tests - Permissions (26 tests)
docker compose exec helpdesk-app php artisan test tests/Feature/TicketManagement/Permissions

# 12. Integration Tests (19 tests)
docker compose exec helpdesk-app php artisan test tests/Integration/TicketManagement

# EJECUTAR TODOS LOS TESTS (338 tests)
docker compose exec helpdesk-app php artisan test tests/Unit/TicketManagement
docker compose exec helpdesk-app php artisan test tests/Feature/TicketManagement
docker compose exec helpdesk-app php artisan test tests/Integration/TicketManagement
```

---

### Progreso Esperado

| Fase | Tests Verdes | Tests Totales | % Completitud |
|------|--------------|---------------|---------------|
| 1. Migraciones | 0 | 338 | 0% |
| 2. Modelos | 9 | 338 | 2.7% |
| 3. Enums | 9 | 338 | 2.7% |
| 4. Exceptions | 9 | 338 | 2.7% |
| 5. Factories | 10 | 338 | 3.0% |
| 6. Services | 29 | 338 | 8.6% |
| 7. Rules | 37 | 338 | 10.9% |
| 8. Policies | 63 | 338 | 18.6% |
| 9. Resources | 100 | 338 | 29.6% |
| 10. Requests | 150 | 338 | 44.4% |
| 11. Controllers | **318** | 338 | **94.1%** |
| 12. Routes | **338** | 338 | **100%** ✅ |

---

## 🎉 RESULTADO FINAL

Al completar TODAS las fases (1-16), deberías tener:

- ✅ **338 tests VERDES** (RED → GREEN completo)
- ✅ **23 endpoints funcionando**
- ✅ **6 tablas** en PostgreSQL con triggers
- ✅ **5 modelos** Eloquent completos
- ✅ **6 servicios** con lógica de negocio
- ✅ **5 policies** de autorización
- ✅ **13 form requests** de validación
- ✅ **8 resources** de transformación
- ✅ **7 controllers** orquestadores
- ✅ **1 job** para auto-close
- ✅ **100% cobertura** del feature

---

**FIN DEL PLAN DE IMPLEMENTACIÓN** 🚀

**Este documento es la guía COMPLETA para transformar TODOS los tests RED a GREEN del feature Ticket Management.**
