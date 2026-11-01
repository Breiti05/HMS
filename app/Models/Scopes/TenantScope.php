<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Filtert jede Abfrage auf den Betrieb des angemeldeten Benutzers.
 * Ohne angemeldeten Benutzer (z. B. Konsole, Seeder) wird nicht gefiltert.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if ($user && $user->tenant_id) {
            $builder->where($model->qualifyColumn('tenant_id'), $user->tenant_id);
        }
    }
}
