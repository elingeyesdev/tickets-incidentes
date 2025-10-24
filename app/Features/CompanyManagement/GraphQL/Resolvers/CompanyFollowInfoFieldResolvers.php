<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Resolvers;

/**
 * Field resolvers for CompanyFollowInfo type
 *
 * Resolves fields for followed companies with metadata.
 * Used by myFollowedCompanies query.
 */
class CompanyFollowInfoFieldResolvers
{
    /**
     * Resolver for CompanyFollowInfo.id
     *
     * Returns the CompanyFollower record ID.
     *
     * @param \App\Features\CompanyManagement\Models\CompanyFollower $follower
     * @return string
     */
    public function id($follower): string
    {
        return $follower->id;
    }

    /**
     * Resolver for CompanyFollowInfo.company
     *
     * Returns Company object (as CompanyMinimal in schema).
     *
     * @param \App\Features\CompanyManagement\Models\CompanyFollower $follower
     * @return \App\Features\CompanyManagement\Models\Company
     */
    public function company($follower)
    {
        // Relationship is already loaded via ->with('company') in query
        return $follower->company;
    }

    /**
     * Resolver for CompanyFollowInfo.followedAt
     *
     * Returns when the user followed this company.
     *
     * @param \App\Features\CompanyManagement\Models\CompanyFollower $follower
     * @return string DateTime in ISO 8601 format
     */
    public function followedAt($follower): string
    {
        return $follower->followed_at->toIso8601String();
    }

    /**
     * Resolver for CompanyFollowInfo.myTicketsCount
     *
     * Returns count of tickets the authenticated user created for this company.
     * TODO: Implement when Ticketing feature is ready.
     *
     * @param \App\Features\CompanyManagement\Models\CompanyFollower $follower
     * @return int
     */
    public function myTicketsCount($follower): int
    {
        // Placeholder until Ticketing feature is implemented
        return 0;
    }

    /**
     * Resolver for CompanyFollowInfo.lastTicketCreatedAt
     *
     * Returns when the user last created a ticket for this company.
     * TODO: Implement when Ticketing feature is ready.
     *
     * @param \App\Features\CompanyManagement\Models\CompanyFollower $follower
     * @return string|null DateTime in ISO 8601 format or null
     */
    public function lastTicketCreatedAt($follower): ?string
    {
        // Placeholder until Ticketing feature is implemented
        return null;
    }

    /**
     * Resolver for CompanyFollowInfo.hasUnreadAnnouncements
     *
     * Returns whether there are unread announcements from this company.
     * TODO: Implement when Announcements feature is ready.
     *
     * @param \App\Features\CompanyManagement\Models\CompanyFollower $follower
     * @return bool
     */
    public function hasUnreadAnnouncements($follower): bool
    {
        // Placeholder until Announcements feature is implemented
        return false;
    }
}
