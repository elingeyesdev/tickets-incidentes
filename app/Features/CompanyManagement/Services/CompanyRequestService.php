<?php

namespace App\Features\CompanyManagement\Services;

use App\Features\CompanyManagement\Events\CompanyRequestApproved;
use App\Features\CompanyManagement\Events\CompanyRequestRejected;
use App\Features\CompanyManagement\Events\CompanyRequestSubmitted;
use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Services\RoleService;
use App\Features\UserManagement\Services\UserService;
use App\Shared\Helpers\CodeGenerator;
use Illuminate\Support\Facades\DB;

class CompanyRequestService
{
    public function __construct(
        private CompanyService $companyService,
        private UserService $userService,
        private RoleService $roleService
    ) {}

    /**
     * Submit a new company request.
     */
    public function submit(array $data): CompanyRequest
    {
        return DB::transaction(function () use ($data) {
            // Generate unique request code
            $requestCode = CodeGenerator::generate('REQ');

            // Create request
            $request = CompanyRequest::create([
                'request_code' => $requestCode,
                'company_name' => $data['company_name'],
                'legal_name' => $data['legal_name'] ?? null,
                'admin_email' => $data['admin_email'],
                'business_description' => $data['business_description'],
                'website' => $data['website'] ?? null,
                'industry_type' => $data['industry_type'],
                'estimated_users' => $data['estimated_users'] ?? null,
                'contact_address' => $data['contact_address'] ?? null,
                'contact_city' => $data['contact_city'] ?? null,
                'contact_country' => $data['contact_country'] ?? null,
                'contact_postal_code' => $data['contact_postal_code'] ?? null,
                'tax_id' => $data['tax_id'] ?? null,
                'status' => 'pending',
            ]);

            // Fire event
            event(new CompanyRequestSubmitted($request));

            return $request;
        });
    }

    /**
     * Approve a company request.
     *
     * This is a complex process:
     * 1. Find or create admin user
     * 2. Create company
     * 3. Assign COMPANY_ADMIN role to admin user
     * 4. Mark request as approved
     * 5. Fire event (sends email)
     */
    public function approve(CompanyRequest $request, User $reviewer): Company
    {
        // Validate request is pending
        if (!$request->isPending()) {
            throw new \Exception('Only pending requests can be approved');
        }

        return DB::transaction(function () use ($request, $reviewer) {
            // 1. Find or create admin user
            $adminUser = User::where('email', $request->admin_email)->first();

            if (!$adminUser) {
                // Create new user from request data
                $adminUser = $this->userService->createFromCompanyRequest(
                    $request->admin_email,
                    $request->company_name
                );
            }

            // 2. Create company
            $company = $this->companyService->create([
                'name' => $request->company_name,
                'legal_name' => $request->legal_name,
                'support_email' => $request->admin_email,
                'website' => $request->website,
                'contact_address' => $request->contact_address,
                'contact_city' => $request->contact_city,
                'contact_country' => $request->contact_country,
                'contact_postal_code' => $request->contact_postal_code,
                'tax_id' => $request->tax_id,
                'created_from_request_id' => $request->id,
            ], $adminUser);

            // 3. Assign COMPANY_ADMIN role to admin user
            $this->roleService->assignRoleToUser(
                userId: $adminUser->id,
                roleCode: 'company_admin',
                companyId: $company->id,
                assignedBy: $reviewer->id
            );

            // 4. Mark request as approved
            $request->markAsApproved($reviewer, $company);

            // 5. Fire event (triggers email sending)
            event(new CompanyRequestApproved($request, $company, $adminUser));

            return $company;
        });
    }

    /**
     * Reject a company request.
     */
    public function reject(CompanyRequest $request, User $reviewer, string $reason): CompanyRequest
    {
        // Validate request is pending
        if (!$request->isPending()) {
            throw new \Exception('Only pending requests can be rejected');
        }

        DB::transaction(function () use ($request, $reviewer, $reason) {
            // Mark as rejected
            $request->markAsRejected($reviewer, $reason);

            // Fire event (triggers email sending)
            event(new CompanyRequestRejected($request, $reason));
        });

        return $request->fresh();
    }

    /**
     * Get pending requests.
     */
    public function getPending(int $limit = 15): \Illuminate\Database\Eloquent\Collection
    {
        return CompanyRequest::pending()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all requests (for admin).
     */
    public function getAll(?string $status = null, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        $query = CompanyRequest::query();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if email already has a pending request.
     */
    public function hasPendingRequest(string $email): bool
    {
        return CompanyRequest::where('admin_email', $email)
            ->where('status', 'pending')
            ->exists();
    }
}
