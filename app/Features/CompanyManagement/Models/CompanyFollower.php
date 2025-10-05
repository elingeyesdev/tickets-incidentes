<?php

namespace App\Features\CompanyManagement\Models;

use App\Features\UserManagement\Models\User;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyFollower extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'business.user_company_followers';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'company_id',
        'followed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'id' => 'string',
        'followed_at' => 'datetime',
    ];

    /**
     * Get the user who follows the company.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the company being followed.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-set followed_at timestamp on creation
        static::creating(function ($model) {
            if (!$model->followed_at) {
                $model->followed_at = now();
            }
        });
    }
}
