<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = ['name', 'type'];

    public function locations(): HasMany
    {
        return $this->hasMany(StorageLocation::class);
    }
}
