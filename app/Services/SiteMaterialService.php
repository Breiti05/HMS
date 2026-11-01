<?php

namespace App\Services;

use App\Exceptions\InvalidScan;
use App\Models\Article;
use App\Models\Site;
use App\Models\SiteMaterial;
use App\Models\StorageLocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SiteMaterialService
{
    public function __construct(private StockService $stock) {}

    public function assign(Site $site, Article $article, StorageLocation $from, float $quantity): SiteMaterial
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Die Menge muss größer als null sein.');
        }

        return SiteMaterial::create([
            'site_id' => $site->id,
            'article_id' => $article->id,
            'storage_location_id' => $from->id,
            'quantity' => $quantity,
            'token' => Str::random(32),
        ]);
    }

    /**
     * Verladescan: prüft das Etikett, bucht den Ausgang und merkt sich,
     * dass das Material jetzt auf dem Weg zur Baustelle ist.
     */
    public function scan(string $payload): SiteMaterial
    {
        $parts = explode('|', trim($payload));

        if (count($parts) !== 5 || $parts[0] !== SiteMaterial::PREFIX) {
            throw new InvalidScan('Das ist kein gültiges HMS-Etikett.');
        }

        [, $siteNumber, $articleNumber, $quantity, $token] = $parts;

        return DB::transaction(function () use ($siteNumber, $articleNumber, $quantity, $token) {
            $material = SiteMaterial::with(['site', 'article', 'location'])
                ->where('token', $token)
                ->lockForUpdate()
                ->first();

            if (! $material) {
                throw new InvalidScan('Dieses Etikett ist unbekannt.');
            }

            if ($material->site->number !== $siteNumber
                || $material->article->number !== $articleNumber
                || (string) $material->quantityLabel() !== $quantity) {
                throw new InvalidScan('Das Etikett stimmt nicht mit der Buchung überein.');
            }

            if ($material->loaded_at) {
                throw new InvalidScan('Dieses Etikett wurde bereits gescannt.');
            }

            $this->stock->issue(
                $material->article,
                $material->location,
                (float) $material->quantity,
                'Verladen für Baustelle '.$material->site->number,
                $material->site,
                'site_issue',
            );

            $material->forceFill(['loaded_at' => now(), 'loaded_by' => Auth::id()])->save();

            return $material;
        });
    }
}
