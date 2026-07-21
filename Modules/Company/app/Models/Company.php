<?php

declare(strict_types=1);

namespace Modules\Company\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Acl\Models\User;
use Modules\Project\Models\Project;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Get the users belonging to the company.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'company_id');
    }

    /**
     * Get the projects belonging to the company.
     */
    public function projects()
    {
        return $this->hasMany(Project::class, 'company_id');
    }
}
