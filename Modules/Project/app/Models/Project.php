<?php

declare(strict_types=1);

namespace Modules\Project\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use Auditable, BelongsToTenant, HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
    ];

    /**
     * Get the tasks for the project.
     */
    public function tasks()
    {
        return $this->hasMany(Task::class, 'project_id');
    }
}
