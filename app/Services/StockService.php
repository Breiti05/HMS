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
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

/**
 * Alle Bestandsänderungen laufen hier durch: Bestand, Protokoll und
 * Kanban-Nachschub bleiben so immer konsistent.
 */
class StockService
{
    public function receive(Article $article, StorageLocation $location, float $quantity, ?string $note = null): void
    {
        $this->assertPositive($quantity);
        $this->assertSameTenant($article, $location);

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
        $this->assertSameTenant($article, $location);

        DB::transaction(function () use ($article, $location, $quantity, $note, $site, $type) {
            $this->change($article, $location, -$quantity);
            $this->log($article, $location, $type, $quantity, $note, $site);
            $this->checkMinimum($article);
        });
    }

    public function transfer(Article $article, StorageLocation $from, StorageLocation $to, float $quantity): void
    {
        $this->assertPositive($quantity);
        $this->assertSameTenant($article, $from);
        $this->assertSameTenant($article, $to);

        if ($from->id === $to->id) {
            throw new InvalidArgumentException('Quelle und Ziel der Umlagerung müssen verschieden sein.');
        }

        DB::transaction(function () use ($article, $from, $to, $quantity) {
            // Beide Zeilen immer in gleicher Reihenfolge sperren, sonst drohen Deadlocks (A→B und B→A).
            $this->ensureLevel($article, $from);
            $this->ensureLevel($article, $to);
            StockLevel::where('article_id', $article->id)
                ->whereIn('storage_location_id', [$from->id, $to->id])
                ->orderBy('storage_location_id')
                ->lockForUpdate()
                ->get();

            $group = (string) Str::uuid();
            $this->change($article, $from, -$quantity);
            $this->log($article, $from, 'transfer_out', $quantity, null, null, $group);
            $this->change($article, $to, $quantity);
            $this->log($article, $to, 'transfer_in', $quantity, null, null, $group);
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
        $this->ensureLevel($article, $location);

        $level = StockLevel::where('article_id', $article->id)
            ->where('storage_location_id', $location->id)
            ->lockForUpdate()
            ->firstOrFail();

        $current = (float) $level->quantity;
        $new = round($current + $delta, 3);

        if ($new < 0) {
            throw new InsufficientStock(sprintf(
                'Nicht genug Bestand: %s hat an %s nur %s %s, gebraucht werden %s.',
                $article->name, $location->code, $this->fmt($current), $article->unit, $this->fmt(abs($delta)),
            ));
        }

        $level->update(['quantity' => $new]);
    }

    /** Legt die Bestandszeile an, falls es sie noch nicht gibt, damit sie gesperrt werden kann. */
    private function ensureLevel(Article $article, StorageLocation $location): void
    {
        DB::table('stock_levels')->insertOrIgnore([
            'tenant_id' => $article->tenant_id,
            'article_id' => $article->id,
            'storage_location_id' => $location->id,
            'quantity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function log(Article $article, StorageLocation $location, string $type, float $quantity, ?string $note = null, ?Site $site = null, ?string $group = null): void
    {
        StockMovement::create([
            'article_id' => $article->id,
            'storage_location_id' => $location->id,
            'site_id' => $site?->id,
            'type' => $type,
            'quantity' => $quantity,
            'user_id' => Auth::id(),
            'note' => $note,
            'transfer_group' => $group,
        ]);
    }

    /** Unter Mindestbestand: höchstens eine offene oder bestellte Kanban-Karte pro Artikel. */
    private function checkMinimum(Article $article): void
    {
        // Artikelzeile als Serialisierungspunkt sperren, damit parallele Buchungen keine Doppelkarten erzeugen.
        $locked = Article::whereKey($article->id)->lockForUpdate()->first();
        $min = (float) $locked->min_stock;
        $total = $this->totalQuantity($locked);

        if ($min <= 0 || $total >= $min) {
            return;
        }

        $pending = KanbanCard::where('article_id', $locked->id)
            ->whereIn('status', ['open', 'ordered'])
            ->exists();

        if ($pending) {
            return;
        }

        KanbanCard::create([
            'article_id' => $locked->id,
            'status' => 'open',
            'quantity' => $locked->reorder_qty !== null
                ? (float) $locked->reorder_qty
                : round(max($min * 2 - $total, $min), 3),
        ]);
    }

    private function assertSameTenant(Article $article, StorageLocation $location): void
    {
        if ($article->tenant_id != $location->tenant_id) {
            throw new LogicException('Artikel und Lagerplatz gehören zu verschiedenen Betrieben.');
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
