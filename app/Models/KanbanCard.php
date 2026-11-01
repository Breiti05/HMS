<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KanbanCard extends Model
{
    use BelongsToTenant;

    public const STATUSES = ['open', 'ordered', 'delivered'];

    protected $fillable = ['article_id', 'status', 'quantity'];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
