<?php

use App\Exceptions\InsufficientStock;
use App\Exceptions\InvalidScan;
use App\Models\Article;
use App\Models\Site;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use App\Services\SiteMaterialService;
use App\Services\StockService;

beforeEach(function () {
    [$this->tenant, $this->user] = betrieb();
    $this->actingAs($this->user);

    $lager = Warehouse::create(['name' => 'Hauptlager']);
    $this->platz = StorageLocation::create(['warehouse_id' => $lager->id, 'code' => 'A-01', 'row' => 1, 'col' => 1]);
    $this->rohr = Article::create(['number' => 'R-100', 'name' => 'Rohr 20mm', 'unit' => 'm']);
    $this->stock = app(StockService::class);
    $this->stock->receive($this->rohr, $this->platz, 100);

    $this->baustelle = Site::create(['number' => 'B-2026-014', 'name' => 'Neubau Familie Schmidt', 'address' => 'Dorfstraße 3']);
    $this->material = app(SiteMaterialService::class);
});

it('berechnet den Fortschritt aus den abgehakten Aufgaben', function () {
    $a = $this->baustelle->tasks()->create(['title' => 'Rohbauinstallation']);
    $this->baustelle->tasks()->create(['title' => 'Heizkörper montieren']);
    $this->baustelle->tasks()->create(['title' => 'Abnahme']);
    $this->baustelle->tasks()->create(['title' => 'Dokumentation']);

    expect($this->baustelle->progress())->toBe(0);

    $a->complete($this->user);

    expect($a->fresh()->done_at)->not->toBeNull()
        ->and($a->fresh()->done_by)->toBe($this->user->id)
        ->and($this->baustelle->fresh()->progress())->toBe(25);
});

it('macht eine Aufgabe wieder offen', function () {
    $a = $this->baustelle->tasks()->create(['title' => 'Rohbauinstallation']);
    $a->complete($this->user);
    $a->reopen();

    expect($a->fresh()->done_at)->toBeNull()
        ->and($this->baustelle->progress())->toBe(0);
});

it('hat ohne Aufgaben null Prozent Fortschritt', function () {
    expect($this->baustelle->progress())->toBe(0);
});

it('ordnet Material zu und erzeugt einen QR-Inhalt mit allen vier Angaben', function () {
    $m = $this->material->assign($this->baustelle, $this->rohr, $this->platz, 40);

    expect($m->token)->toHaveLength(32)
        ->and($m->loaded_at)->toBeNull();

    $payload = $m->qrPayload();
    expect($payload)->toContain('B-2026-014')
        ->toContain('R-100')
        ->toContain('40')
        ->toContain($m->token);
});

it('liefert die Etikettenansicht als SVG-QR-Code', function () {
    $m = $this->material->assign($this->baustelle, $this->rohr, $this->platz, 40);

    $this->get(route('material.label', $m))
        ->assertOk()
        ->assertSee('<svg', false)
        ->assertSee('Neubau Familie Schmidt')
        ->assertSee('B-2026-014')
        ->assertSee('R-100');
});

it('bucht beim Verladescan den Ausgang und markiert das Material als verladen', function () {
    $m = $this->material->assign($this->baustelle, $this->rohr, $this->platz, 40);

    $geladen = $this->material->scan($m->qrPayload());

    expect($geladen->loaded_at)->not->toBeNull()
        ->and($geladen->loaded_by)->toBe($this->user->id)
        ->and($this->stock->quantityAt($this->rohr, $this->platz))->toBe(60.0);
});

it('weist einen zweiten Scan desselben Etiketts ab und bucht nicht doppelt', function () {
    $m = $this->material->assign($this->baustelle, $this->rohr, $this->platz, 40);
    $this->material->scan($m->qrPayload());

    expect(fn () => $this->material->scan($m->qrPayload()))->toThrow(InvalidScan::class);
    expect($this->stock->quantityAt($this->rohr, $this->platz))->toBe(60.0);
});

it('weist ein verfälschtes Etikett ab', function () {
    $m = $this->material->assign($this->baustelle, $this->rohr, $this->platz, 40);
    $gefaelscht = str_replace('|40|', '|4|', $m->qrPayload());

    expect(fn () => $this->material->scan($gefaelscht))->toThrow(InvalidScan::class);
    expect($this->stock->quantityAt($this->rohr, $this->platz))->toBe(100.0);
});

it('weist unbekannte Etiketten und Müll ab', function () {
    expect(fn () => $this->material->scan('HMS1|X|Y|1|nichtvorhanden'))->toThrow(InvalidScan::class);
    expect(fn () => $this->material->scan('irgendwas'))->toThrow(InvalidScan::class);
});

it('verlädt nichts, wenn der Bestand nicht reicht, und lässt das Material offen', function () {
    $m = $this->material->assign($this->baustelle, $this->rohr, $this->platz, 150);

    expect(fn () => $this->material->scan($m->qrPayload()))->toThrow(InsufficientStock::class);

    expect($m->fresh()->loaded_at)->toBeNull()
        ->and($this->stock->quantityAt($this->rohr, $this->platz))->toBe(100.0);
});

it('zeigt, welches Material einer Baustelle schon verladen ist', function () {
    $a = $this->material->assign($this->baustelle, $this->rohr, $this->platz, 10);
    $this->material->assign($this->baustelle, $this->rohr, $this->platz, 20);
    $this->material->scan($a->qrPayload());

    expect($this->baustelle->materials()->whereNotNull('loaded_at')->count())->toBe(1)
        ->and($this->baustelle->materials()->whereNull('loaded_at')->count())->toBe(1);
});

it('nimmt Scans über die Schnittstelle an und antwortet mit JSON', function () {
    $m = $this->material->assign($this->baustelle, $this->rohr, $this->platz, 40);

    $this->postJson(route('scan'), ['payload' => $m->qrPayload()])
        ->assertOk()
        ->assertJsonPath('site', 'Neubau Familie Schmidt')
        ->assertJsonPath('article', 'Rohr 20mm')
        ->assertJsonPath('quantity', 40);

    $this->postJson(route('scan'), ['payload' => $m->qrPayload()])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Dieses Etikett wurde bereits gescannt.');
});

it('erlaubt Scans nur angemeldeten Benutzern', function () {
    $m = $this->material->assign($this->baustelle, $this->rohr, $this->platz, 40);
    $payload = $m->qrPayload();
    auth()->logout();

    $this->postJson(route('scan'), ['payload' => $payload])->assertUnauthorized();
});

it('trennt Baustellen und Material nach Betrieb', function () {
    $m = $this->material->assign($this->baustelle, $this->rohr, $this->platz, 40);
    $payload = $m->qrPayload();

    [, $fremd] = betrieb('Betrieb B');
    $this->actingAs($fremd);

    expect(Site::count())->toBe(0);
    expect(fn () => $this->material->scan($payload))->toThrow(InvalidScan::class);

    $this->actingAs($this->user);
    expect($this->stock->quantityAt($this->rohr, $this->platz))->toBe(100.0);
});
