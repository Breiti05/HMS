<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = ['number', 'name', 'unit', 'min_stock', 'reorder_qty', 'description', 'active'];

    protected function casts(): array
    {
        return ['min_stock' => 'decimal:3', 'reorder_qty' => 'decimal:3', 'active' => 'boolean'];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
