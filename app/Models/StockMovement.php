<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use BelongsToTenant;

    protected $fillable = ['article_id', 'storage_location_id', 'site_id', 'type', 'quantity', 'user_id', 'note', 'unit_cost', 'transfer_group'];

    protected static function booted(): void
    {
        // Buchungen sind unveränderlich; Korrekturen laufen über Gegenbuchungen.
        static::updating(fn () => throw new \LogicException('Bewegungen können nicht geändert werden.'));
        static::deleting(fn () => throw new \LogicException('Bewegungen können nicht gelöscht werden.'));
    }

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class, 'storage_location_id');
    }
}
