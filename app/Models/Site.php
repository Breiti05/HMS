<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use BelongsToTenant;

    protected $fillable = ['number', 'name', 'address', 'status'];

    public function tasks(): HasMany
    {
        return $this->hasMany(SiteTask::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(SiteMaterial::class);
    }

    /** Fortschritt in Prozent (0–100) aus den abgehakten Aufgaben. */
    public function progress(): int
    {
        $total = $this->tasks()->count();

        if ($total === 0) {
            return 0;
        }

        return (int) round($this->tasks()->whereNotNull('done_at')->count() / $total * 100);
    }
}
