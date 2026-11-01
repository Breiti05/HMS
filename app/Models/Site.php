<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use BelongsToTenant, SoftDeletes;

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
        if ($this->relationLoaded('tasks')) {
            $total = $this->tasks->count();
            $done = $this->tasks->whereNotNull('done_at')->count();
        } else {
            $total = $this->tasks()->count();
            $done = $this->tasks()->whereNotNull('done_at')->count();
        }

        return $total === 0 ? 0 : (int) round($done / $total * 100);
    }
}
