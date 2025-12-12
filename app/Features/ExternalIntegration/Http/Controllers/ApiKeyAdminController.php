<?php

namespace App\Features\ExternalIntegration\Http\Controllers;

use App\Features\ExternalIntegration\Models\ServiceApiKey;
use App\Features\Company\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for managing Service API Keys (Platform Admin)
 * 
 * Provides CRUD operations for API Keys used by external integrations.
 */
class ApiKeyAdminController extends Controller
{
    /**
     * Display the API Keys management view
     */
    public function index()
    {
        return view('app.platform-admin.api-keys.index');
    }

    /**
     * List all API Keys with filters and pagination
     */
    public function list(Request $request): JsonResponse
    {
        $query = ServiceApiKey::with(['company:id,name,company_code,logo_url', 'creator:id,email', 'creator.profile:user_id,first_name,last_name']);

        // Filter by company
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by status (active/revoked)
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'revoked') {
                $query->where('is_active', false);
            }
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Search by name or key prefix
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('key', 'like', "%{$search}%")
                  ->orWhereHas('company', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Order
        $orderBy = $request->input('order_by', 'created_at');
        $orderDirection = $request->input('order_direction', 'desc');
        $allowedOrderFields = ['created_at', 'name', 'last_used_at', 'usage_count'];
        
        if (in_array($orderBy, $allowedOrderFields)) {
            $query->orderBy($orderBy, $orderDirection);
        }

        // Pagination
        $perPage = min($request->input('per_page', 15), 100);
        $apiKeys = $query->paginate($perPage);

        // Transform data to include masked key
        $apiKeys->getCollection()->transform(function ($apiKey) {
            return [
                'id' => $apiKey->id,
                'key' => $apiKey->masked_key,
                'key_full' => $apiKey->key, // Full key for copy (admin only)
                'name' => $apiKey->name,
                'description' => $apiKey->description,
                'type' => $apiKey->type,
                'is_active' => $apiKey->is_active,
                'usage_count' => $apiKey->usage_count,
                'last_used_at' => $apiKey->last_used_at?->toISOString(),
                'expires_at' => $apiKey->expires_at?->toISOString(),
                'created_at' => $apiKey->created_at->toISOString(),
                'company' => $apiKey->company ? [
                    'id' => $apiKey->company->id,
                    'name' => $apiKey->company->name,
                    'companyCode' => $apiKey->company->company_code,
                    'logoUrl' => $apiKey->company->logo_url,
                ] : null,
                'creator' => $apiKey->creator ? [
                    'id' => $apiKey->creator->id,
                    'name' => $apiKey->creator->profile 
                        ? trim($apiKey->creator->profile->first_name . ' ' . $apiKey->creator->profile->last_name)
                        : $apiKey->creator->email,
                ] : null,
            ];
        });

        return response()->json($apiKeys);
    }

    /**
     * Get statistics for API Keys
     */
    public function statistics(): JsonResponse
    {
        $total = ServiceApiKey::count();
        $active = ServiceApiKey::where('is_active', true)->count();
        $revoked = ServiceApiKey::where('is_active', false)->count();
        $usedToday = ServiceApiKey::whereDate('last_used_at', today())->count();

        return response()->json([
            'total' => $total,
            'active' => $active,
            'revoked' => $revoked,
            'used_today' => $usedToday,
        ]);
    }

    /**
     * Generate a new API Key for a company
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|uuid|exists:companies,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:production,development,testing',
            'expires_at' => 'nullable|date|after:today',
        ], [
            'company_id.required' => 'Debes seleccionar una empresa.',
            'company_id.exists' => 'La empresa seleccionada no existe.',
            'name.required' => 'El nombre de la API Key es obligatorio.',
            'name.max' => 'El nombre no puede exceder 100 caracteres.',
            'type.required' => 'Debes seleccionar un tipo.',
            'type.in' => 'El tipo debe ser production, development o testing.',
            'expires_at.after' => 'La fecha de expiración debe ser posterior a hoy.',
        ]);

        // Generate the key with correct prefix based on type
        $key = ServiceApiKey::generateKey($validated['type']);

        $apiKey = ServiceApiKey::create([
            'company_id' => $validated['company_id'],
            'key' => $key,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        // Load relationships
        $apiKey->load(['company:id,name,company_code', 'creator:id,email', 'creator.profile:user_id,first_name,last_name']);

        return response()->json([
            'success' => true,
            'message' => 'API Key generada exitosamente.',
            'data' => [
                'id' => $apiKey->id,
                'key' => $key, // Return full key ONLY on creation
                'name' => $apiKey->name,
                'type' => $apiKey->type,
                'company' => $apiKey->company ? [
                    'id' => $apiKey->company->id,
                    'name' => $apiKey->company->name,
                ] : null,
            ],
        ], 201);
    }

    /**
     * Revoke an API Key
     */
    public function revoke(string $id): JsonResponse
    {
        $apiKey = ServiceApiKey::findOrFail($id);
        
        if (!$apiKey->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Esta API Key ya está revocada.',
            ], 422);
        }

        $apiKey->revoke();

        return response()->json([
            'success' => true,
            'message' => 'API Key revocada exitosamente.',
        ]);
    }

    /**
     * Activate a revoked API Key
     */
    public function activate(string $id): JsonResponse
    {
        $apiKey = ServiceApiKey::findOrFail($id);
        
        if ($apiKey->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Esta API Key ya está activa.',
            ], 422);
        }

        $apiKey->activate();

        return response()->json([
            'success' => true,
            'message' => 'API Key activada exitosamente.',
        ]);
    }

    /**
     * Delete an API Key permanently
     */
    public function destroy(string $id): JsonResponse
    {
        $apiKey = ServiceApiKey::findOrFail($id);
        
        $apiKey->delete();

        return response()->json([
            'success' => true,
            'message' => 'API Key eliminada permanentemente.',
        ]);
    }

    /**
     * Get API Keys for a specific company (used in company modal)
     */
    public function byCompany(string $companyId): JsonResponse
    {
        $apiKeys = ServiceApiKey::where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($apiKey) {
                return [
                    'id' => $apiKey->id,
                    'key' => $apiKey->masked_key,
                    'key_full' => $apiKey->key,
                    'name' => $apiKey->name,
                    'type' => $apiKey->type,
                    'is_active' => $apiKey->is_active,
                    'usage_count' => $apiKey->usage_count,
                    'last_used_at' => $apiKey->last_used_at?->toISOString(),
                    'created_at' => $apiKey->created_at->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $apiKeys,
        ]);
    }
}
