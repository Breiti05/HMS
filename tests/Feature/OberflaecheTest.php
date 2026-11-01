<?php

use App\Models\Article;
use App\Models\KanbanCard;
use App\Models\Site;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use App\Services\SiteMaterialService;
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

it('schickt Gäste von allen Seiten zur Anmeldung', function () {
    auth()->logout();

    foreach (['/', '/lager', '/baustellen', '/kanban', '/scan'] as $url) {
        $this->get($url)->assertRedirect('/anmelden');
    }
});

it('zeigt auf der Übersicht die Kennzahlen als Kacheln', function () {
    $this->stock->receive($this->rohr, $this->platzA, 12);
    $this->stock->issue($this->rohr, $this->platzA, 9); // 3 < 10 -> knapp, Kanban offen
    Site::create(['number' => 'B-1', 'name' => 'Neubau Schmidt']);

    $this->get('/')
        ->assertOk()
        ->assertSee('data-kachel="artikel" data-wert="1"', false)
        ->assertSee('data-kachel="knapp" data-wert="1"', false)
        ->assertSee('data-kachel="baustellen" data-wert="1"', false)
        ->assertSee('data-kachel="kanban" data-wert="1"', false)
        ->assertSee('Neubau Schmidt');
});

it('zeigt das Lager als Raster mit Status je Lagerplatz', function () {
    $this->stock->receive($this->rohr, $this->platzA, 3); // unter Mindestbestand 10

    $this->get('/lager')
        ->assertOk()
        ->assertSee('Hauptlager')
        ->assertSee('data-platz="A-01" data-status="knapp"', false)
        ->assertSee('data-platz="A-02" data-status="leer"', false)
        ->assertSee('Rohr 20mm');
});

it('bucht Wareneingang und Warenausgang über das Formular', function () {
    $this->post('/lager/buchen', [
        'article_id' => $this->rohr->id, 'storage_location_id' => $this->platzA->id,
        'type' => 'receipt', 'quantity' => 40, 'note' => 'Lieferschein 1',
    ])->assertRedirect('/lager');

    expect($this->stock->quantityAt($this->rohr, $this->platzA))->toBe(40.0);

    $this->post('/lager/buchen', [
        'article_id' => $this->rohr->id, 'storage_location_id' => $this->platzA->id,
        'type' => 'issue', 'quantity' => 15,
    ])->assertRedirect('/lager');

    expect($this->stock->quantityAt($this->rohr, $this->platzA))->toBe(25.0);
});

it('meldet zu wenig Bestand beim Ausgang als Fehlermeldung und bucht nichts', function () {
    $this->stock->receive($this->rohr, $this->platzA, 5);

    $this->from('/lager')->post('/lager/buchen', [
        'article_id' => $this->rohr->id, 'storage_location_id' => $this->platzA->id,
        'type' => 'issue', 'quantity' => 6,
    ])->assertRedirect('/lager')->assertSessionHasErrors('quantity');

    expect($this->stock->quantityAt($this->rohr, $this->platzA))->toBe(5.0);
});

it('lehnt Buchungen mit Artikeln oder Lagerplätzen anderer Betriebe ab', function () {
    [, $userB] = betrieb('Betrieb B');
    $fremd = \App\Support\TenantContext::run($userB->tenant_id, fn () => Article::create(['number' => 'F-1', 'name' => 'Fremd', 'unit' => 'Stk']));

    $this->post('/lager/buchen', [
        'article_id' => $fremd->id, 'storage_location_id' => $this->platzA->id,
        'type' => 'receipt', 'quantity' => 1,
    ])->assertSessionHasErrors('article_id');
});

it('legt Baustellen an und zeigt sie als Karten mit Fortschritt', function () {
    $this->post('/baustellen', ['number' => 'B-7', 'name' => 'Sanierung Bäckerei', 'address' => 'Markt 1'])
        ->assertRedirect();

    $site = Site::where('number', 'B-7')->firstOrFail();
    $a = $site->tasks()->create(['title' => 'Demontage']);
    $site->tasks()->create(['title' => 'Montage']);
    $a->complete($this->user);

    $this->get('/baustellen')
        ->assertOk()
        ->assertSee('Sanierung Bäckerei')
        ->assertSee('data-fortschritt="50"', false);
});

it('zeigt eine Baustelle mit Aufgaben und hakt sie ab', function () {
    $site = Site::create(['number' => 'B-1', 'name' => 'Neubau Schmidt']);

    $this->post("/baustellen/{$site->id}/aufgaben", ['title' => 'Rohinstallation'])->assertRedirect();
    $task = $site->tasks()->firstOrFail();

    $this->get("/baustellen/{$site->id}")
        ->assertOk()
        ->assertSee('Rohinstallation')
        ->assertSee('data-fortschritt="0"', false);

    $this->post("/aufgaben/{$task->id}/umschalten")->assertRedirect();
    expect($task->fresh()->done_at)->not->toBeNull();

    $this->post("/aufgaben/{$task->id}/umschalten")->assertRedirect();
    expect($task->fresh()->done_at)->toBeNull();
});

it('ordnet Material zu, zeigt den Etikettenlink und den Verladestatus', function () {
    $this->stock->receive($this->rohr, $this->platzA, 100);
    $site = Site::create(['number' => 'B-1', 'name' => 'Neubau Schmidt']);

    $this->post("/baustellen/{$site->id}/material", [
        'article_id' => $this->rohr->id, 'storage_location_id' => $this->platzA->id, 'quantity' => 30,
    ])->assertRedirect();

    $m = $site->materials()->firstOrFail();

    $this->get("/baustellen/{$site->id}")
        ->assertOk()
        ->assertSee('Rohr 20mm')
        ->assertSee(route('material.label', $m), false)
        ->assertSee('data-material-status="offen"', false);

    app(SiteMaterialService::class)->scan($m->qrPayload());

    $this->get("/baustellen/{$site->id}")->assertSee('data-material-status="verladen"', false);
});

it('findet Baustellen anderer Betriebe nicht', function () {
    $site = Site::create(['number' => 'B-1', 'name' => 'Neubau Schmidt']);
    [, $userB] = betrieb('Betrieb B');
    $this->actingAs($userB);

    $this->get("/baustellen/{$site->id}")->assertNotFound();
});

it('zeigt das Kanban-Board und verschiebt Karten', function () {
    $this->stock->receive($this->rohr, $this->platzA, 12);
    $this->stock->issue($this->rohr, $this->platzA, 9);
    $karte = KanbanCard::firstOrFail();

    $this->get('/kanban')
        ->assertOk()
        ->assertSee('data-spalte="open"', false)
        ->assertSee('data-spalte="ordered"', false)
        ->assertSee('data-spalte="delivered"', false)
        ->assertSee('Rohr 20mm');

    $this->post("/kanban/{$karte->id}/status", ['status' => 'ordered'])->assertRedirect('/kanban');
    expect($karte->fresh()->status)->toBe('ordered');

    $this->post("/kanban/{$karte->id}/status", ['status' => 'quatsch'])->assertSessionHasErrors('status');
});

it('bietet die Scan-Seite mit Kamerabereich an', function () {
    $this->get('/scan')->assertOk()->assertSee('id="scanner"', false);
});
