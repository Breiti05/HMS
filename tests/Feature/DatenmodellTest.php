<?php

use App\Exceptions\InvalidScan;
use App\Models\Article;
use App\Models\KanbanCard;
use App\Models\Site;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SiteMaterialService;
use App\Services\StockService;
use App\Support\TenantContext;
use Illuminate\Database\QueryException;

beforeEach(function () {
    [$this->tenant, $this->user] = betrieb();
    $this->actingAs($this->user);
    $this->lager = Warehouse::create(['name' => 'Hauptlager']);
    $this->platzA = StorageLocation::create(['warehouse_id' => $this->lager->id, 'code' => 'A-01']);
    $this->platzB = StorageLocation::create(['warehouse_id' => $this->lager->id, 'code' => 'A-02']);
    $this->rohr = Article::create(['number' => 'R-100', 'name' => 'Rohr', 'unit' => 'm', 'min_stock' => 10]);
    $this->stock = app(StockService::class);
});

// --- Mandantentrennung: fail-closed ---------------------------------------

it('liefert ohne Mandantenkontext keine Datensätze statt aller', function () {
    auth()->logout();

    expect(Article::count())->toBe(0);
});

it('weigert sich, ohne Mandantenkontext Datensätze anzulegen', function () {
    auth()->logout();

    expect(fn () => Article::create(['number' => 'X-1', 'name' => 'X', 'unit' => 'Stk']))
        ->toThrow(LogicException::class);
});

it('führt Code gezielt im Kontext eines Betriebs aus, z. B. in Jobs', function () {
    auth()->logout();

    $anzahl = TenantContext::run($this->tenant->id, fn () => Article::count());

    expect($anzahl)->toBe(1)
        ->and(Article::count())->toBe(0);
});

it('verlangt für jeden Benutzer einen Betrieb', function () {
    expect(fn () => User::factory()->create(['tenant_id' => null]))->toThrow(QueryException::class);
});

it('verhindert, dass ein Datensatz in einen anderen Betrieb verschoben wird', function () {
    [$fremd] = betrieb('Betrieb B');

    $this->rohr->tenant_id = $fremd->id;

    expect(fn () => $this->rohr->save())->toThrow(LogicException::class);
});

it('verbietet Fremdschlüssel auf Datensätze anderer Betriebe', function () {
    [, $userB] = betrieb('Betrieb B');
    $this->actingAs($userB);
    $fremderArtikel = Article::create(['number' => 'F-1', 'name' => 'Fremd', 'unit' => 'Stk']);

    $this->actingAs($this->user);

    expect(fn () => StockLevel::withoutGlobalScopes()->create([
        'article_id' => $fremderArtikel->id,
        'storage_location_id' => $this->platzA->id,
        'quantity' => 1,
    ]))->toThrow(QueryException::class);
});

// --- Historie bleibt erhalten ---------------------------------------------

it('behält die Buchungshistorie, wenn ein Artikel gelöscht wird', function () {
    $this->stock->receive($this->rohr, $this->platzA, 10);
    $this->rohr->delete();

    expect(Article::count())->toBe(0)
        ->and(StockMovement::count())->toBe(1);
});

it('verbietet das endgültige Löschen eines Artikels mit Buchungen', function () {
    $this->stock->receive($this->rohr, $this->platzA, 10);

    expect(fn () => $this->rohr->forceDelete())->toThrow(QueryException::class);
});

it('verbietet das Ändern und Löschen von Bewegungen', function () {
    $this->stock->receive($this->rohr, $this->platzA, 10);
    $bewegung = StockMovement::first();

    expect(fn () => $bewegung->update(['quantity' => 99]))->toThrow(LogicException::class);
    expect(fn () => $bewegung->delete())->toThrow(LogicException::class);
});

// --- Buchungslogik ---------------------------------------------------------

it('lehnt eine Umlagerung auf denselben Lagerplatz ab', function () {
    $this->stock->receive($this->rohr, $this->platzA, 10);

    expect(fn () => $this->stock->transfer($this->rohr, $this->platzA, $this->platzA, 5))
        ->toThrow(InvalidArgumentException::class);
});

it('verknüpft beide Buchungen einer Umlagerung über eine Gruppen-ID', function () {
    $this->stock->receive($this->rohr, $this->platzA, 10);
    $this->stock->transfer($this->rohr, $this->platzA, $this->platzB, 4);

    $paar = StockMovement::whereIn('type', ['transfer_out', 'transfer_in'])->get();

    expect($paar)->toHaveCount(2)
        ->and($paar[0]->transfer_group)->not->toBeNull()
        ->and($paar[0]->transfer_group)->toBe($paar[1]->transfer_group);
});

it('legt keine neue Kanban-Karte an, solange schon bestellt wurde', function () {
    $this->stock->receive($this->rohr, $this->platzA, 50);
    $this->stock->issue($this->rohr, $this->platzA, 45);
    KanbanCard::first()->update(['status' => 'ordered']);

    $this->stock->issue($this->rohr, $this->platzA, 1);

    expect(KanbanCard::count())->toBe(1);
});

it('nutzt die Nachschubmenge des Artikels für die Kanban-Karte', function () {
    $this->rohr->update(['reorder_qty' => 100]);
    $this->stock->receive($this->rohr, $this->platzA, 50);
    $this->stock->issue($this->rohr, $this->platzA, 45);

    expect((float) KanbanCard::first()->quantity)->toBe(100.0);
});

it('verbietet Buchungen auf Datensätzen verschiedener Betriebe', function () {
    [, $userB] = betrieb('Betrieb B');
    $this->actingAs($userB);
    $fremderPlatz = TenantContext::run($userB->tenant_id, function () {
        $l = Warehouse::create(['name' => 'Lager B']);

        return StorageLocation::create(['warehouse_id' => $l->id, 'code' => 'B-01']);
    });
    $this->actingAs($this->user);

    expect(fn () => $this->stock->receive($this->rohr, $fremderPlatz, 5))->toThrow(LogicException::class);
});

// --- Baustelle und Etikett -------------------------------------------------

it('erzeugt unvorhersagbare Hex-Token für Etiketten', function () {
    $site = Site::create(['number' => 'B-1', 'name' => 'Test']);
    $m = app(SiteMaterialService::class)->assign($site, $this->rohr, $this->platzA, 1);

    expect($m->token)->toMatch('/^[a-f0-9]{32}$/');
});

it('verlädt nur für aktive Baustellen', function () {
    $this->stock->receive($this->rohr, $this->platzA, 20);
    $site = Site::create(['number' => 'B-1', 'name' => 'Test', 'status' => 'done']);
    $service = app(SiteMaterialService::class);
    $m = $service->assign($site, $this->rohr, $this->platzA, 5);

    expect(fn () => $service->scan($m->qrPayload()))->toThrow(InvalidScan::class);
    expect($this->stock->quantityAt($this->rohr, $this->platzA))->toBe(20.0);
});

it('unterscheidet Haupt- und Fahrzeuglager', function () {
    $van = Warehouse::create(['name' => 'Transporter 1', 'type' => 'vehicle']);

    expect($this->lager->fresh()->type)->toBe('main')
        ->and($van->fresh()->type)->toBe('vehicle');
});
