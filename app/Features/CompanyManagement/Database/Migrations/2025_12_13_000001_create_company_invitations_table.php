<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the company_invitations table for agent invitation management.
 * 
 * This table stores invitations sent by Company Admins to users,
 * allowing them to join as agents after accepting the invitation.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business.company_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Company sending the invitation
            $table->uuid('company_id');
            $table->foreign('company_id')
                ->references('id')
                ->on('business.companies')
                ->onDelete('cascade');

            // User being invited (must already exist in the system)
            $table->uuid('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('auth.users')
                ->onDelete('cascade');

            // Role to assign upon acceptance (default AGENT)
            $table->string('role_code', 50)->default('AGENT');
            $table->foreign('role_code')
                ->references('role_code')
                ->on('auth.roles')
                ->onDelete('restrict');

            // Invitation status
            $table->enum('status', ['PENDING', 'ACCEPTED', 'REJECTED', 'CANCELLED'])
                ->default('PENDING');

            // Who sent the invitation
            $table->uuid('invited_by');
            $table->foreign('invited_by')
                ->references('id')
                ->on('auth.users')
                ->onDelete('cascade');

            // Optional message from the inviter
            $table->text('message')->nullable();

            // When the user responded (accepted/rejected)
            $table->timestamp('responded_at')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes for common queries
            $table->index(['company_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('invited_by');

            // Unique constraint: Only one pending invitation per user per company
            $table->unique(['company_id', 'user_id', 'status'], 'unique_pending_invitation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business.company_invitations');
    }
};
