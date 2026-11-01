<?php

namespace App\Services;

use App\Exceptions\InsufficientStock;
use App\Models\Article;
use App\Models\KanbanCard;
use App\Models\Site;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StorageLocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Alle Bestandsänderungen laufen hier durch: Bestand, Protokoll und
 * Kanban-Nachschub bleiben so immer konsistent.
 */
class StockService
{
    public function receive(Article $article, StorageLocation $location, float $quantity, ?string $note = null): void
    {
        $this->assertPositive($quantity);

        DB::transaction(function () use ($article, $location, $quantity, $note) {
            $this->change($article, $location, $quantity);
            $this->log($article, $location, 'receipt', $quantity, $note);
        });
    }

    public function issue(
        Article $article,
        StorageLocation $location,
        float $quantity,
        ?string $note = null,
        ?Site $site = null,
        string $type = 'issue',
    ): void {
        $this->assertPositive($quantity);

        DB::transaction(function () use ($article, $location, $quantity, $note, $site, $type) {
            $this->change($article, $location, -$quantity);
            $this->log($article, $location, $type, $quantity, $note, $site);
            $this->checkMinimum($article);
        });
    }

    public function transfer(Article $article, StorageLocation $from, StorageLocation $to, float $quantity): void
    {
        $this->assertPositive($quantity);

        DB::transaction(function () use ($article, $from, $to, $quantity) {
            $this->change($article, $from, -$quantity);
            $this->log($article, $from, 'transfer_out', $quantity);
            $this->change($article, $to, $quantity);
            $this->log($article, $to, 'transfer_in', $quantity);
        });
    }

    public function quantityAt(Article $article, StorageLocation $location): float
    {
        $level = StockLevel::where('article_id', $article->id)
            ->where('storage_location_id', $location->id)
            ->first();

        return $level ? (float) $level->quantity : 0.0;
    }

    public function totalQuantity(Article $article): float
    {
        return (float) StockLevel::where('article_id', $article->id)->sum('quantity');
    }

    /** Artikel, deren Gesamtbestand unter dem Mindestbestand liegt. */
    public function belowMinimum(): Collection
    {
        return Article::where('min_stock', '>', 0)
            ->get()
            ->filter(fn (Article $a) => $this->totalQuantity($a) < (float) $a->min_stock)
            ->values();
    }

    private function change(Article $article, StorageLocation $location, float $delta): void
    {
        $level = StockLevel::where('article_id', $article->id)
            ->where('storage_location_id', $location->id)
            ->lockForUpdate()
            ->first();

        $current = $level ? (float) $level->quantity : 0.0;
        $new = round($current + $delta, 3);

        if ($new < 0) {
            throw new InsufficientStock(sprintf(
                'Nicht genug Bestand: %s hat an %s nur %s %s, gebraucht werden %s.',
                $article->name, $location->code, $this->fmt($current), $article->unit, $this->fmt(abs($delta)),
            ));
        }

        if ($level) {
            $level->update(['quantity' => $new]);
        } else {
            StockLevel::create([
                'article_id' => $article->id,
                'storage_location_id' => $location->id,
                'quantity' => $new,
            ]);
        }
    }

    private function log(Article $article, StorageLocation $location, string $type, float $quantity, ?string $note = null, ?Site $site = null): void
    {
        StockMovement::create([
            'article_id' => $article->id,
            'storage_location_id' => $location->id,
            'site_id' => $site?->id,
            'type' => $type,
            'quantity' => $quantity,
            'user_id' => Auth::id(),
            'note' => $note,
        ]);
    }

    /** Unter Mindestbestand: genau eine offene Kanban-Karte pro Artikel. */
    private function checkMinimum(Article $article): void
    {
        $min = (float) $article->min_stock;
        $total = $this->totalQuantity($article);

        if ($min <= 0 || $total >= $min) {
            return;
        }

        $hasOpen = KanbanCard::where('article_id', $article->id)->where('status', 'open')->exists();

        if (! $hasOpen) {
            KanbanCard::create([
                'article_id' => $article->id,
                'status' => 'open',
                'quantity' => round($min * 2 - $total, 3),
            ]);
        }
    }

    private function assertPositive(float $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Die Menge muss größer als null sein.');
        }
    }

    private function fmt(float $q): string
    {
        return rtrim(rtrim(number_format($q, 3, ',', ''), '0'), ',');
    }
}
