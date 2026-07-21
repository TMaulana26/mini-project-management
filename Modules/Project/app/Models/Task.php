<?php

declare(strict_types=1);

namespace Modules\Project\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Acl\Models\User;

class Task extends Model
{
    use Auditable, BelongsToTenant, HasFactory;

    protected $fillable = [
        'project_id',
        'company_id',
        'assigned_to_user_id',
        'title',
        'description',
        'status',
    ];

    /**
     * Get the project that owns the task.
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the user assigned to the task.
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
