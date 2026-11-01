<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteMaterial extends Model
{
    use BelongsToTenant;

    public const PREFIX = 'HMS1';

    protected $fillable = ['site_id', 'article_id', 'storage_location_id', 'quantity', 'token'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'loaded_at' => 'datetime'];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class, 'storage_location_id');
    }

    /** Menge ohne überflüssige Nullen: 40 statt 40.000, 2.5 statt 2.500. */
    public function quantityLabel(): int|float
    {
        $q = (float) $this->quantity;

        return floor($q) == $q ? (int) $q : $q;
    }

    /** Inhalt des QR-Codes: Version | Baustellennummer | Materialnummer | Menge | Token. */
    public function qrPayload(): string
    {
        return implode('|', [
            self::PREFIX,
            $this->site->number,
            $this->article->number,
            $this->quantityLabel(),
            $this->token,
        ]);
    }
}
