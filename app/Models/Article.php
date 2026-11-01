<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use BelongsToTenant;

    protected $fillable = ['number', 'name', 'unit', 'min_stock', 'description'];

    protected function casts(): array
    {
        return ['min_stock' => 'decimal:3'];
    }
}
