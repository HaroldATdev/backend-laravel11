<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditService
{
    public static function log(
        string $action,
        ?string $modelType,
        mixed $modelId,
        mixed $oldValues,
        mixed $newValues,
        ?Request $request = null
    ): void {
        AuditLog::create([
            'action'     => $action,
            'model_type' => $modelType,
            'model_id'   => $modelId,
            'user_id'    => $request?->user()?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request?->ip(),
        ]);
    }
}
