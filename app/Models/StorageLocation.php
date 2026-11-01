<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageLocation extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = ['warehouse_id', 'code', 'row', 'col', 'capacity'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }
}
