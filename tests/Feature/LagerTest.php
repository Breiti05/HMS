<?php

use App\Exceptions\InsufficientStock;
use App\Models\Article;
use App\Models\KanbanCard;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use App\Services\StockService;

beforeEach(function () {
    [$this->tenant, $this->user] = betrieb();
    $this->actingAs($this->user);

    $this->lager = Warehouse::create(['name' => 'Hauptlager']);
    $this->platzA = StorageLocation::create(['warehouse_id' => $this->lager->id, 'code' => 'A-01', 'row' => 1, 'col' => 1]);
    $this->platzB = StorageLocation::create(['warehouse_id' => $this->lager->id, 'code' => 'A-02', 'row' => 1, 'col' => 2]);
    $this->rohr = Article::create(['number' => 'R-100', 'name' => 'Rohr 20mm', 'unit' => 'm', 'min_stock' => 10]);
    $this->stock = app(StockService::class);
});

it('erhöht den Bestand beim Wareneingang und protokolliert die Bewegung', function () {
    $this->stock->receive($this->rohr, $this->platzA, 50, 'Lieferschein 4711');

    expect($this->stock->quantityAt($this->rohr, $this->platzA))->toBe(50.0)
        ->and($this->rohr->movements()->count())->toBe(1);

    $bewegung = $this->rohr->movements()->first();
    expect($bewegung->type)->toBe('receipt')
        ->and((float) $bewegung->quantity)->toBe(50.0)
        ->and($bewegung->user_id)->toBe($this->user->id)
        ->and($bewegung->note)->toBe('Lieferschein 4711');
});

it('verringert den Bestand beim Warenausgang', function () {
    $this->stock->receive($this->rohr, $this->platzA, 50);
    $this->stock->issue($this->rohr, $this->platzA, 20);

    expect($this->stock->quantityAt($this->rohr, $this->platzA))->toBe(30.0);
});

it('verweigert einen Ausgang über den Bestand hinaus und ändert nichts', function () {
    $this->stock->receive($this->rohr, $this->platzA, 5);

    expect(fn () => $this->stock->issue($this->rohr, $this->platzA, 6))
        ->toThrow(InsufficientStock::class);

    expect($this->stock->quantityAt($this->rohr, $this->platzA))->toBe(5.0)
        ->and($this->rohr->movements()->count())->toBe(1);
});

it('lehnt Mengen kleiner oder gleich null ab', function () {
    expect(fn () => $this->stock->receive($this->rohr, $this->platzA, 0))->toThrow(InvalidArgumentException::class);
    expect(fn () => $this->stock->issue($this->rohr, $this->platzA, -3))->toThrow(InvalidArgumentException::class);
});

it('summiert den Gesamtbestand über alle Lagerplätze', function () {
    $this->stock->receive($this->rohr, $this->platzA, 30);
    $this->stock->receive($this->rohr, $this->platzB, 12.5);

    expect($this->stock->totalQuantity($this->rohr))->toBe(42.5);
});

it('lagert zwischen Lagerplätzen um', function () {
    $this->stock->receive($this->rohr, $this->platzA, 30);
    $this->stock->transfer($this->rohr, $this->platzA, $this->platzB, 10);

    expect($this->stock->quantityAt($this->rohr, $this->platzA))->toBe(20.0)
        ->and($this->stock->quantityAt($this->rohr, $this->platzB))->toBe(10.0)
        ->and($this->stock->totalQuantity($this->rohr))->toBe(30.0);
});

it('legt eine Kanban-Karte an, sobald der Mindestbestand unterschritten wird', function () {
    $this->stock->receive($this->rohr, $this->platzA, 50);
    expect(KanbanCard::count())->toBe(0);

    $this->stock->issue($this->rohr, $this->platzA, 45);

    $karte = KanbanCard::first();
    expect(KanbanCard::count())->toBe(1)
        ->and($karte->article_id)->toBe($this->rohr->id)
        ->and($karte->status)->toBe('open');
});

it('legt keine zweite offene Kanban-Karte für denselben Artikel an', function () {
    $this->stock->receive($this->rohr, $this->platzA, 50);
    $this->stock->issue($this->rohr, $this->platzA, 45);
    $this->stock->issue($this->rohr, $this->platzA, 1);

    expect(KanbanCard::count())->toBe(1);
});

it('zeigt Artikel unter Mindestbestand', function () {
    $kabel = Article::create(['number' => 'K-200', 'name' => 'Kabel', 'unit' => 'm', 'min_stock' => 5]);
    $this->stock->receive($this->rohr, $this->platzA, 3);
    $this->stock->receive($kabel, $this->platzA, 100);

    $knapp = $this->stock->belowMinimum();

    expect($knapp->pluck('number')->all())->toBe(['R-100']);
});
