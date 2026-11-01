<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    use BelongsToTenant;

    protected $fillable = ['number', 'name', 'unit', 'min_stock', 'description'];

    protected function casts(): array
    {
        return ['min_stock' => 'decimal:3'];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
