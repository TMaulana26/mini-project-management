<?php

declare(strict_types=1);

namespace Modules\Project\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Acl\Models\User;

class Comment extends Model
{
    use Auditable, BelongsToTenant, HasFactory;

    protected $fillable = [
        'task_id',
        'company_id',
        'user_id',
        'content',
    ];

    /**
     * Get the task that owns the comment.
     */
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * Get the user who wrote the comment.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
