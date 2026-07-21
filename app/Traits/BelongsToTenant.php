<?php

declare(strict_types=1);

namespace App\Traits;

use App\Scopes\TenantScope;
use Modules\Company\Models\Company;

trait BelongsToTenant
{
    /**
     * Boot the trait and register scope and model hooks.
     */
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (auth()->check()) {
                $user = auth()->user();
                if (empty($model->company_id) && isset($user->company_id)) {
                    $model->company_id = $user->company_id;
                }
            }
        });
    }

    /**
     * Get the company that owns the resource.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
