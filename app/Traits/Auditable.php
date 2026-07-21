<?php

declare(strict_types=1);

namespace App\Traits;

use Modules\Company\Models\AuditLog;

trait Auditable
{
    /**
     * Boot the trait and register model event listeners.
     */
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            self::logAudit($model, 'created', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            $old = [];

            // Remove updated_at from audited changes
            unset($changes['updated_at']);

            if (! empty($changes)) {
                foreach ($changes as $key => $value) {
                    $old[$key] = $model->getOriginal($key);
                }
                self::logAudit($model, 'updated', $old, $changes);
            }
        });

        static::deleted(function ($model) {
            self::logAudit($model, 'deleted', $model->getAttributes(), null);
        });
    }

    /**
     * Log audit trail event.
     */
    protected static function logAudit($model, string $event, ?array $old, ?array $new): void
    {
        if ($model instanceof AuditLog) {
            return;
        }

        $userId = auth()->id();
        $companyId = $model->company_id ?? (auth()->check() ? auth()->user()->company_id : null);

        if ($companyId) {
            AuditLog::create([
                'company_id' => $companyId,
                'user_id' => $userId,
                'event' => $event,
                'auditable_type' => get_class($model),
                'auditable_id' => $model->id,
                'old_values' => $old ? array_filter($old, fn ($key) => $key !== 'password', ARRAY_FILTER_USE_KEY) : null,
                'new_values' => $new ? array_filter($new, fn ($key) => $key !== 'password', ARRAY_FILTER_USE_KEY) : null,
            ]);
        }
    }
}
