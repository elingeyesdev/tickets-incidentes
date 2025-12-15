<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Models;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\UserManagement\Models\User;
use App\Shared\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'company_announcements';

    protected $fillable = [
        'company_id',
        'author_id',
        'title',
        'content',
        'type',
        'status',
        'metadata',
        'published_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'status' => PublicationStatus::class,
        'type' => AnnouncementType::class,
        'published_at' => 'datetime',
    ];

    /**
     * Get the company that owns the announcement.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the author (user) that created the announcement.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Scope to filter only published announcements.
     */
    public function scopePublished($query)
    {
        return $query->where('status', PublicationStatus::PUBLISHED);
    }

    /**
     * Determine if the announcement is editable.
     * Only DRAFT and SCHEDULED announcements can be edited.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [
            PublicationStatus::DRAFT,
            PublicationStatus::SCHEDULED,
        ]);
    }

    /**
     * Get the scheduled_for date from metadata as a Carbon instance.
     */
    public function getScheduledForAttribute(): ?Carbon
    {
        if (isset($this->metadata['scheduled_for'])) {
            return Carbon::parse($this->metadata['scheduled_for']);
        }

        return null;
    }

    /**
     * Get the formatted urgency level string.
     * Returns a localized urgency string.
     */
    public function formattedUrgency(): string
    {
        $urgency = $this->metadata['urgency'] ?? 'MEDIUM';

        return match ($urgency) {
            'LOW' => __('Low'),
            'MEDIUM' => __('Medium'),
            'HIGH' => __('High'),
            'CRITICAL' => __('Critical'),
            default => $urgency,
        };
    }
}
