<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteTask extends Model
{
    use BelongsToTenant;

    protected $fillable = ['site_id', 'title'];

    protected function casts(): array
    {
        return ['done_at' => 'datetime'];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function complete(User $user): void
    {
        $this->forceFill(['done_at' => now(), 'done_by' => $user->id])->save();
    }

    public function reopen(): void
    {
        $this->forceFill(['done_at' => null, 'done_by' => null])->save();
    }
}
