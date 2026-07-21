<?php

declare(strict_types=1);

namespace Modules\Company\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Modules\Acl\Models\User;

class AuditLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id',
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Get the user who performed the action.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the owning auditable model.
     */
    public function auditable()
    {
        return $this->morphTo();
    }
}
